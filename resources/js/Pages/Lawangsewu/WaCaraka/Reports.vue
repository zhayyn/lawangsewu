<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    appMeta: { type: Object, default: () => ({}) },
    navGroups: { type: Array, default: () => [] },
    authUser: { type: Object, default: () => ({}) },
    reportStats: { type: Object, default: () => ({}) },
});

const loading = ref(false);
const datasetKey = ref('daily');
const stats = ref(props.reportStats || {});

const summary = computed(() => stats.value?.summary || {});
const operatorStats = computed(() => stats.value?.operatorStats || []);
const latestHistorySync = computed(() => stats.value?.historySync?.latest || null);
const topContacts = computed(() => stats.value?.topContacts || []);
const messageTypes = computed(() => stats.value?.messageTypes || []);
const executiveSummary = computed(() => stats.value?.executiveSummary || {});
const snapshotTimeline = computed(() => stats.value?.snapshots?.timeline || []);
const latestSnapshot = computed(() => stats.value?.snapshots?.latest || null);

const chartData = computed(() => {
    if (datasetKey.value === 'weekly') return stats.value?.weeklyInbound || [];
    if (datasetKey.value === 'monthly') return stats.value?.monthlyInbound || [];
    return stats.value?.dailyInbound || [];
});

const chartTone = computed(() => {
    if (datasetKey.value === 'weekly') {
        return {
            accent: 'from-emerald-300 via-teal-300 to-cyan-300',
            edge: 'bg-emerald-300/70',
            floor: 'from-emerald-500/12 via-teal-400/6 to-transparent',
            halo: 'shadow-[0_24px_55px_rgba(16,185,129,0.28)]',
        };
    }

    if (datasetKey.value === 'monthly') {
        return {
            accent: 'from-fuchsia-300 via-violet-300 to-indigo-300',
            edge: 'bg-violet-300/70',
            floor: 'from-fuchsia-500/12 via-violet-400/6 to-transparent',
            halo: 'shadow-[0_24px_55px_rgba(139,92,246,0.28)]',
        };
    }

    return {
        accent: 'from-cyan-300 via-sky-300 to-indigo-300',
        edge: 'bg-cyan-300/70',
        floor: 'from-cyan-500/12 via-sky-400/6 to-transparent',
        halo: 'shadow-[0_24px_55px_rgba(34,211,238,0.28)]',
    };
});

const maxCount = computed(() => Math.max(1, ...chartData.value.map((d) => Number(d.count || 0))));
const chartTotal = computed(() => chartData.value.reduce((sum, point) => sum + Number(point.count || 0), 0));
const chartAverage = computed(() => {
    if (!chartData.value.length) return 0;
    return Math.round(chartTotal.value / chartData.value.length);
});
const chartLeader = computed(() => {
    return chartData.value.reduce((best, point) => {
        const count = Number(point.count || 0);
        if (!best || count > Number(best.count || 0)) return { label: point.label, count };
        return best;
    }, null);
});

const chartColumns = computed(() => {
    return chartData.value.map((point) => {
        const count = Number(point.count || 0);
        const ratio = count / maxCount.value;
        return {
            ...point,
            count,
            ratio,
            height: `${Math.max(12, Math.round(ratio * 168))}px`,
            glowOpacity: Math.max(0.24, ratio),
        };
    });
});

const syncReceived = computed(() => Number(latestHistorySync.value?.messagesReceived || 0));
const syncImported = computed(() => Number(latestHistorySync.value?.messagesImported || 0));
const syncDuplicate = computed(() => Number(latestHistorySync.value?.messagesDuplicate || 0));
const syncEfficiency = computed(() => {
    const total = syncReceived.value || (syncImported.value + syncDuplicate.value);
    if (!total) return 0;
    return Math.min(100, Math.round((syncImported.value / total) * 100));
});

