<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import CctvStreamFrame from '@/Components/lawangsewu/CctvStreamFrame.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    cameras: { type: Array, default: () => [] },
    networkSummary: { type: Object, default: () => ({}) },
});

const page = usePage();
const isSuperAdmin = computed(() => page.props.auth.isSuperAdmin);
// Semua user melihat preview ringan di grid; expanded modal = resolusi penuh
const canViewMultiple = computed(() => true);

const STORAGE_KEY = 'lawangsewu-cctv-zone';
const IDLE_TIMEOUT = 10 * 60 * 1000;
// Setelah 20 detik tanpa iframe load event, tampilkan fallback "tidak tersedia"
const IFRAME_LOAD_TIMEOUT_MS = 20_000;
// Stagger delay per kamera agar tidak loading sekaligus
const STAGGER_DELAY_MS = 400;

const DEFAULT_ZONE = 'Pelayanan';

const expandedCameraIndex = ref(null);
const selectedZone = ref(DEFAULT_ZONE);
const isIdle = ref(false);
const isFullscreen = ref(false);
const lastActivity = ref(Date.now());
const gridContainer = ref(null);
const isCameraHeaderExpanded = ref(true);

// Track iframe load state per kunci kamera+mode: null=loading, true=loaded, false=failed
const iframeState = ref({});
const iframeSourceMode = ref({});

let idleTimer = null;
let cameraHeaderTimer = null;
const iframeTimers = {};

const zoneCatalog = computed(() => {
    const counts = props.cameras.reduce((summary, camera) => {
        summary[camera.zone] = (summary[camera.zone] ?? 0) + 1;
        return summary;
    }, {});

    return [
        { key: 'all', label: 'Semua Zona', count: props.cameras.length },
        ...Object.entries(counts).map(([zone, count]) => ({
            key: zone,
            label: zone,
            count,
        })),
    ];
});

const filteredCameras = computed(() => {
    if (selectedZone.value === 'all') {
        return props.cameras;
    }

    return props.cameras.filter((camera) => camera.zone === selectedZone.value);
});

const visibleCameras = computed(() => (
    isFullscreen.value
        ? filteredCameras.value
        : filteredCameras.value.slice(0, 16)
));

const selectedCamera = computed(() => {
    if (expandedCameraIndex.value === null) {
        return null;
    }

    return visibleCameras.value[expandedCameraIndex.value] ?? null;
});

const activeCameraCount = computed(() => visibleCameras.value.filter((camera) => camera.status === 'LIVE').length);
const hiddenCameraCount = computed(() => Math.max(0, filteredCameras.value.length - visibleCameras.value.length));
const zoneCount = computed(() => Math.max(0, zoneCatalog.value.length - 1));
const lastActivityLabel = computed(() => new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
}).format(new Date(lastActivity.value)));

function resetIdleTimer() {
    isIdle.value = false;
    lastActivity.value = Date.now();

    if (idleTimer) {
        clearTimeout(idleTimer);
    }

    idleTimer = window.setTimeout(() => {
        isIdle.value = true;
    }, IDLE_TIMEOUT);
}

function resetCameraHeaderTimer() {
    isCameraHeaderExpanded.value = true;

    if (cameraHeaderTimer) {
        clearTimeout(cameraHeaderTimer);
    }

    cameraHeaderTimer = window.setTimeout(() => {
        isCameraHeaderExpanded.value = false;
    }, 3000);
}

function toggleFullscreenApp() {
    if (!gridContainer.value) {
        return;
    }

    if (!document.fullscreenElement) {
        gridContainer.value.requestFullscreen?.().catch(() => {});
        return;
    }

    document.exitFullscreen?.();
}

function syncFullscreenState() {
    isFullscreen.value = document.fullscreenElement === gridContainer.value;
}

function expandCamera(index) {
    expandedCameraIndex.value = expandedCameraIndex.value === index ? null : index;
    resetIdleTimer();
}

function closeExpandedCamera() {
    expandedCameraIndex.value = null;
}

function selectZone(zoneKey) {
    selectedZone.value = zoneKey;
    expandedCameraIndex.value = null;
    resetIdleTimer();
    resetCameraHeaderTimer();
}

function resumeStreams() {
    resetIdleTimer();
    resetCameraHeaderTimer();
}

