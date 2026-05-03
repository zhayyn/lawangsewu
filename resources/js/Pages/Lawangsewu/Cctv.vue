<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    cameras: { type: Array, default: () => [] },
    networkSummary: { type: Object, default: () => ({}) },
});

const STORAGE_KEY = 'lawangsewu-cctv-zone';
const IDLE_TIMEOUT = 10 * 60 * 1000;
// Setelah 20 detik tanpa iframe load event, tampilkan fallback "tidak tersedia"
const IFRAME_LOAD_TIMEOUT_MS = 20_000;
// Stagger delay per kamera agar tidak loading sekaligus
const STAGGER_DELAY_MS = 400;

const expandedCameraIndex = ref(null);
const selectedZone = ref('all');
const isIdle = ref(false);
const isFullscreen = ref(false);
const lastActivity = ref(Date.now());
const gridContainer = ref(null);
const isCameraHeaderExpanded = ref(true);

// Track iframe load state per kunci kamera: null=loading, true=loaded, false=failed
const iframeState = ref({});

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

function startIframeTimer(key) {
    iframeState.value[key] = null; // loading
    if (iframeTimers[key]) clearTimeout(iframeTimers[key]);
    iframeTimers[key] = setTimeout(() => {
        // Jika setelah timeout belum ada event load, tandai sebagai gagal
        if (iframeState.value[key] === null) {
            iframeState.value[key] = false;
        }
    }, IFRAME_LOAD_TIMEOUT_MS);
}

function onIframeLoad(key) {
    if (iframeTimers[key]) clearTimeout(iframeTimers[key]);
    iframeState.value[key] = true;
}

function onIframeError(key) {
    if (iframeTimers[key]) clearTimeout(iframeTimers[key]);
    iframeState.value[key] = false;
}

function retryIframe(key) {
    iframeState.value[key] = null;
    startIframeTimer(key);
    // Force re-render dengan set ulang ke null dan trigger di nextTick
    // Vue akan meng-unmount dan mount ulang iframe via key change trick di template
}