const heroMetrics = computed(() => [
    {
        label: 'Inbound Hari Ini',
        value: summary.value.inboundToday || 0,
        note: 'Pesan baru yang masuk hari ini',
        chip: 'bg-cyan-300/15 text-cyan-100 ring-cyan-300/30',
        card: 'from-cyan-400/20 via-cyan-200/8 to-transparent',
        glow: 'shadow-[0_30px_70px_rgba(34,211,238,0.22)]',
    },
    {
        label: 'Inbound Minggu Ini',
        value: summary.value.inboundWeek || 0,
        note: 'Akumulasi 7 hari terakhir',
        chip: 'bg-emerald-300/15 text-emerald-100 ring-emerald-300/30',
        card: 'from-emerald-400/22 via-emerald-200/8 to-transparent',
        glow: 'shadow-[0_30px_70px_rgba(16,185,129,0.22)]',
    },
    {
        label: 'Inbound Bulan Ini',
        value: summary.value.inboundMonth || 0,
        note: 'Modal laporan bulanan',
        chip: 'bg-violet-300/15 text-violet-100 ring-violet-300/30',
        card: 'from-violet-400/22 via-fuchsia-200/8 to-transparent',
        glow: 'shadow-[0_30px_70px_rgba(139,92,246,0.22)]',
    },
    {
        label: 'Percakapan Aktif',
        value: summary.value.activeConversations || 0,
        note: 'Thread yang sedang hidup',
        chip: 'bg-amber-300/15 text-amber-100 ring-amber-300/30',
        card: 'from-amber-300/24 via-orange-200/10 to-transparent',
        glow: 'shadow-[0_30px_70px_rgba(245,158,11,0.22)]',
    },
]);

const leaderboard = computed(() => {
    return [...operatorStats.value]
        .map((row) => ({
            ...row,
            conversations: Number(row.conversations || 0),
            outboundMessages: Number(row.outboundMessages || 0),
        }))
        .sort((a, b) => {
            if (b.outboundMessages !== a.outboundMessages) return b.outboundMessages - a.outboundMessages;
            return b.conversations - a.conversations;
        });
});

const topOperator = computed(() => leaderboard.value[0] || null);
const secondOperator = computed(() => leaderboard.value[1] || null);
const topMessageType = computed(() => messageTypes.value[0] || null);
const pdfUrl = computed(() => {
    try {
        return typeof route === 'function' ? route('lawangsewu.wacaraka.reports.pdf') : '/wa-caraka/reports/pdf';
    } catch {
        return '/wa-caraka/reports/pdf';
    }
});

