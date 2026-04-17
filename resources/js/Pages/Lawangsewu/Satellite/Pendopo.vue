<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    appMeta:          { type: Object, required: true },
    navGroups:        { type: Array,  default: () => [] },
    pendopoUrl:       { type: String, required: true },
    guestbookListUrl: { type: String, required: true },
    metricUrl:        { type: String, required: true },
    stats: {
        type: Object,
        default: () => ({ day: 0, week: 0, month: 0, year: 0, all: 0 }),
    },
});

const isLoading = ref(true);
const hasLoadTimeout = ref(false);
const iframeReloadKey = ref(0);
const isCompactDevice = ref(false);
const autoRedirecting = ref(false);

let loadTimeoutHandle = null;
let sessionStart = 0;

const resolvedPendopoUrl = computed(() => {
    if (props.pendopoUrl.includes('embedded=1')) {
        return props.pendopoUrl;
    }

    return props.pendopoUrl.includes('?')
        ? `${props.pendopoUrl}&embedded=1`
        : `${props.pendopoUrl}?embedded=1`;
});

const directPendopoUrl = computed(() => resolvedPendopoUrl.value.replace('embedded=1', 'embedded=0'));

const detectCompactDevice = () => {
    if (typeof window === 'undefined') {
        return;
    }

    const pointerCoarse = window.matchMedia('(pointer: coarse)').matches;
    const compactWidth = window.matchMedia('(max-width: 1180px)').matches;
    const mobileUA = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');
    isCompactDevice.value = compactWidth || pointerCoarse || mobileUA;
};

const clearLoadTimeout = () => {
    if (loadTimeoutHandle) {
        window.clearTimeout(loadTimeoutHandle);
        loadTimeoutHandle = null;
    }
};

const emitMetric = (eventName, mode) => {
    if (typeof window === 'undefined' || !props.metricUrl) {
        return;
    }

    const elapsed = Math.max(0, Math.round(performance.now() - sessionStart));
    const params = new URLSearchParams({
        event: eventName,
        duration_ms: String(elapsed),
        compact: isCompactDevice.value ? '1' : '0',
        mode,
    });

    const image = new Image();
    image.src = `${props.metricUrl}?${params.toString()}`;
};

const startLoadTimeout = () => {
    clearLoadTimeout();
    hasLoadTimeout.value = false;
    isLoading.value = true;

    loadTimeoutHandle = window.setTimeout(() => {
        hasLoadTimeout.value = true;
        isLoading.value = false;
        emitMetric('iframe_timeout', 'iframe');
    }, 9000);
};

const retryFrame = () => {
    emitMetric('retry_iframe', 'iframe');
    iframeReloadKey.value += 1;
    startLoadTimeout();
};

const openDirectPendopo = () => {
    emitMetric('open_direct', isCompactDevice.value ? 'compact-direct' : 'desktop-direct');
    window.location.assign(directPendopoUrl.value);
};

const handleLoad = () => {
    clearLoadTimeout();
    hasLoadTimeout.value = false;
    isLoading.value = false;
    emitMetric('iframe_load', 'iframe');
};

onMounted(() => {
    sessionStart = performance.now();
    detectCompactDevice();

    if (isCompactDevice.value) {
        autoRedirecting.value = true;
        emitMetric('auto_redirect', 'compact-direct');
        window.setTimeout(() => {
            openDirectPendopo();
        }, 180);
        return;
    }

    startLoadTimeout();
    window.addEventListener('resize', detectCompactDevice);
});

onBeforeUnmount(() => {
    clearLoadTimeout();
    window.removeEventListener('resize', detectCompactDevice);
});
</script>

