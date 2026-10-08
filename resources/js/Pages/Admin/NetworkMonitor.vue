<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import {
    Activity,
    ArrowDownToLine,
    ArrowUpFromLine,
    CircleDot,
    Globe2,
    Link2,
    RefreshCcw,
    Router,
    Server,
    Signal,
    Video,
    Wifi,
} from 'lucide-vue-next';
import { computed, markRaw, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    appMeta: { type: Object, required: true },
    endpoints: { type: Object, default: () => ({}) },
    navGroups: { type: Array, default: () => [] },
    snapshot: { type: Object, required: true },
});

const data = ref(props.snapshot);
const lastUpdated = ref(props.snapshot.captured_at || '');
const isLoading = ref(false);
const refreshError = ref('');
const activeTab = ref('overview');
const expandedInterface = ref(null);
let autoRefresh = null;

const interfaces = computed(() => Object.values(data.value?.interfaces || {}));
const primaryInterface = computed(() => interfaces.value[0] || {});
const bandwidthHistory = computed(() => data.value?.bandwidth_history || []);
const topTalkers = computed(() => data.value?.top_talkers || []);
const streamHealth = computed(() => data.value?.stream_health || []);
const systemNet = computed(() => data.value?.system_net || {});
const apiUrl = computed(() => props.endpoints?.api || '/admin/network-monitor/api');
const liveStreamCount = computed(() => streamHealth.value.filter((stream) => stream.status === 'live').length);

const iconComponents = {
    Activity: markRaw(Activity),
    ArrowDownToLine: markRaw(ArrowDownToLine),
    ArrowUpFromLine: markRaw(ArrowUpFromLine),
    Globe2: markRaw(Globe2),
    Link2: markRaw(Link2),
    Server: markRaw(Server),
    Video: markRaw(Video),
};

const tabs = [
    { key: 'overview', label: 'Overview', icon: iconComponents.Activity, tone: 'cyan' },
    { key: 'interfaces', label: 'Interfaces', icon: iconComponents.Server, tone: 'emerald' },
    { key: 'streams', label: 'CCTV Streams', icon: iconComponents.Video, tone: 'violet' },
    { key: 'connections', label: 'Connections', icon: iconComponents.Globe2, tone: 'amber' },
];

const toneClasses = {
    emerald: {
        card: 'border-emerald-200 bg-white text-emerald-700 shadow-slate-100 hover:border-emerald-400 dark:border-emerald-400/25 dark:bg-zinc-950/70 dark:text-emerald-200 dark:shadow-none',
        icon: 'bg-emerald-100 text-emerald-600 dark:bg-emerald-400/15 dark:text-emerald-300',
        soft: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-200',
        dot: 'bg-emerald-500',
    },
    rose: {
        card: 'border-rose-200 bg-white text-rose-700 shadow-slate-100 hover:border-rose-400 dark:border-rose-400/25 dark:bg-zinc-950/70 dark:text-rose-200 dark:shadow-none',
        icon: 'bg-rose-100 text-rose-600 dark:bg-rose-400/15 dark:text-rose-300',
        soft: 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-400/25 dark:bg-rose-400/10 dark:text-rose-200',
        dot: 'bg-rose-500',
    },
    violet: {
        card: 'border-violet-200 bg-white text-violet-700 shadow-slate-100 hover:border-violet-400 dark:border-violet-400/25 dark:bg-zinc-950/70 dark:text-violet-200 dark:shadow-none',
        icon: 'bg-violet-100 text-violet-600 dark:bg-violet-400/15 dark:text-violet-300',
        soft: 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-400/25 dark:bg-violet-400/10 dark:text-violet-200',
        dot: 'bg-violet-500',
    },
    amber: {
        card: 'border-amber-200 bg-white text-amber-700 shadow-slate-100 hover:border-amber-400 dark:border-amber-400/25 dark:bg-zinc-950/70 dark:text-amber-200 dark:shadow-none',
        icon: 'bg-amber-100 text-amber-600 dark:bg-amber-400/15 dark:text-amber-300',
        soft: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-400/25 dark:bg-amber-400/10 dark:text-amber-200',
        dot: 'bg-amber-500',
    },
    cyan: {
        card: 'border-cyan-200 bg-white text-cyan-700 shadow-slate-100 hover:border-cyan-400 dark:border-cyan-400/25 dark:bg-zinc-950/70 dark:text-cyan-200 dark:shadow-none',
        icon: 'bg-cyan-100 text-cyan-600 dark:bg-cyan-400/15 dark:text-cyan-300',
        soft: 'border-cyan-200 bg-cyan-50 text-cyan-700 dark:border-cyan-400/25 dark:bg-cyan-400/10 dark:text-cyan-200',
        dot: 'bg-cyan-500',
    },
    slate: {
        card: 'border-slate-200 bg-white text-slate-700 shadow-slate-100 hover:border-slate-300 dark:border-white/10 dark:bg-zinc-950/70 dark:text-slate-200 dark:shadow-none',
        icon: 'bg-slate-100 text-slate-600 dark:bg-white/10 dark:text-slate-300',
        soft: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-slate-200',
        dot: 'bg-slate-400',
    },
};