const updatedAtText = computed(() => {
    if (!stats.value?.generatedAt) return '-';
    return new Date(stats.value.generatedAt).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const syncUpdatedAtText = computed(() => {
    if (!latestHistorySync.value?.updatedAt) return '-';
    return new Date(latestHistorySync.value.updatedAt).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
});

const refreshStats = async () => {
    if (loading.value) return;
    loading.value = true;
    try {
        const res = await fetch(route('lawangsewu.wacaraka.reports.data'), {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const json = await res.json();
        if (res.ok) stats.value = json;
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Laporan WA Caraka" />

    <LawangsewuLayout current-route="wacaraka" :nav-groups="navGroups" :app-meta="appMeta">
        <section class="report-shell relative overflow-hidden rounded-[2rem] border border-slate-800/80 bg-[linear-gradient(145deg,rgba(2,6,23,0.98),rgba(15,23,42,0.96),rgba(17,24,39,0.98))] px-5 py-6 text-white shadow-[0_36px_120px_rgba(2,6,23,0.45)] sm:px-6">
            <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(34,211,238,0.18),transparent_34%),radial-gradient(circle_at_top_right,rgba(168,85,247,0.14),transparent_30%),linear-gradient(transparent,rgba(15,23,42,0.15))]" />
            <div class="pointer-events-none absolute inset-x-10 top-0 h-28 rounded-b-[999px] bg-white/6 blur-3xl" />

            <div class="relative flex flex-wrap items-start justify-between gap-4">
                <div class="max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1 text-[11px] font-black uppercase tracking-[0.3em] text-cyan-100">
                        <span class="h-2 w-2 rounded-full bg-cyan-300 shadow-[0_0_14px_rgba(34,211,238,0.85)]" />
                        WA Caraka Admin Command Deck
                    </div>
                    <h1 class="mt-4 text-2xl font-black tracking-tight text-white sm:text-[2rem]">
                        Statistik 3D untuk membaca ritme inbox, stabilitas sync, dan tenaga operator dalam satu layar.
                    </h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300">
                        Halaman ini khusus admin dan superadmin. Operator tetap bersih dan ringan, sementara Anda mendapatkan panel kontrol visual untuk laporan bulanan dan audit performa.
                    </p>

                    <div class="mt-5 flex flex-wrap gap-3 text-xs font-semibold text-slate-200">
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 backdrop-blur">
                            Update terakhir <span class="ml-1 font-black text-white">{{ updatedAtText }}</span>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 backdrop-blur">
                            Sync health <span class="ml-1 font-black text-emerald-300">{{ syncEfficiency }}%</span>
                        </div>
                        <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-2 backdrop-blur">
                            Puncak data <span class="ml-1 font-black text-cyan-200">{{ chartLeader?.label || '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-2">
                    <button
                        @click="window.history.back()"
                        class="rounded-2xl border border-white/15 bg-white/5 px-4 py-2 text-xs font-bold text-slate-100 transition hover:bg-white/10"
                    >
                        Kembali
                    </button>
                    <button
                        @click="refreshStats"
                        :disabled="loading"
                        class="rounded-2xl border border-cyan-300/30 bg-cyan-300/10 px-4 py-2 text-xs font-bold text-cyan-100 transition hover:bg-cyan-300/20 disabled:opacity-40"
                    >
                        {{ loading ? 'Memuat...' : 'Refresh Data' }}
                    </button>
                    <a
                        :href="pdfUrl"
                        target="_blank"
                        rel="noopener"
                        class="rounded-2xl border border-fuchsia-300/30 bg-fuchsia-300/10 px-4 py-2 text-xs font-bold text-fuchsia-100 transition hover:bg-fuchsia-300/20"
                    >
                        Export PDF
                    </a>
                </div>
            </div>

            <div class="relative mt-6 grid gap-4 lg:grid-cols-4">
                <article
                    v-for="metric in heroMetrics"
                    :key="metric.label"
                    class="group relative overflow-hidden rounded-[1.7rem] border border-white/10 bg-[rgba(15,23,42,0.76)] p-4 transition duration-300 hover:-translate-y-1"
                    :class="metric.glow"
                >
                    <div class="absolute inset-0 bg-gradient-to-br opacity-100" :class="metric.card" />
                    <div class="absolute inset-x-5 top-0 h-10 rounded-b-full bg-white/10 blur-2xl" />
                    <div class="absolute inset-x-4 bottom-[-18px] h-8 rounded-[999px] bg-black/45 blur-xl" />

                    <div class="relative">
                        <span class="inline-flex rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-[0.22em] ring-1" :class="metric.chip">
                            {{ metric.label }}
                        </span>
                        <div class="mt-5 flex items-end justify-between gap-3">
                            <div>
                                <p class="text-4xl font-black tracking-tight text-white">{{ metric.value }}</p>
                                <p class="mt-2 text-xs leading-5 text-slate-300">{{ metric.note }}</p>
                            </div>
                            <div class="metric-pillar">
                                <div class="metric-pillar__top" />
                                <div class="metric-pillar__side" />
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.65fr,1fr]">
            <article class="report-panel overflow-hidden p-5 sm:p-6 xl:col-span-2">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-sky-700">Executive Snapshot</p>
                        <h2 class="mt-2 text-lg font-black text-slate-900">Ringkasan eksekutif bulan {{ executiveSummary.periodLabel || '-' }}</h2>
                        <p class="mt-1 text-sm text-slate-500">Panel ini seperti ringkasan rapat: siapa paling aktif, kontak paling sibuk, dan jenis pesan paling dominan langsung terlihat.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-4">
                    <div class="glass-card">
                        <p class="glass-card__label">Operator utama</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ executiveSummary.topOperator?.name || topOperator?.name || '-' }}</p>
                        <p class="mt-2 text-xs text-slate-500">Outbound {{ executiveSummary.topOperator?.outboundMessages || topOperator?.outboundMessages || 0 }}</p>
                    </div>
                    <div class="glass-card">
                        <p class="glass-card__label">Kontak tersibuk</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ executiveSummary.topContact?.name || topContacts[0]?.name || '-' }}</p>
                        <p class="mt-2 text-xs text-slate-500">Pesan {{ executiveSummary.topContact?.messages || topContacts[0]?.messages || 0 }}</p>
                    </div>
                    <div class="glass-card">
                        <p class="glass-card__label">Jenis dominan</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ executiveSummary.topMessageType?.label || topMessageType?.label || '-' }}</p>
                        <p class="mt-2 text-xs text-slate-500">Volume {{ executiveSummary.topMessageType?.count || topMessageType?.count || 0 }}</p>
                    </div>
                    <div class="glass-card">
                        <p class="glass-card__label">Snapshot terbaru</p>
                        <p class="mt-2 text-lg font-black text-slate-950">{{ latestSnapshot?.label || '-' }}</p>
                        <p class="mt-2 text-xs text-slate-500">Kontak unik {{ latestSnapshot?.uniqueContacts || 0 }}</p>
                    </div>
                </div>
            </article>

            <article class="report-panel overflow-hidden p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-cyan-700">3D Inbound Skyline</p>
                        <h2 class="mt-2 text-lg font-black text-slate-900">Grafik volumetrik pesan masuk</h2>
                        <p class="mt-1 text-sm text-slate-500">Kolom dibuat seperti bangunan kota agar tren naik-turun cepat terbaca saat Anda presentasi bulanan.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            @click="datasetKey = 'daily'"
                            class="rounded-2xl px-3 py-1.5 text-xs font-bold transition"
                            :class="datasetKey === 'daily' ? 'bg-cyan-600 text-white shadow-[0_16px_30px_rgba(8,145,178,0.28)]' : 'border border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                        >
                            Harian
                        </button>
                        <button
                            @click="datasetKey = 'weekly'"
                            class="rounded-2xl px-3 py-1.5 text-xs font-bold transition"
                            :class="datasetKey === 'weekly' ? 'bg-emerald-600 text-white shadow-[0_16px_30px_rgba(5,150,105,0.28)]' : 'border border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                        >
                            Mingguan
                        </button>
                        <button
                            @click="datasetKey = 'monthly'"
                            class="rounded-2xl px-3 py-1.5 text-xs font-bold transition"
                            :class="datasetKey === 'monthly' ? 'bg-violet-600 text-white shadow-[0_16px_30px_rgba(109,40,217,0.28)]' : 'border border-slate-200 bg-white text-slate-500 hover:bg-slate-50'"
                        >
                            Bulanan
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-[1fr,270px]">
                    <div class="skyline-stage" :class="chartTone.halo">
                        <div class="skyline-stage__floor bg-gradient-to-r" :class="chartTone.floor" />
                        <div class="skyline-stage__grid" />

                        <div v-if="chartColumns.length" class="skyline-columns">
                            <div v-for="column in chartColumns" :key="column.label" class="skyline-column">
                                <div class="skyline-column__stack">
                                    <div class="skyline-column__glow" :style="{ opacity: column.glowOpacity }" />
                                    <div class="skyline-column__body bg-gradient-to-t" :class="chartTone.accent" :style="{ height: column.height }" />
                                    <div class="skyline-column__edge" :class="chartTone.edge" :style="{ height: column.height }" />
                                    <div class="skyline-column__top bg-white/70" />
                                </div>
                                <div class="mt-3 text-center">
                                    <p class="truncate text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">{{ column.label }}</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">{{ column.count }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="flex h-[260px] items-center justify-center text-sm font-semibold text-slate-400">
                            Belum ada data grafik.
                        </div>
                    </div>

                    <div class="grid gap-4">
                        <article class="rounded-[1.6rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#eef6ff)] p-4 shadow-[0_22px_50px_rgba(148,163,184,0.16)]">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Ringkasan Gelombang</p>
                            <div class="mt-4 grid gap-3">
                                <div class="rounded-2xl bg-slate-950 px-4 py-3 text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.08)]">
                                    <p class="text-[11px] uppercase tracking-[0.22em] text-slate-400">Total</p>
                                    <p class="mt-1 text-2xl font-black">{{ chartTotal }}</p>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Rata-rata</p>
                                        <p class="mt-1 text-xl font-black text-slate-900">{{ chartAverage }}</p>
                                    </div>
                                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Puncak</p>
                                        <p class="mt-1 text-xl font-black text-slate-900">{{ chartLeader?.count || 0 }}</p>
                                    </div>
                                </div>
                                <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-400">Periode terkuat</p>
                                    <p class="mt-1 text-sm font-black text-slate-900">{{ chartLeader?.label || 'Belum ada data' }}</p>
                                    <p class="mt-1 text-xs text-slate-500">Titik ini cocok dijadikan highlight saat membuat laporan bulanan ke pimpinan.</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </article>

            <article class="report-panel overflow-hidden p-5 sm:p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-violet-700">History Sync Engine</p>
                        <h2 class="mt-2 text-lg font-black text-slate-900">Stabilitas sinkron riwayat</h2>
                        <p class="mt-1 text-sm text-slate-500">Agar data bulanan tetap hidup walau perangkat putus, runtime, Laravel, dan database dibuat saling mengunci.</p>
                    </div>
                    <span
                        class="rounded-full border px-3 py-1 text-[11px] font-black uppercase tracking-[0.18em]"
                        :class="latestHistorySync?.status === 'completed' ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : latestHistorySync?.status === 'running' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-slate-50 text-slate-600'"
                    >
                        {{ latestHistorySync?.status || 'belum ada sync' }}
                    </span>
                </div>

                <div class="mt-5 grid gap-5 lg:grid-cols-[220px,1fr]">
                    <div class="sync-orb-wrap">
                        <div class="sync-orb" :style="{ '--sync-progress-deg': `${syncEfficiency * 3.6}deg` }">
                            <div class="sync-orb__inner">
                                <p class="text-[11px] font-black uppercase tracking-[0.22em] text-slate-400">Imported</p>
                                <p class="mt-1 text-4xl font-black text-slate-950">{{ syncEfficiency }}%</p>
                                <p class="mt-2 text-xs text-slate-500">Efisiensi import terhadap batch riwayat terbaru.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="sync-card">
                            <p class="sync-card__label">Pesan diterima</p>
                            <p class="sync-card__value text-sky-600">{{ syncReceived }}</p>
                        </div>
                        <div class="sync-card">
                            <p class="sync-card__label">Pesan diimpor</p>
                            <p class="sync-card__value text-emerald-600">{{ syncImported }}</p>
                        </div>
                        <div class="sync-card">
                            <p class="sync-card__label">Duplikat</p>
                            <p class="sync-card__value text-amber-600">{{ syncDuplicate }}</p>
                        </div>
                        <div class="sync-card">
                            <p class="sync-card__label">Progress runtime</p>
                            <p class="sync-card__value text-violet-600">{{ latestHistorySync?.progress ?? 0 }}%</p>
                        </div>
                        <div class="sync-card sm:col-span-2">
                            <p class="sync-card__label">Sinkron terakhir</p>
                            <p class="mt-1 text-sm font-black text-slate-900">{{ syncUpdatedAtText }}</p>
                            <p class="mt-2 text-xs leading-5 text-slate-500">
                                Batch riwayat lama disimpan ke database lokal, jadi putus koneksi atau ganti nomor tidak menghapus jejak laporan yang sudah terkumpul.
                            </p>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section class="mt-6 grid gap-6 xl:grid-cols-[1.2fr,1fr]">
            <article class="report-panel overflow-hidden p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-indigo-700">Snapshot Arsip</p>
                        <h2 class="mt-2 text-lg font-black text-slate-900">Foto bulanan yang tidak berubah-ubah</h2>
                        <p class="mt-1 text-sm text-slate-500">Ibarat kita memotret dashboard tiap akhir bulan. Foto itu tetap tersimpan walau device putus, nomor diganti, atau history sync berjalan ulang.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article v-for="snapshot in snapshotTimeline" :key="snapshot.monthKey" class="snapshot-tile">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">{{ snapshot.monthKey }}</p>
                                <h3 class="mt-1 text-lg font-black text-slate-950">{{ snapshot.label }}</h3>
                            </div>
                            <span class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-sky-700">
                                {{ snapshot.totalMessages }} pesan
                            </span>
                        </div>

                        <div class="mt-4 grid grid-cols-2 gap-3">
                            <div class="rounded-2xl bg-white/70 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Inbound</p>
                                <p class="mt-1 text-xl font-black text-sky-700">{{ snapshot.inboundMessages }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/70 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Outbound</p>
                                <p class="mt-1 text-xl font-black text-emerald-700">{{ snapshot.outboundMessages }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/70 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Kontak Unik</p>
                                <p class="mt-1 text-xl font-black text-violet-700">{{ snapshot.uniqueContacts }}</p>
                            </div>
                            <div class="rounded-2xl bg-white/70 px-4 py-3">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-slate-400">Top Operator</p>
                                <p class="mt-1 truncate text-sm font-black text-slate-900">{{ snapshot.topOperatorName || '-' }}</p>
                            </div>
                        </div>
                    </article>
                    <article v-if="snapshotTimeline.length === 0" class="snapshot-tile lg:col-span-2">
                        <p class="text-sm font-semibold text-slate-500">Belum ada snapshot bulanan.</p>
                    </article>
                </div>
            </article>

            <article class="report-panel overflow-hidden p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-black uppercase tracking-[0.24em] text-emerald-700">Operator Flight Deck</p>
                        <h2 class="mt-2 text-lg font-black text-slate-900">Papan tenaga operator</h2>
                        <p class="mt-1 text-sm text-slate-500">Siapa paling aktif membalas, siapa paling banyak menangani thread, semuanya terlihat cepat.</p>
                    </div>
                </div>

                <div class="mt-5 grid gap-4 lg:grid-cols-2">
                    <article class="rounded-[1.6rem] border border-slate-200 bg-[linear-gradient(180deg,#0f172a,#111827)] p-5 text-white shadow-[0_28px_60px_rgba(15,23,42,0.32)]">
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-emerald-300">Operator terdepan</p>
                        <template v-if="topOperator">
                            <h3 class="mt-3 text-2xl font-black">{{ topOperator.name }}</h3>
                            <div class="mt-4 grid grid-cols-2 gap-3">
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Percakapan</p>
                                    <p class="mt-1 text-2xl font-black text-cyan-200">{{ topOperator.conversations }}</p>
                                </div>
                                <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                                    <p class="text-[10px] uppercase tracking-[0.2em] text-slate-400">Outbound</p>
                                    <p class="mt-1 text-2xl font-black text-emerald-200">{{ topOperator.outboundMessages }}</p>
                                </div>
                            </div>
                        </template>
                        <p v-else class="mt-3 text-sm text-slate-300">Belum ada data operator.</p>
                    </article>

                    <article class="rounded-[1.6rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#f8fafc)] p-5 shadow-[0_24px_50px_rgba(148,163,184,0.18)]">
                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Bayangan berikutnya</p>
                        <template v-if="secondOperator">
                            <h3 class="mt-3 text-2xl font-black text-slate-900">{{ secondOperator.name }}</h3>
                            <div class="mt-4 space-y-3">
                                <div>
                                    <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                                        <span>Percakapan</span>
                                        <span>{{ secondOperator.conversations }}</span>
                                    </div>
                                    <div class="mt-2 h-3 rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 to-sky-500" :style="{ width: `${Math.min(100, topOperator ? (secondOperator.conversations / Math.max(1, topOperator.conversations)) * 100 : 0)}%` }" />
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                                        <span>Outbound</span>
                                        <span>{{ secondOperator.outboundMessages }}</span>
                                    </div>
                                    <div class="mt-2 h-3 rounded-full bg-slate-200">
                                        <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500" :style="{ width: `${Math.min(100, topOperator ? (secondOperator.outboundMessages / Math.max(1, topOperator.outboundMessages)) * 100 : 0)}%` }" />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <p v-else class="mt-3 text-sm text-slate-500">Belum ada operator lain yang bisa dibandingkan.</p>
                    </article>
                </div>

                <div class="mt-5 overflow-x-auto rounded-[1.6rem] border border-slate-200">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-50">
                            <tr class="text-left text-slate-500">
                                <th class="px-4 py-3 font-black uppercase tracking-[0.18em]">Operator</th>
                                <th class="px-4 py-3 font-black uppercase tracking-[0.18em]">Percakapan</th>
                                <th class="px-4 py-3 font-black uppercase tracking-[0.18em]">Pesan outbound</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in leaderboard" :key="row.id" class="border-t border-slate-100 bg-white/80">
                                <td class="px-4 py-3 font-semibold text-slate-900">{{ row.name }}</td>
                                <td class="px-4 py-3 font-black text-sky-600">{{ row.conversations }}</td>
                                <td class="px-4 py-3 font-black text-emerald-600">{{ row.outboundMessages }}</td>
                            </tr>
                            <tr v-if="leaderboard.length === 0">
                                <td colspan="3" class="px-4 py-6 text-center text-slate-500">Belum ada data operator.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="report-panel overflow-hidden p-5 sm:p-6">
                <p class="text-[11px] font-black uppercase tracking-[0.24em] text-slate-500">Kontak dan tipe pesan</p>
                <h2 class="mt-2 text-lg font-black text-slate-900">Apa yang paling ramai bulan ini</h2>

                <div class="mt-5 grid gap-4">
                    <div class="rounded-[1.6rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#f8fafc)] p-4 shadow-[0_18px_40px_rgba(148,163,184,0.12)]">
                        <p class="text-sm font-black text-slate-900">Kontak tersibuk</p>
                        <div class="mt-4 space-y-3">
                            <div v-for="contact in topContacts" :key="contact.remoteNumber" class="rounded-2xl border border-slate-200 bg-white px-4 py-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-black text-slate-900">{{ contact.name }}</p>
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ contact.remoteNumber }}</p>
                                        <p class="mt-2 text-xs text-slate-500">{{ contact.lastMessagePreview || 'Belum ada preview' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-black text-sky-700">{{ contact.messages }}</p>
                                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">pesan</p>
                                    </div>
                                </div>
                            </div>
                            <p v-if="topContacts.length === 0" class="text-sm text-slate-500">Belum ada data kontak.</p>
                        </div>
                    </div>
                    <div class="rounded-[1.6rem] border border-slate-200 bg-[linear-gradient(180deg,#ffffff,#f8fafc)] p-4 shadow-[0_18px_40px_rgba(148,163,184,0.12)]">
                        <p class="text-sm font-black text-slate-900">Jenis pesan dominan</p>
                        <div class="mt-4 space-y-3">
                            <div v-for="type in messageTypes" :key="type.type" class="type-row">
                                <div class="flex items-center justify-between text-xs font-bold text-slate-500">
                                    <span>{{ type.label }}</span>
                                    <span>{{ type.count }}</span>
                                </div>
                                <div class="mt-2 h-3 rounded-full bg-slate-200">
                                    <div class="h-full rounded-full bg-gradient-to-r from-fuchsia-500 via-violet-500 to-indigo-500" :style="{ width: `${Math.min(100, topMessageType ? (type.count / Math.max(1, topMessageType.count)) * 100 : 0)}%` }" />
                                </div>
                            </div>
                            <p v-if="messageTypes.length === 0" class="text-sm text-slate-500">Belum ada data jenis pesan.</p>
                        </div>
                    </div>
                </div>
            </article>
        </section>
    </LawangsewuLayout>
</template>

<style scoped>
.report-shell {
    backdrop-filter: blur(18px);
}

.report-panel {
    position: relative;
    border-radius: 2rem;
    border: 1px solid rgba(226, 232, 240, 0.9);
    background:
        radial-gradient(circle at top right, rgba(56, 189, 248, 0.08), transparent 25%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.97), rgba(248, 250, 252, 0.96));
    box-shadow: 0 28px 80px rgba(148, 163, 184, 0.16);
}

.glass-card {
    border-radius: 1.6rem;
    border: 1px solid rgba(191, 219, 254, 0.8);
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(239, 246, 255, 0.92));
    padding: 1rem;
    box-shadow: 0 20px 40px rgba(148, 163, 184, 0.14);
}