<template>
    <Head title="Buku Tamu Pendopo" />

    <LawangsewuLayout current-route="satellite.pendopo" :nav-groups="navGroups" :app-meta="appMeta">
        <div class="flex min-h-[calc(100dvh-86px)] flex-col gap-4 pb-2 lg:min-h-[calc(100dvh-94px)]">

            <!-- Stats + Action Bar -->
            <section class="shrink-0 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <!-- Title -->
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-sm font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Buku Tamu Pendopo</h1>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-3)]">Modul Satelit Aktif</p>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="flex flex-wrap gap-2">
                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Hari Ini</p>
                            <p class="text-lg font-black text-[var(--text-1)]">{{ stats.day }}</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Minggu Ini</p>
                            <p class="text-lg font-black text-[var(--text-1)]">{{ stats.week }}</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Bulan Ini</p>
                            <p class="text-lg font-black text-[var(--text-1)]">{{ stats.month }}</p>
                        </div>
                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-amber-500">Total Tamu</p>
                            <p class="text-lg font-black text-amber-400">{{ stats.all }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <div v-if="isLoading" class="flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 py-1.5">
                            <div class="h-2 w-2 animate-ping rounded-full bg-amber-500"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-amber-500">Menghubungkan...</span>
                        </div>
                        <a :href="guestbookListUrl" class="secondary-button text-[10px] font-black uppercase">
                            Riwayat Tamu
                        </a>
                        <button type="button" class="secondary-button text-[10px] font-black uppercase" @click="openDirectPendopo">
                            Buka Langsung
                        </button>
                        <a :href="directPendopoUrl" target="_blank" class="secondary-button text-[10px] font-black uppercase">
                            Buka Tab Baru
                        </a>
                    </div>
                </div>
            </section>

            <section
                v-if="autoRedirecting"
                class="flex flex-1 items-center justify-center rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-6 text-center"
            >
                <div class="mx-auto max-w-xl space-y-3">
                    <div class="mx-auto h-14 w-14 rounded-2xl bg-amber-500/15 text-amber-500 ring-1 ring-amber-500/20 flex items-center justify-center">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </div>
                    <p class="text-sm font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Menyiapkan Akses Pendopo</p>
                    <p class="text-xs text-[var(--text-3)]">Perangkat iPad/HP diarahkan langsung ke Form Pendopo agar lebih cepat dan stabil.</p>
                    <button type="button" class="secondary-button text-xs font-black uppercase" @click="openDirectPendopo">
                        Buka Sekarang
                    </button>
                </div>
            </section>

            <!-- Satellite Frame -->
            <div v-else class="relative min-h-[50dvh] flex-1 overflow-hidden rounded-2xl border border-[var(--border)]">
                <iframe
                    :key="iframeReloadKey"
                    :src="resolvedPendopoUrl"
                    class="h-full w-full border-0 bg-white"
                    @load="handleLoad"
                    title="Pendopo Guestbook"
                    allow="camera; microphone"
                ></iframe>

                <!-- Loading Overlay -->
                <div v-if="isLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-[var(--surface-1)]/80 backdrop-blur-md">
                    <div class="relative mb-6 h-24 w-24">
                        <div class="absolute inset-0 animate-pulse rounded-3xl bg-amber-500/10"></div>
                        <div class="absolute inset-4 flex items-center justify-center rounded-2xl bg-amber-600 text-2xl font-black text-white shadow-xl shadow-amber-600/30">
                            P
                        </div>
                    </div>
                    <p class="animate-pulse text-xs font-black uppercase tracking-[0.5em]">Menyiapkan Akses Pendopo</p>
                </div>

                <div v-if="hasLoadTimeout" class="absolute inset-x-3 top-3 z-20 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3 text-[11px] text-amber-500 backdrop-blur-sm">
                    <p class="font-black uppercase tracking-[0.12em]">Akses lambat terdeteksi</p>
                    <p class="mt-1 text-amber-400">Pratinjau iframe tidak merespons tepat waktu. Gunakan buka langsung agar lebih stabil.</p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        <button type="button" class="secondary-button text-[10px] font-black uppercase" @click="retryFrame">Coba Lagi</button>
                        <button type="button" class="secondary-button text-[10px] font-black uppercase" @click="openDirectPendopo">Buka Langsung</button>
                    </div>
                </div>
            </div>

        </div>
    </LawangsewuLayout>
</template>