const metricCards = computed(() => [
    {
        label: 'Download Rate',
        value: stripRate(primaryInterface.value?.rx_human),
        suffix: 'B/s',
        tone: 'emerald',
        icon: iconComponents.ArrowDownToLine,
        progress: rateWidth(primaryInterface.value?.rx_rate),
    },
    {
        label: 'Upload Rate',
        value: stripRate(primaryInterface.value?.tx_human),
        suffix: 'B/s',
        tone: 'rose',
        icon: iconComponents.ArrowUpFromLine,
        progress: rateWidth(primaryInterface.value?.tx_rate),
    },
    {
        label: 'Live CCTV',
        value: liveStreamCount.value,
        suffix: `/ ${streamHealth.value.length}`,
        tone: 'violet',
        icon: iconComponents.Video,
        progress: streamHealth.value.length ? (liveStreamCount.value / streamHealth.value.length) * 100 : 0,
    },
    {
        label: 'Connections',
        value: systemNet.value.established || 0,
        suffix: 'EST',
        tone: 'amber',
        icon: iconComponents.Link2,
        progress: Math.min(100, (systemNet.value.established || 0) * 3),
    },
]);

const chartPoints = computed(() => {
    const history = bandwidthHistory.value;
    if (!history.length) return { rx: '', tx: '' };

    const width = 100;
    const height = 40;
    const maxValue = Math.max(...history.map((item) => Math.max(item.rx || 1, item.tx || 1)), 1);

    const buildLine = (key) => history.map((item, index) => {
        const x = (index / (history.length - 1 || 1)) * width;
        const y = height - ((item[key] || 0) / maxValue) * height;
        return `${x},${y}`;
    }).join(' ');

    return {
        rx: buildLine('rx'),
        tx: buildLine('tx'),
    };
});