.glass-card__label {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: rgb(100 116 139);
}

.snapshot-tile {
    border-radius: 1.7rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background:
        radial-gradient(circle at top right, rgba(129, 140, 248, 0.1), transparent 26%),
        linear-gradient(180deg, rgba(255, 255, 255, 0.97), rgba(241, 245, 249, 0.94));
    padding: 1rem;
    box-shadow: 0 22px 44px rgba(148, 163, 184, 0.14);
}

.type-row {
    border-radius: 1.15rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: rgba(255, 255, 255, 0.92);
    padding: 0.9rem 1rem;
}

.metric-pillar {
    position: relative;
    width: 28px;
    height: 72px;
    border-radius: 0.95rem;
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.5), rgba(148, 163, 184, 0.12));
    transform: skewY(-10deg);
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.45),
        14px 18px 28px rgba(15, 23, 42, 0.25);
}

.metric-pillar__top {
    position: absolute;
    top: -9px;
    left: 4px;
    width: 20px;
    height: 20px;
    border-radius: 0.7rem;
    background: rgba(255, 255, 255, 0.65);
    transform: skewX(30deg);
}

.metric-pillar__side {
    position: absolute;
    top: 6px;
    right: -8px;
    width: 10px;
    height: 60px;
    border-radius: 0.7rem;
    background: rgba(255, 255, 255, 0.12);
}

