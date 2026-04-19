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

const chartData = computed(() => {
    if (datasetKey.value === 'weekly') return stats.value?.weeklyInbound || [];
    if (datasetKey.value === 'monthly') return stats.value?.monthlyInbound || [];
    return stats.value?.dailyInbound || [];
});

const maxCount = computed(() => Math.max(1, ...chartData.value.map((d) => Number(d.count || 0))));

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

const updatedAtText = computed(() => {
    if (!stats.value?.generatedAt) return '-';
    return new Date(stats.value.generatedAt).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit', month: 'short', year: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
});
</script>

<template>
    <Head title="Laporan WA Caraka" />

    <LawangsewuLayout current-route="wacaraka" :nav-groups="navGroups" :app-meta="appMeta">
        <section class="rounded-[2rem] border border-[var(--border)] bg-[linear-gradient(140deg,rgba(15,23,42,0.98),rgba(30,41,59,0.95))] p-6 text-white shadow-[var(--shadow)]">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.25em] text-cyan-300">WA Caraka • Laporan Operator PTSP</p>
                    <h1 class="mt-2 text-xl font-black sm:text-2xl">Statistik Harian, Mingguan, Bulanan</h1>
                    <p class="mt-1 text-xs text-slate-300">Terakhir diperbarui: {{ updatedAtText }}</p>
                </div>
                <div class="flex gap-2">
                    <button @click="window.history.back()" class="rounded-xl border border-white/20 px-3 py-1.5 text-xs font-bold text-slate-100 hover:bg-white/10 transition">Kembali</button>
                    <button @click="refreshStats" :disabled="loading" class="rounded-xl border border-cyan-300/40 bg-cyan-300/10 px-3 py-1.5 text-xs font-bold text-cyan-200 hover:bg-cyan-300/20 transition disabled:opacity-40">
                        {{ loading ? 'Memuat...' : '↻ Refresh Data' }}
                    </button>
                </div>
            </div>
        </section>

        <section class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4 shadow-[var(--shadow)]">
                <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-2)]">Inbound Hari Ini</p>
                <p class="mt-1 text-2xl font-black text-cyan-600">{{ summary.inboundToday || 0 }}</p>
            </article>
            <article class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4 shadow-[var(--shadow)]">
                <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-2)]">Inbound Minggu Ini</p>
                <p class="mt-1 text-2xl font-black text-emerald-600">{{ summary.inboundWeek || 0 }}</p>
            </article>
            <article class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4 shadow-[var(--shadow)]">
                <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-2)]">Inbound Bulan Ini</p>
                <p class="mt-1 text-2xl font-black text-violet-600">{{ summary.inboundMonth || 0 }}</p>
            </article>
            <article class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4 shadow-[var(--shadow)]">
                <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-2)]">Percakapan Aktif</p>
                <p class="mt-1 text-2xl font-black text-amber-600">{{ summary.activeConversations || 0 }}</p>
            </article>
        </section>

        <section class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[var(--shadow)]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-base font-black text-[var(--text-1)]">Grafik Pesan Masuk</h2>
                <div class="flex gap-2">
                    <button @click="datasetKey = 'daily'" class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                            :class="datasetKey === 'daily' ? 'bg-cyan-600 text-white' : 'border border-[var(--border)] text-[var(--text-2)] hover:bg-[var(--surface-2)]'">Harian</button>
                    <button @click="datasetKey = 'weekly'" class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                            :class="datasetKey === 'weekly' ? 'bg-emerald-600 text-white' : 'border border-[var(--border)] text-[var(--text-2)] hover:bg-[var(--surface-2)]'">Mingguan</button>
                    <button @click="datasetKey = 'monthly'" class="rounded-xl px-3 py-1.5 text-xs font-bold transition"
                            :class="datasetKey === 'monthly' ? 'bg-violet-600 text-white' : 'border border-[var(--border)] text-[var(--text-2)] hover:bg-[var(--surface-2)]'">Bulanan</button>
                </div>
            </div>

            <div class="mt-4 grid gap-2">
                <div v-for="point in chartData" :key="point.label" class="grid grid-cols-[70px,minmax(0,1fr),40px] items-center gap-2 sm:grid-cols-[90px,minmax(0,1fr),50px]">
                    <span class="text-[10px] font-bold text-[var(--text-2)] sm:text-xs">{{ point.label }}</span>
                    <div class="h-3 rounded-full bg-[var(--surface-2)] overflow-hidden">
                        <div class="h-full rounded-full bg-gradient-to-r from-cyan-500 via-sky-500 to-indigo-500" :style="{ width: `${Math.max(4, (Number(point.count || 0) / maxCount) * 100)}%` }" />
                    </div>
                    <span class="text-right text-[10px] font-black text-[var(--text-1)] sm:text-xs">{{ point.count || 0 }}</span>
                </div>
            </div>
        </section>

        <section class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[var(--shadow)]">
            <h2 class="text-base font-black text-[var(--text-1)]">Kinerja Operator PTSP</h2>
            <p class="mt-1 text-xs text-[var(--text-2)]">Jumlah percakapan yang ditangani dan total pesan outbound per operator.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead>
                        <tr class="border-b border-[var(--border)] text-left text-[var(--text-2)]">
                            <th class="px-3 py-2 font-bold">Operator</th>
                            <th class="px-3 py-2 font-bold">Percakapan</th>
                            <th class="px-3 py-2 font-bold">Pesan Outbound</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in operatorStats" :key="row.id" class="border-b border-[var(--border)]/60">
                            <td class="px-3 py-2 font-semibold text-[var(--text-1)]">{{ row.name }}</td>
                            <td class="px-3 py-2 font-black text-sky-600">{{ row.conversations }}</td>
                            <td class="px-3 py-2 font-black text-emerald-600">{{ row.outboundMessages }}</td>
                        </tr>
                        <tr v-if="operatorStats.length === 0">
                            <td colspan="3" class="px-3 py-6 text-center text-[var(--text-2)]">Belum ada data operator.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </LawangsewuLayout>
</template>