function handleKeydown(event) {
    if (event.key === 'Escape' && selectedCamera.value) {
        closeExpandedCamera();
        return;
    }

    resetIdleTimer();
}

// ──────────────────────────────────────────────
// Iframe load state helpers
// ──────────────────────────────────────────────

function iframeKey(camera, quality = 'preview') {
    return `${camera.key}:${quality}`;
}

function sourceModeKey(camera, quality = 'preview') {
    return `${camera.key}:${quality}:source`;
}

function currentSourceMode(camera, quality = 'preview') {
    return iframeSourceMode.value[sourceModeKey(camera, quality)] || 'primary';
}

function currentCameraSrc(camera, quality = 'preview') {
    const mode = currentSourceMode(camera, quality);

    if (mode === 'fallback') {
        return camera.fallbackSrc || camera.iframeSrc || '';
    }

    if (quality === 'full') {
        return camera.fullSrc || camera.previewSrc || camera.iframeSrc || '';
    }

    return camera.previewSrc || camera.iframeSrc || '';
}

function sourceLabel(camera, quality = 'preview') {
    if (currentSourceMode(camera, quality) === 'fallback') {
        return 'ACO Fallback';
    }

    return quality === 'full' ? 'HD Relay' : 'SD Relay';
}

function hasFallback(camera) {
    const fallback = camera.fallbackSrc || '';
    const primary = camera.previewSrc || camera.iframeSrc || '';

    return fallback !== '' && fallback !== primary;
}

function startIframeTimer(camera, quality = 'preview') {
    const key = iframeKey(camera, quality);

    iframeState.value[key] = null; // loading
    if (iframeTimers[key]) clearTimeout(iframeTimers[key]);
    iframeTimers[key] = setTimeout(() => {
        // Jika setelah timeout belum ada event load, pindah ke ACO sebagai backup.
        if (iframeState.value[key] === null) {
            if (currentSourceMode(camera, quality) === 'primary' && hasFallback(camera)) {
                iframeSourceMode.value[sourceModeKey(camera, quality)] = 'fallback';
                iframeState.value[key] = undefined;
                setTimeout(() => startIframeTimer(camera, quality), 50);
                return;
            }

            iframeState.value[key] = false;
        }
    }, IFRAME_LOAD_TIMEOUT_MS);
}

function onIframeLoad(camera, quality = 'preview') {
    const key = iframeKey(camera, quality);

    if (iframeTimers[key]) clearTimeout(iframeTimers[key]);
    iframeState.value[key] = true;
}

function onIframeError(camera, quality = 'preview') {
    const key = iframeKey(camera, quality);

    if (iframeTimers[key]) clearTimeout(iframeTimers[key]);

    if (currentSourceMode(camera, quality) === 'primary' && hasFallback(camera)) {
        iframeSourceMode.value[sourceModeKey(camera, quality)] = 'fallback';
        iframeState.value[key] = undefined;
        setTimeout(() => startIframeTimer(camera, quality), 50);
        return;
    }

    iframeState.value[key] = false;
}

function retryIframe(camera, quality = 'preview') {
    const key = iframeKey(camera, quality);

    iframeSourceMode.value[sourceModeKey(camera, quality)] = 'primary';
    iframeState.value[key] = null;
    startIframeTimer(camera, quality);
}

onMounted(() => {
    const storedZone = window.localStorage.getItem(STORAGE_KEY);

    // Hanya pakai localStorage jika user pernah memilih zona sebelumnya
    // Default ke 'Pelayanan' agar tidak load semua zona sekaligus
    if (storedZone && zoneCatalog.value.some((zone) => zone.key === storedZone)) {
        selectedZone.value = storedZone;
    } else {
        selectedZone.value = DEFAULT_ZONE;
    }

    // Staggered loading: muat kamera satu per satu dengan jeda agar tidak overload
    visibleCameras.value.forEach((camera, i) => {
        setTimeout(() => startIframeTimer(camera, 'preview'), i * STAGGER_DELAY_MS);
    });

    resetIdleTimer();
    resetCameraHeaderTimer();
    window.addEventListener('mousedown', resetIdleTimer);
    window.addEventListener('touchstart', resetIdleTimer, { passive: true });
    window.addEventListener('keydown', handleKeydown);
    document.addEventListener('fullscreenchange', syncFullscreenState);
});