.skyline-stage {
    position: relative;
    overflow: hidden;
    min-height: 360px;
    padding: 1.4rem 1.2rem 1.2rem;
    border-radius: 1.8rem;
    border: 1px solid rgba(226, 232, 240, 0.9);
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.98), rgba(226, 232, 240, 0.88));
    perspective: 1200px;
}

.skyline-stage__grid {
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(148, 163, 184, 0.16) 1px, transparent 1px),
        linear-gradient(90deg, rgba(148, 163, 184, 0.14) 1px, transparent 1px);
    background-size: 100% 25%, 12% 100%;
    mask-image: linear-gradient(180deg, rgba(15, 23, 42, 0.5), transparent 85%);
}

.skyline-stage__floor {
    position: absolute;
    left: 1rem;
    right: 1rem;
    bottom: 0.9rem;
    height: 92px;
    border-radius: 1.6rem;
    transform: rotateX(72deg);
    transform-origin: bottom center;
}

.skyline-columns {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(78px, 1fr));
    gap: 0.85rem;
    align-items: end;
    min-height: 308px;
    padding-top: 1.4rem;
}

.skyline-column {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    align-items: center;
    min-width: 0;
}

.skyline-column__stack {
    position: relative;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    width: 52px;
    min-height: 190px;
}

.skyline-column__glow {
    position: absolute;
    bottom: 4px;
    width: 38px;
    height: 18px;
    border-radius: 999px;
    background: rgba(56, 189, 248, 0.4);
    filter: blur(14px);
}