async function refreshData() {
    isLoading.value = true;
    refreshError.value = '';

    try {
        const response = await fetch(apiUrl.value, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const json = await response.json();
        data.value = json;
        lastUpdated.value = json.captured_at || '';
    } catch (error) {
        console.error('Failed to refresh network monitor:', error);
        refreshError.value = 'Gagal memperbarui data jaringan. Coba refresh manual atau cek sesi login.';
    } finally {
        isLoading.value = false;
    }
}

function formatBytes(bytes = 0) {
    if (bytes >= 1073741824) return `${(bytes / 1073741824).toFixed(2)} GB`;
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    if (bytes >= 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${bytes || 0} B`;
}

function stripRate(rate) {
    return (rate || '0 B/s').replace('B/s', '').trim();
}

function rateWidth(rate = 0) {
    return `${Math.min(100, (Number(rate) || 0) / 1000000)}%`;
}

function statusClass(status) {
    return status === 'active' ? 'bg-emerald-500' : 'bg-slate-400 dark:bg-slate-500';
}

function interfaceCardClass(iface) {
    if (expandedInterface.value === iface.name) {
        return 'border-cyan-300 bg-cyan-50/80 shadow-cyan-100 dark:border-cyan-400/35 dark:bg-cyan-400/10 dark:shadow-none';
    }

    return 'border-slate-200 bg-white/75 shadow-slate-100/80 hover:border-cyan-300 hover:bg-cyan-50/50 dark:border-white/10 dark:bg-white/5 dark:shadow-none dark:hover:border-cyan-400/25 dark:hover:bg-cyan-400/10';
}

function streamStatusClass(status) {
    if (status === 'live') {
        return 'border-violet-300 bg-violet-50 text-violet-700 dark:border-violet-400/30 dark:bg-violet-400/10 dark:text-violet-200';
    }

    if (status === 'configured') {
        return 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-200';
    }

    return 'border-slate-200 bg-slate-50 text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-300';
}

function tabClass(tab) {
    if (activeTab.value === tab.key) {
        return `${toneClasses[tab.tone].soft} shadow-sm`;
    }

    return 'border-transparent text-slate-500 hover:border-slate-200 hover:bg-white/70 hover:text-slate-700 dark:text-slate-400 dark:hover:border-white/10 dark:hover:bg-white/5 dark:hover:text-slate-100';
}

function talkerStates(talker) {
    return Object.entries(talker?.state || {}).slice(0, 2);
}

function stateTone(state) {
    if (state === 'ESTABLISHED') return toneClasses.emerald.soft;
    if (state === 'LISTEN') return toneClasses.cyan.soft;
    if (state === 'TIME_WAIT') return toneClasses.amber.soft;
    return toneClasses.slate.soft;
}

onMounted(() => {
    refreshData();
    autoRefresh = setInterval(refreshData, 3000);
});

onUnmounted(() => {
    if (autoRefresh) clearInterval(autoRefresh);
});
</script>

<template>
    <Head title="Monitor Jaringan" />

    <LawangsewuLayout
        current-route="admin-network-monitor"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-5 text-slate-800 dark:text-slate-100">
            <section class="overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm shadow-slate-100 backdrop-blur-xl dark:border-white/10 dark:bg-zinc-950/80 dark:shadow-none">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                    <div class="space-y-3">
                        <div class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-[11px] font-black uppercase tracking-[0.16em] text-emerald-700 shadow-sm dark:border-emerald-400/25 dark:bg-emerald-400/10 dark:text-emerald-200">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-500 opacity-60"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Live Network Monitor
                        </div>

                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-slate-950 dark:text-white">Monitor Jaringan</h1>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Pantau bandwidth, interface server, health stream CCTV, dan koneksi aktif dari satu panel ringan.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-2 text-left shadow-sm dark:border-white/10 dark:bg-white/5 sm:text-right">
                            <p class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-300">Last Update</p>
                            <p class="font-mono text-sm font-black text-slate-900 dark:text-white">{{ lastUpdated || '-' }}</p>
                        </div>

                        <button
                            type="button"
                            @click="refreshData"
                            :disabled="isLoading"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-300 bg-white px-4 py-3 text-sm font-bold text-emerald-700 shadow-sm transition hover:border-emerald-400 hover:bg-emerald-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-200 dark:hover:bg-emerald-400/15"
                        >
                            <RefreshCcw :class="['h-4 w-4', isLoading ? 'animate-spin' : '']" />
                            Refresh
                        </button>
                    </div>
                </div>
            </section>

            <div
                v-if="refreshError"
                class="rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:border-rose-400/30 dark:bg-rose-400/10 dark:text-rose-200"
            >
                {{ refreshError }}
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in metricCards"
                    :key="card.label"
                    :class="[
                        'group rounded-lg border p-5 shadow-sm backdrop-blur-xl transition duration-200 hover:-translate-y-0.5 hover:shadow-md dark:hover:shadow-none',
                        toneClasses[card.tone].card,
                    ]"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-black uppercase tracking-[0.18em] opacity-75">{{ card.label }}</p>
                            <div class="mt-3 flex items-baseline gap-2">
                                <span class="text-3xl font-black tracking-tight text-slate-950 dark:text-white">{{ card.value }}</span>
                                <span class="text-xs font-black opacity-75">{{ card.suffix }}</span>
                            </div>
                        </div>

                        <div :class="['flex h-11 w-11 items-center justify-center rounded-lg transition group-hover:scale-105', toneClasses[card.tone].icon]">
                            <component :is="card.icon" class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-4 h-1.5 overflow-hidden rounded-full bg-white/80 ring-1 ring-black/5 dark:bg-white/10 dark:ring-white/10">
                        <div
                            :class="['h-full rounded-full transition-all duration-500', toneClasses[card.tone].dot]"
                            :style="{ width: typeof card.progress === 'number' ? `${Math.min(100, card.progress)}%` : card.progress }"
                        ></div>
                    </div>
                </article>
            </section>

            <nav class="grid gap-2 rounded-lg border border-slate-200 bg-white/75 p-1.5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5 sm:grid-cols-2 xl:grid-cols-4">
                <button
                    v-for="tab in tabs"
                    :key="tab.key"
                    type="button"
                    @click="activeTab = tab.key"
                    :class="[
                        'inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border px-3 py-2 text-[12px] font-black uppercase tracking-[0.1em] transition',
                        tabClass(tab),
                    ]"
                >
                    <component :is="tab.icon" class="h-4 w-4" />
                    {{ tab.label }}
                </button>
            </nav>

            <section v-if="activeTab === 'overview'" class="grid gap-5 xl:grid-cols-[2fr_1fr]">
                <article class="rounded-lg border border-slate-200 bg-white/75 p-5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="inline-flex items-center gap-2 text-sm font-black uppercase tracking-[0.14em] text-slate-900 dark:text-white">
                            <Activity class="h-4 w-4 text-cyan-600 dark:text-cyan-300" />
                            Bandwidth Utilization
                        </h2>

                        <div class="flex items-center gap-4 text-[11px] font-bold">
                            <span class="inline-flex items-center gap-1.5 text-emerald-700 dark:text-emerald-300">
                                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                RX
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-rose-700 dark:text-rose-300">
                                <span class="h-2 w-2 rounded-full bg-rose-500"></span>
                                TX
                            </span>
                        </div>
                    </div>

                    <div class="relative h-52 overflow-hidden rounded-lg border border-slate-200 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-slate-950/60">
                        <svg viewBox="0 0 100 40" class="h-full w-full" preserveAspectRatio="none">
                            <g class="text-slate-300 dark:text-slate-700" stroke="currentColor" stroke-width="0.12">
                                <line x1="0" y1="10" x2="100" y2="10" />
                                <line x1="0" y1="20" x2="100" y2="20" />
                                <line x1="0" y1="30" x2="100" y2="30" />
                            </g>
                            <polyline
                                :points="chartPoints.rx"
                                fill="none"
                                stroke="#10b981"
                                stroke-width="0.75"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <polyline
                                :points="chartPoints.tx"
                                fill="none"
                                stroke="#f43f5e"
                                stroke-width="0.75"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>

                        <div v-if="!bandwidthHistory.length" class="absolute inset-0 grid place-items-center text-sm font-semibold text-slate-500 dark:text-slate-400">
                            Menunggu data bandwidth...
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-2">
                        <div :class="['rounded-lg border p-3', toneClasses.emerald.soft]">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] opacity-75">Download Sekarang</p>
                            <p class="mt-1 text-lg font-black text-slate-950 dark:text-white">{{ primaryInterface.rx_human || '0 B/s' }}</p>
                        </div>
                        <div :class="['rounded-lg border p-3', toneClasses.rose.soft]">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] opacity-75">Upload Sekarang</p>
                            <p class="mt-1 text-lg font-black text-slate-950 dark:text-white">{{ primaryInterface.tx_human || '0 B/s' }}</p>
                        </div>
                    </div>
                </article>

                <div class="space-y-4">
                    <article class="rounded-lg border border-slate-200 bg-white/75 p-5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
                        <h3 class="mb-4 inline-flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-300">
                            <Router class="h-4 w-4 text-emerald-600 dark:text-emerald-300" />
                            Interfaces
                        </h3>

                        <div class="space-y-2">
                            <div
                                v-for="iface in interfaces.slice(0, 4)"
                                :key="iface.name"
                                class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white/70 px-3 py-2 dark:border-white/10 dark:bg-white/5"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <span :class="['h-2 w-2 shrink-0 rounded-full', statusClass(iface.status)]"></span>
                                    <span class="truncate font-mono text-[12px] font-bold text-slate-800 dark:text-slate-100">{{ iface.name }}</span>
                                </div>
                                <span class="shrink-0 text-[11px] font-black text-emerald-700 dark:text-emerald-300">{{ iface.rx_human }}</span>
                            </div>

                            <p v-if="interfaces.length === 0" class="py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                Interface belum terdeteksi.
                            </p>
                        </div>
                    </article>

                    <article class="rounded-lg border border-slate-200 bg-white/75 p-5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
                        <h3 class="mb-4 inline-flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-300">
                            <Video class="h-4 w-4 text-violet-600 dark:text-violet-300" />
                            CCTV Streams
                        </h3>

                        <div class="space-y-2">
                            <div
                                v-for="stream in streamHealth.slice(0, 3)"
                                :key="stream.name"
                                class="flex items-center justify-between gap-3 rounded-lg border border-slate-200 bg-white/70 px-3 py-2 dark:border-white/10 dark:bg-white/5"
                            >
                                <span class="truncate text-[12px] font-bold text-slate-800 dark:text-slate-100">{{ stream.name }}</span>
                                <span :class="['shrink-0 rounded-full border px-2 py-1 text-[10px] font-black uppercase tracking-[0.12em]', streamStatusClass(stream.status)]">
                                    {{ stream.status }}
                                </span>
                            </div>

                            <p v-if="streamHealth.length === 0" class="py-4 text-center text-sm text-slate-500 dark:text-slate-400">
                                Stream belum terdeteksi.
                            </p>
                        </div>
                    </article>
                </div>
            </section>

            <section v-if="activeTab === 'interfaces'" class="grid gap-4 md:grid-cols-2">
                <article
                    v-for="iface in interfaces"
                    :key="iface.name"
                    @click="expandedInterface = expandedInterface === iface.name ? null : iface.name"
                    :class="[
                        'cursor-pointer rounded-lg border p-5 shadow-sm backdrop-blur-xl transition hover:-translate-y-0.5 hover:shadow-md dark:hover:shadow-none',
                        interfaceCardClass(iface),
                    ]"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div :class="['flex h-11 w-11 shrink-0 items-center justify-center rounded-lg', iface.status === 'active' ? toneClasses.emerald.icon : toneClasses.slate.icon]">
                                <Wifi class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-slate-950 dark:text-white">{{ iface.name }}</p>
                                <div class="mt-1 flex items-center gap-2">
                                    <span :class="['h-1.5 w-1.5 rounded-full', statusClass(iface.status)]"></span>
                                    <span :class="['text-[10px] font-black uppercase tracking-[0.12em]', iface.status === 'active' ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-500 dark:text-slate-400']">
                                        {{ iface.status }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="grid shrink-0 grid-cols-2 gap-3 text-right text-[12px]">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.12em] text-emerald-700 dark:text-emerald-300">RX</p>
                                <p class="font-black text-slate-950 dark:text-white">{{ iface.rx_human }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.12em] text-rose-700 dark:text-rose-300">TX</p>
                                <p class="font-black text-slate-950 dark:text-white">{{ iface.tx_human }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="expandedInterface === iface.name" class="mt-4 grid gap-3 border-t border-cyan-200 pt-4 dark:border-cyan-400/20 sm:grid-cols-3">
                        <div class="rounded-lg border border-emerald-200 bg-emerald-50/80 p-3 text-center dark:border-emerald-400/20 dark:bg-emerald-400/10">
                            <p class="text-[10px] font-black uppercase tracking-[0.12em] text-emerald-700 dark:text-emerald-300">Total RX</p>
                            <p class="mt-1 text-sm font-black text-slate-950 dark:text-white">{{ formatBytes(iface.rx_bytes) }}</p>
                        </div>
                        <div class="rounded-lg border border-rose-200 bg-rose-50/80 p-3 text-center dark:border-rose-400/20 dark:bg-rose-400/10">
                            <p class="text-[10px] font-black uppercase tracking-[0.12em] text-rose-700 dark:text-rose-300">Total TX</p>
                            <p class="mt-1 text-sm font-black text-slate-950 dark:text-white">{{ formatBytes(iface.tx_bytes) }}</p>
                        </div>
                        <div class="rounded-lg border border-cyan-200 bg-cyan-50/80 p-3 text-center dark:border-cyan-400/20 dark:bg-cyan-400/10">
                            <p class="text-[10px] font-black uppercase tracking-[0.12em] text-cyan-700 dark:text-cyan-300">Total</p>
                            <p class="mt-1 text-sm font-black text-slate-950 dark:text-white">{{ formatBytes(iface.total_bytes) }}</p>
                        </div>
                    </div>
                </article>

                <div v-if="interfaces.length === 0" class="rounded-lg border border-slate-200 bg-white/75 p-10 text-center shadow-sm dark:border-white/10 dark:bg-white/5 md:col-span-2">
                    <Signal class="mx-auto h-10 w-10 text-slate-400" />
                    <p class="mt-3 font-semibold text-slate-500 dark:text-slate-400">No network interfaces detected.</p>
                </div>
            </section>

            <section v-if="activeTab === 'streams'" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="stream in streamHealth"
                    :key="stream.name"
                    class="rounded-lg border border-violet-200 bg-white/75 p-5 shadow-sm backdrop-blur-xl transition hover:-translate-y-0.5 hover:border-violet-300 hover:bg-violet-50/40 hover:shadow-md dark:border-violet-400/20 dark:bg-white/5 dark:hover:bg-violet-400/10 dark:hover:shadow-none"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div :class="['relative flex h-11 w-11 shrink-0 items-center justify-center rounded-lg', stream.status === 'live' ? toneClasses.violet.icon : toneClasses.slate.icon]">
                                <Video class="h-5 w-5" />
                                <span v-if="stream.status === 'live'" class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-violet-500 ring-2 ring-white dark:ring-slate-900"></span>
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-slate-950 dark:text-white">{{ stream.name }}</p>
                                <p class="mt-0.5 truncate text-[11px] font-semibold text-slate-500 dark:text-slate-400">{{ stream.codec || 'auto' }}</p>
                            </div>
                        </div>

                        <span :class="['shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.12em]', streamStatusClass(stream.status)]">
                            {{ stream.status }}
                        </span>
                    </div>

                    <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50/80 px-3 py-2 dark:border-white/10 dark:bg-slate-950/50">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-500 dark:text-slate-400">Source</p>
                        <p class="mt-1 truncate font-mono text-[12px] font-bold text-cyan-700 dark:text-cyan-300">{{ stream.source || 'N/A' }}</p>
                    </div>
                </article>

                <div v-if="streamHealth.length === 0" class="rounded-lg border border-slate-200 bg-white/75 p-10 text-center shadow-sm dark:border-white/10 dark:bg-white/5 md:col-span-2 xl:col-span-3">
                    <Video class="mx-auto h-10 w-10 text-slate-400" />
                    <p class="mt-3 font-semibold text-slate-500 dark:text-slate-400">No CCTV streams detected. Ensure go2rtc or MediaMTX is running.</p>
                </div>
            </section>

            <section v-if="activeTab === 'connections'" class="space-y-5">
                <div class="grid gap-4 md:grid-cols-4">
                    <div :class="['rounded-lg border p-4 text-center shadow-sm', toneClasses.emerald.card]">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] opacity-75">Established</p>
                        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ systemNet.established || 0 }}</p>
                    </div>
                    <div :class="['rounded-lg border p-4 text-center shadow-sm', toneClasses.amber.card]">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] opacity-75">Time Wait</p>
                        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ systemNet.time_wait || 0 }}</p>
                    </div>
                    <div :class="['rounded-lg border p-4 text-center shadow-sm', toneClasses.cyan.card]">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] opacity-75">Listening</p>
                        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ systemNet.listen || 0 }}</p>
                    </div>
                    <div :class="['rounded-lg border p-4 text-center shadow-sm', toneClasses.slate.card]">
                        <p class="text-[10px] font-black uppercase tracking-[0.14em] opacity-75">Total</p>
                        <p class="mt-2 text-3xl font-black text-slate-950 dark:text-white">{{ systemNet.total_connections || 0 }}</p>
                    </div>
                </div>

                <article class="rounded-lg border border-slate-200 bg-white/75 p-5 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
                    <h3 class="mb-4 inline-flex items-center gap-2 text-[11px] font-black uppercase tracking-[0.16em] text-slate-500 dark:text-slate-300">
                        <CircleDot class="h-4 w-4 text-amber-600 dark:text-amber-300" />
                        Top Connection Sources
                    </h3>

                    <div class="space-y-3">
                        <div
                            v-for="(talker, index) in topTalkers.slice(0, 8)"
                            :key="index"
                            class="flex items-center justify-between gap-4 rounded-lg border border-slate-200 bg-white/70 px-4 py-3 transition hover:border-amber-300 hover:bg-amber-50/40 dark:border-white/10 dark:bg-white/5 dark:hover:border-amber-400/25 dark:hover:bg-amber-400/10"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-slate-50 text-[11px] font-black text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-300">
                                    {{ index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate font-mono text-[12px] font-black text-slate-900 dark:text-white">{{ talker.ip || 'Local' }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        <span
                                            v-for="[state, count] in talkerStates(talker)"
                                            :key="state"
                                            :class="['rounded border px-1.5 py-0.5 text-[9px] font-black uppercase tracking-[0.08em]', stateTone(state)]"
                                        >
                                            {{ state }} {{ count }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <span class="shrink-0 text-sm font-black text-slate-950 dark:text-white">{{ talker.connections }}</span>
                        </div>

                        <div v-if="topTalkers.length === 0" class="rounded-lg border border-slate-200 bg-slate-50/70 py-8 text-center font-semibold text-slate-500 dark:border-white/10 dark:bg-white/5 dark:text-slate-400">
                            No external connections detected.
                        </div>
                    </div>
                </article>
            </section>
        </div>
    </LawangsewuLayout>
</template>