onUnmounted(() => {
    if (idleTimer) {
        clearTimeout(idleTimer);
    }

    if (cameraHeaderTimer) {
        clearTimeout(cameraHeaderTimer);
    }

    Object.values(iframeTimers).forEach((t) => clearTimeout(t));

    window.removeEventListener('mousedown', resetIdleTimer);
    window.removeEventListener('touchstart', resetIdleTimer);
    window.removeEventListener('keydown', handleKeydown);
    document.removeEventListener('fullscreenchange', syncFullscreenState);
});

watch(selectedZone, (value) => {
    window.localStorage.setItem(STORAGE_KEY, value);
    // Staggered setelah ganti zona
    visibleCameras.value.forEach((camera, i) => {
        setTimeout(() => startIframeTimer(camera, 'preview'), i * STAGGER_DELAY_MS);
    });
});

watch(selectedCamera, (camera) => {
    if (!camera) {
        return;
    }

    startIframeTimer(camera, 'full');
});
</script>

<template>
    <Head title="CCTV Monitor" />

    <LawangsewuLayout
        current-route="cctv"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div
            ref="gridContainer"
            :class="[
                'relative min-h-[calc(100vh-120px)] rounded-lg border border-slate-200 bg-slate-50 p-3 text-slate-900 shadow-sm dark:border-white/10 dark:bg-[#0a0f18] dark:text-slate-100 sm:p-5 xl:p-6',
                isFullscreen ? 'flex h-screen flex-col overflow-y-auto space-y-0' : 'space-y-5 overflow-visible',
            ]"
        >
            <div
                v-if="!isFullscreen"
                class="relative flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between"
            >
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg border border-emerald-200 bg-white p-3 text-emerald-600 shadow-sm dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-xl font-black tracking-tight text-slate-950 dark:text-white sm:text-2xl">
                                CCTV Wall Monitoring
                            </h1>
                            <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">
                                Pengawasan area pelayanan, persidangan, dan keamanan internal
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
                        <div class="rounded-lg border border-emerald-200 bg-white px-4 py-3 shadow-sm dark:border-emerald-400/20 dark:bg-white/5">
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Feed Aktif</p>
                            <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ activeCameraCount }}/{{ visibleCameras.length }}</p>
                            <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Status live monitor</p>
                        </div>

                        <div class="rounded-lg border border-cyan-200 bg-white px-4 py-3 shadow-sm dark:border-cyan-400/20 dark:bg-white/5">
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Zona Terpantau</p>
                            <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ networkSummary.locationCount ?? zoneCount }}</p>
                            <p class="text-xs font-semibold text-cyan-700 dark:text-cyan-300">{{ networkSummary.status ?? 'Jaringan stabil' }}</p>
                        </div>

                        <div class="rounded-lg border border-amber-200 bg-white px-4 py-3 shadow-sm dark:border-amber-400/20 dark:bg-white/5">
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Latency</p>
                            <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ networkSummary.latency ?? '1.2 detik' }}</p>
                            <p class="text-xs font-semibold text-amber-700 dark:text-amber-300">Optimasi low-latency</p>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm dark:border-white/10 dark:bg-white/5">
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">Aktivitas Terakhir</p>
                            <p class="mt-2 text-lg font-black text-slate-950 dark:text-white">{{ lastActivityLabel }}</p>
                            <p class="text-xs font-semibold text-slate-500 dark:text-slate-300">Auto-idle 10 menit</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-[11px] font-black uppercase tracking-[0.18em]"
                        :class="isIdle ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/25 dark:bg-amber-400/10 dark:text-amber-300' : 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-300'"
                    >
                        <span
                            class="h-2 w-2 rounded-full"
                            :class="isIdle ? 'bg-amber-300' : 'animate-pulse bg-emerald-300'"
                        />
                        {{ isIdle ? 'Idle Mode' : 'Live System' }}
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-lg border border-cyan-200 bg-white px-4 py-3 text-xs font-black uppercase tracking-[0.14em] text-cyan-700 shadow-sm transition hover:border-cyan-300 hover:bg-cyan-50 dark:border-cyan-400/25 dark:bg-cyan-400/10 dark:text-cyan-200 dark:hover:bg-cyan-400/15"
                        @click="toggleFullscreenApp"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                        Fullscreen
                    </button>
                </div>
            </div>

            <div
                v-if="!isFullscreen"
                class="relative flex flex-wrap items-center gap-2"
            >
                <button
                    v-for="zone in zoneCatalog"
                    :key="zone.key"
                    type="button"
                    :data-testid="`zone-filter-${zone.key}`"
                    class="rounded-lg border px-3 py-2 text-[11px] font-black uppercase tracking-[0.12em] shadow-sm transition"
                    :class="selectedZone === zone.key ? 'border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-400/15 dark:text-emerald-200' : 'border-slate-200 bg-white text-slate-600 hover:border-cyan-300 hover:text-cyan-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-300 dark:hover:border-cyan-400/30 dark:hover:text-white'"
                    @click="selectZone(zone.key)"
                >
                    {{ zone.label }} · {{ zone.count }}
                </button>

                <span
                    v-if="hiddenCameraCount > 0"
                    class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-[11px] font-bold text-slate-500 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-slate-400"
                >
                    +{{ hiddenCameraCount }} feed lain disembunyikan
                </span>
            </div>

            <div :class="['relative', isFullscreen ? 'flex-1 min-h-0' : 'flex-1']">
                <div
                    v-if="isFullscreen"
                    class="absolute right-0 top-0 z-20 flex items-center gap-2"
                >
                    <span
                        class="rounded-full border border-cyan-400/20 bg-black/55 px-3 py-2 text-[11px] font-black uppercase tracking-[0.16em] text-cyan-200 backdrop-blur-md"
                    >
                        {{ filteredCameras.length }} kamera
                    </span>
                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-black/55 px-4 py-3 text-xs font-black uppercase tracking-[0.18em] text-white backdrop-blur-md transition hover:border-cyan-400/40 hover:bg-white/10"
                        @click="toggleFullscreenApp"
                    >
                        Keluar Fullscreen
                    </button>
                </div>

                <Transition name="fade">
                    <div
                        v-if="isIdle"
                        class="absolute inset-0 z-30 flex flex-col items-center justify-center rounded-[2rem] border border-white/10 bg-black/80 px-6 text-center backdrop-blur-xl"
                    >
                        <div class="mb-6 flex h-24 w-24 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-300">
                            <svg class="h-10 w-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>

                        <h2 class="text-2xl font-black text-white">Feed Diistirahatkan Sementara</h2>
                        <p class="mt-3 max-w-xl text-sm leading-7 text-slate-300">
                            Sistem menghentikan render stream setelah 10 menit tanpa interaksi untuk menjaga bandwidth dan kestabilan monitor operator.
                        </p>

                        <button
                            type="button"
                            class="mt-8 rounded-2xl bg-cyan-500 px-6 py-3 text-sm font-black uppercase tracking-[0.18em] text-slate-950 transition hover:bg-cyan-400"
                            @click="resumeStreams"
                        >
                            Resume Feed
                        </button>
                    </div>
                </Transition>

                <div
                    v-if="visibleCameras.length > 0 && !isIdle"
                    :class="[
                        'grid',
                        isFullscreen
                            ? 'grid-cols-1 gap-3 pt-16 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4'
                            : 'grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4',
                    ]"
                >
                    <button
                        v-for="(camera, index) in visibleCameras"
                        :key="camera.key"
                        type="button"
                        data-testid="camera-tile"
                        :class="[
                            'group relative overflow-hidden rounded-lg border border-slate-200 bg-slate-950 text-left shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow-md dark:border-white/10 dark:bg-black dark:hover:border-cyan-400/35 dark:hover:shadow-[0_20px_60px_rgba(34,211,238,0.08)]',
                            isFullscreen
                                ? 'fullscreen-camera-tile'
                                : 'aspect-video min-h-[210px] sm:min-h-0',
                        ]"
                        @click="expandCamera(index)"
                    >
                        <CctvStreamFrame
                            :src="iframeState[iframeKey(camera, 'preview')] === undefined ? '' : currentCameraSrc(camera, 'preview')"
                            :title="camera.name"
                            :media-class="[
                                'absolute inset-0 h-full w-full border-0 object-cover opacity-90 transition-opacity group-hover:opacity-100',
                                isFullscreen ? 'iframe-fullres' : 'iframe-preview',
                            ].join(' ')"
                            @loaded="onIframeLoad(camera, 'preview')"
                            @error="onIframeError(camera, 'preview')"
                        />

                        <!-- Overlay: loading saat belum ada event -->
                        <div
                            v-if="iframeState[iframeKey(camera, 'preview')] === null"
                            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/60"
                        >
                            <svg class="h-6 w-6 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" />
                            </svg>
                        </div>

                        <!-- Overlay: gagal memuat (timeout atau error) -->
                        <div
                            v-else-if="iframeState[iframeKey(camera, 'preview')] === false"
                            class="absolute inset-0 flex flex-col items-center justify-center gap-3 bg-black/80 text-center"
                        >
                            <svg class="h-8 w-8 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18.364 5.636a9 9 0 11-12.728 12.728A9 9 0 0118.364 5.636z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4m0 4h.01" />
                            </svg>
                            <p class="text-[11px] font-black uppercase tracking-[0.15em] text-rose-300">Stream tidak tersedia</p>
                            <button
                                type="button"
                                class="rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.15em] text-white transition hover:bg-white/20"
                                @click.stop="retryIframe(camera, 'preview')"
                            >
                                Coba lagi
                            </button>
                        </div>

                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black via-black/10 to-transparent" />

                        <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-3 p-4">
                            <div
                                class="min-w-0 rounded-lg border border-white/15 bg-black/50 px-2.5 py-1.5 backdrop-blur-md transition-all duration-300 group-hover:bg-black/65"
                                :class="isCameraHeaderExpanded ? 'max-w-[72%]' : 'max-w-[55%]'"
                            >
                                <p
                                    class="truncate font-black uppercase tracking-[0.13em] text-white transition-all duration-300"
                                    :class="isCameraHeaderExpanded ? 'text-[10px]' : 'text-[9px]'"
                                >
                                    {{ camera.name }}
                                </p>
                                <p
                                    class="mt-0.5 truncate font-bold uppercase tracking-[0.13em] text-slate-300 transition-all duration-300"
                                    :class="isCameraHeaderExpanded ? 'text-[9px] opacity-90' : 'text-[8px] opacity-60'"
                                >
                                    {{ camera.zone }} · {{ camera.updatedAt }}
                                </p>
                            </div>

                            <span
                                class="rounded-full border px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.15em]"
                                :class="currentSourceMode(camera, 'preview') === 'fallback' ? 'border-amber-300/50 bg-amber-400/15 text-amber-200' : (camera.status === 'LIVE' ? 'border-emerald-300/50 bg-emerald-400/15 text-emerald-200' : 'border-rose-300/50 bg-rose-400/15 text-rose-200')"
                            >
                                {{ currentSourceMode(camera, 'preview') === 'fallback' ? 'BACKUP' : camera.status }}
                            </span>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-300">
                                    {{ sourceLabel(camera, 'preview') }}
                                </p>
                                <p class="mt-1 text-xs font-semibold text-white">
                                    {{ camera.resolution }}
                                </p>
                            </div>

                            <span class="rounded-full border border-white/10 bg-black/55 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-200 backdrop-blur-md">
                                Buka detail
                            </span>
                        </div>
                    </button>
                </div>

                <div
                    v-else-if="!isIdle"
                    class="flex min-h-[360px] items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white px-6 text-center shadow-sm dark:border-white/10 dark:bg-white/[0.03]"
                >
                    <div class="max-w-md space-y-3">
                        <p class="text-lg font-black text-slate-950 dark:text-white">Belum ada feed untuk zona ini</p>
                        <p class="text-sm leading-7 text-slate-500 dark:text-slate-300">
                            Pilih zona lain atau aktifkan kamera tambahan dari panel seed/data CCTV untuk menampilkan feed pada monitor ini.
                        </p>
                    </div>
                </div>
            </div>

            <Transition name="scale">
                <div
                    v-if="selectedCamera"
                    data-testid="expanded-camera"
                    class="fixed inset-x-3 bottom-16 top-[4.75rem] z-40 overflow-hidden rounded-lg border border-cyan-400/25 bg-black shadow-[0_24px_120px_rgba(0,0,0,0.65)] sm:inset-x-6 sm:bottom-20 sm:top-20 xl:left-[max(22rem,calc((100vw-1800px)/2+2rem))] xl:right-[max(2rem,calc((100vw-1800px)/2+2rem))] xl:bottom-8 xl:top-24"
                >
                    <div class="absolute inset-x-0 top-0 z-10 flex items-start justify-between gap-4 p-4 sm:p-5">
                        <div class="rounded-[1.25rem] border border-white/10 bg-black/60 px-4 py-3 backdrop-blur-xl">
                            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-300">Expanded Feed</p>
                            <h2 class="mt-1 text-lg font-black text-white">{{ selectedCamera.name }}</h2>
                            <p class="mt-1 text-xs text-slate-300">{{ selectedCamera.zone }} · {{ selectedCamera.updatedAt }}</p>
                        </div>

                        <button
                            type="button"
                            data-testid="close-expanded-camera"
                            class="rounded-full border border-white/10 bg-black/60 p-3 text-white backdrop-blur-xl transition hover:border-white/20 hover:bg-white/10"
                            @click="closeExpandedCamera"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <CctvStreamFrame
                        :src="iframeState[iframeKey(selectedCamera, 'full')] === undefined ? '' : currentCameraSrc(selectedCamera, 'full')"
                        :title="selectedCamera.name"
                        controls
                        media-class="h-full w-full border-0 object-contain"
                        @loaded="onIframeLoad(selectedCamera, 'full')"
                        @error="onIframeError(selectedCamera, 'full')"
                    />

                    <div
                        v-if="iframeState[iframeKey(selectedCamera, 'full')] === null"
                        class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/70"
                    >
                        <div class="rounded-2xl border border-white/10 bg-black/70 px-5 py-4 text-center backdrop-blur-xl">
                            <svg class="mx-auto h-6 w-6 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" />
                            </svg>
                            <p class="mt-3 text-[11px] font-black uppercase tracking-[0.18em] text-cyan-200">Memuat feed {{ sourceLabel(selectedCamera, 'full') }}</p>
                        </div>
                    </div>

                    <div
                        v-else-if="iframeState[iframeKey(selectedCamera, 'full')] === false"
                        class="absolute inset-0 flex items-center justify-center bg-black/80 px-6 text-center"
                    >
                        <div class="max-w-sm rounded-2xl border border-rose-400/20 bg-rose-950/30 p-6">
                            <p class="text-sm font-black uppercase tracking-[0.18em] text-rose-200">Feed tidak tersedia</p>
                            <button
                                type="button"
                                class="mt-4 rounded-full border border-white/10 bg-white/10 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-white transition hover:bg-white/20"
                                @click="retryIframe(selectedCamera, 'full')"
                            >
                                Coba Muat Ulang
                            </button>
                        </div>
                    </div>

                    <div class="absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-black via-black/90 to-transparent p-4 sm:p-5">
                        <div class="flex flex-wrap items-center gap-3 text-[11px] font-bold uppercase tracking-[0.16em]">
                            <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-2 text-emerald-300">{{ selectedCamera.status }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-slate-200">{{ sourceLabel(selectedCamera, 'full') }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-slate-200">{{ selectedCamera.resolution }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-slate-200">Zona {{ selectedCamera.zone }}</span>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </LawangsewuLayout>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.35s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.scale-enter-active,
.scale-leave-active {
    transition: all 0.28s ease;
}

.scale-enter-from,
.scale-leave-to {
    opacity: 0;
    transform: scale(0.97);
}

.fullscreen-camera-tile {
    aspect-ratio: 16 / 9;
    min-height: calc((100vh - 7.75rem) / 4);
}

/*
  Preview ringan di grid: batasi render quality hint agar browser
  tidak upscale/memproses lebih dari yang diperlukan untuk tile kecil.
  Fullscreen: tampilkan di resolusi penuh tanpa batasan.
*/
.iframe-preview {
    image-rendering: auto;
    will-change: auto;
    /* Paksa browser render di ukuran tile saja (hemat resource) */
    contain: strict;
}

.iframe-fullres {
    image-rendering: auto;
    will-change: transform;
    contain: none;
}
</style>