.skyline-column__body {
    position: absolute;
    bottom: 0;
    left: 5px;
    width: 34px;
    min-height: 12px;
    border-radius: 1rem 1rem 0.55rem 0.55rem;
    box-shadow:
        inset 0 1px 0 rgba(255, 255, 255, 0.36),
        0 18px 28px rgba(15, 23, 42, 0.18);
    transform: rotateX(12deg);
    transform-origin: bottom center;
}

.skyline-column__edge {
    position: absolute;
    right: 5px;
    bottom: 3px;
    width: 10px;
    min-height: 12px;
    border-radius: 0.7rem;
    opacity: 0.72;
    transform: skewY(-48deg);
    transform-origin: bottom;
}

.skyline-column__top {
    position: absolute;
    left: 11px;
    width: 24px;
    height: 12px;
    border-radius: 999px;
    box-shadow: 0 0 18px rgba(255, 255, 255, 0.35);
}

.sync-orb-wrap {
    display: flex;
    justify-content: center;
}

.sync-orb {
    --sync-progress-deg: 0deg;
    position: relative;
    width: 220px;
    height: 220px;
    padding: 14px;
    border-radius: 999px;
    background:
        radial-gradient(circle at 30% 28%, rgba(255, 255, 255, 0.95), rgba(255, 255, 255, 0.15) 26%, transparent 31%),
        conic-gradient(from 210deg, #0f172a 0deg, #0f172a var(--sync-progress-deg), rgba(226, 232, 240, 0.95) var(--sync-progress-deg), rgba(226, 232, 240, 0.95) 360deg);
    box-shadow:
        inset 0 4px 18px rgba(255, 255, 255, 0.3),
        0 24px 70px rgba(99, 102, 241, 0.18);
}

.sync-orb::before {
    content: '';
    position: absolute;
    inset: 16px;
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.95), rgba(226, 232, 240, 0.96));
    box-shadow: inset 0 -10px 18px rgba(148, 163, 184, 0.16);
}

.sync-orb__inner {
    position: relative;
    z-index: 1;
    display: flex;
    height: 100%;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
}

.sync-card {
    border-radius: 1.4rem;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
    padding: 1rem;
    box-shadow: 0 18px 40px rgba(148, 163, 184, 0.12);
}

.sync-card__label {
    font-size: 10px;
    font-weight: 900;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: rgb(100 116 139);
}

.sync-card__value {
    margin-top: 0.35rem;
    font-size: 1.7rem;
    line-height: 1;
    font-weight: 900;
}
</style>