onMounted(() => {
    const storedZone = window.localStorage.getItem(STORAGE_KEY);

    if (storedZone && zoneCatalog.value.some((zone) => zone.key === storedZone)) {
        selectedZone.value = storedZone;
    }

    // Staggered loading: muat kamera satu per satu dengan jeda agar tidak overload
    visibleCameras.value.forEach((camera, i) => {
        setTimeout(() => startIframeTimer(camera.key), i * STAGGER_DELAY_MS);
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
        setTimeout(() => startIframeTimer(camera.key), i * STAGGER_DELAY_MS);
    });
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
                'relative min-h-[calc(100vh-120px)] rounded-[2rem] border border-white/10 bg-[#0a0f18] p-4 sm:p-5 xl:p-6',
                isFullscreen ? 'flex h-screen flex-col overflow-y-auto space-y-0' : 'space-y-5 overflow-visible',
            ]"
        >
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.22),transparent_24%),radial-gradient(circle_at_bottom_left,rgba(20,184,166,0.15),transparent_22%)]" />

            <div
                v-if="!isFullscreen"
                class="relative flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between"
            >
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="rounded-2xl border border-cyan-400/20 bg-cyan-400/10 p-3 text-cyan-300">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>

                        <div>
                            <h1 class="text-xl font-black tracking-tight text-white sm:text-2xl">
                                CCTV Wall Monitoring
                            </h1>
                            <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-slate-400">
                                Pengawasan area pelayanan, persidangan, dan keamanan internal
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4">
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Feed Aktif</p>
                            <p class="mt-2 text-lg font-black text-white">{{ activeCameraCount }}/{{ visibleCameras.length }}</p>
                            <p class="text-xs text-emerald-300">Status live monitor</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Zona Terpantau</p>
                            <p class="mt-2 text-lg font-black text-white">{{ networkSummary.locationCount ?? zoneCount }}</p>
                            <p class="text-xs text-cyan-300">{{ networkSummary.status ?? 'Jaringan stabil' }}</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Latency</p>
                            <p class="mt-2 text-lg font-black text-white">{{ networkSummary.latency ?? '1.2 detik' }}</p>
                            <p class="text-xs text-amber-300">Optimasi low-latency</p>
                        </div>

                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3 backdrop-blur-sm">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Aktivitas Terakhir</p>
                            <p class="mt-2 text-lg font-black text-white">{{ lastActivityLabel }}</p>
                            <p class="text-xs text-slate-300">Auto-idle 10 menit</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-3 xl:justify-end">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-2 text-[11px] font-black uppercase tracking-[0.18em]"
                        :class="isIdle ? 'border-amber-400/25 bg-amber-400/10 text-amber-300' : 'border-emerald-400/25 bg-emerald-400/10 text-emerald-300'"
                    >
                        <span
                            class="h-2 w-2 rounded-full"
                            :class="isIdle ? 'bg-amber-300' : 'animate-pulse bg-emerald-300'"
                        />
                        {{ isIdle ? 'Idle Mode' : 'Live System' }}
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-xs font-black uppercase tracking-[0.18em] text-white transition hover:border-cyan-400/40 hover:bg-white/10"
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
                    class="rounded-full border px-3 py-2 text-[11px] font-black uppercase tracking-[0.16em] transition"
                    :class="selectedZone === zone.key ? 'border-cyan-400/30 bg-cyan-400/15 text-cyan-200' : 'border-white/10 bg-white/5 text-slate-300 hover:border-white/20 hover:text-white'"
                    @click="selectZone(zone.key)"
                >
                    {{ zone.label }} · {{ zone.count }}
                </button>

                <span
                    v-if="hiddenCameraCount > 0"
                    class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-[11px] font-bold text-slate-400"
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
                            'group relative overflow-hidden rounded-[1.5rem] border border-white/10 bg-black text-left transition duration-300 hover:-translate-y-0.5 hover:border-cyan-400/35 hover:shadow-[0_20px_60px_rgba(34,211,238,0.08)]',
                            isFullscreen
                                ? 'fullscreen-camera-tile'
                                : 'aspect-[16/10] min-h-[220px] sm:aspect-video xl:min-h-[240px]',
                        ]"
                        @click="expandCamera(index)"
                    >
                        <iframe
                            :src="iframeState[camera.key] === undefined ? '' : camera.iframeSrc"
                            :title="camera.name"
                            class="absolute inset-0 h-full w-full border-0 opacity-90 transition-opacity group-hover:opacity-100"
                            loading="lazy"
                            allow="autoplay; fullscreen; picture-in-picture"
                            referrerpolicy="no-referrer-when-downgrade"
                            sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
                            @load="onIframeLoad(camera.key)"
                            @error="onIframeError(camera.key)"
                        />

                        <!-- Overlay: loading saat belum ada event -->
                        <div
                            v-if="iframeState[camera.key] === null"
                            class="pointer-events-none absolute inset-0 flex items-center justify-center bg-black/60"
                        >
                            <svg class="h-6 w-6 animate-spin text-cyan-400" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" fill="currentColor" />
                            </svg>
                        </div>

                        <!-- Overlay: gagal memuat (timeout atau error) -->
                        <div
                            v-else-if="iframeState[camera.key] === false"
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
                                @click.stop="retryIframe(camera.key)"
                            >
                                Coba lagi
                            </button>
                        </div>

                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black via-black/10 to-transparent" />

                        <div class="absolute inset-x-0 top-0 flex items-start justify-between gap-3 p-4">
                            <div
                                class="min-w-0 rounded-xl border border-white/10 bg-black/45 px-2.5 py-1.5 backdrop-blur-md transition-all duration-300 group-hover:bg-black/60"
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
                                :class="camera.status === 'LIVE' ? 'border-emerald-400/20 bg-emerald-400/10 text-emerald-300' : 'border-rose-400/20 bg-rose-400/10 text-rose-300'"
                            >
                                {{ camera.status }}
                            </span>
                        </div>

                        <div class="absolute inset-x-0 bottom-0 flex items-end justify-between gap-3 p-4">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-cyan-300">
                                    {{ camera.signal }}
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
                    class="flex min-h-[360px] items-center justify-center rounded-[2rem] border border-dashed border-white/10 bg-white/[0.03] px-6 text-center"
                >
                    <div class="max-w-md space-y-3">
                        <p class="text-lg font-black text-white">Belum ada feed untuk zona ini</p>
                        <p class="text-sm leading-7 text-slate-300">
                            Pilih zona lain atau aktifkan kamera tambahan dari panel seed/data CCTV untuk menampilkan feed pada monitor ini.
                        </p>
                    </div>
                </div>
            </div>

            <Transition name="scale">
                <div
                    v-if="selectedCamera"
                    data-testid="expanded-camera"
                    class="fixed inset-x-3 bottom-20 top-[5.5rem] z-40 overflow-hidden rounded-[2rem] border border-cyan-400/25 bg-black shadow-[0_24px_120px_rgba(0,0,0,0.65)] sm:inset-x-6 sm:bottom-24 sm:top-24 xl:left-[max(22rem,calc((100vw-1800px)/2+2rem))] xl:right-[max(2rem,calc((100vw-1800px)/2+2rem))] xl:bottom-8 xl:top-24"
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

                    <iframe
                        :src="selectedCamera.iframeSrc"
                        :title="selectedCamera.name"
                        class="h-full w-full border-0"
                        allow="autoplay; fullscreen"
                        referrerpolicy="strict-origin-when-cross-origin"
                    />

                    <div class="absolute inset-x-0 bottom-0 z-10 bg-gradient-to-t from-black via-black/90 to-transparent p-4 sm:p-5">
                        <div class="flex flex-wrap items-center gap-3 text-[11px] font-bold uppercase tracking-[0.16em]">
                            <span class="rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-2 text-emerald-300">{{ selectedCamera.status }}</span>
                            <span class="rounded-full border border-white/10 bg-white/5 px-3 py-2 text-slate-200">{{ selectedCamera.signal }}</span>
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
</style>
