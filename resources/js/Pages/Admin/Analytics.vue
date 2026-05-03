<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';

const props = defineProps({
    appMeta:   { type: Object, default: () => ({}) },
    navGroups: { type: Array,  default: () => [] },
    stats:     { type: Object, required: true },
});

const chartCanvas = ref(null);

onMounted(() => {
    if (chartCanvas.value && window.Chart) {
        const ctx = chartCanvas.value.getContext('2d');
        const labels = props.stats.chart_data.map(d => d.visit_date);
        const data = props.stats.chart_data.map(d => d.total);
        
        // If empty, provide some dummy structure just to show the grid
        if (labels.length === 0) {
            labels.push(new Date().toISOString().split('T')[0]);
            data.push(0);
        }

        new window.Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pengunjung Web',
                    data: data,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#8b5cf6',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#94a3b8' },
                        grid: { color: 'rgba(148, 163, 184, 0.1)', borderDash: [5, 5] }
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>

<template>
    <Head title="Lawangsewu Analytics" />
    <LawangsewuLayout
        current-route="admin-analytics"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500 shadow-[0_8px_20px_-10px_rgba(99,102,241,0.7)]">
                            <svg class="h-4.5 w-4.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 3v18h18" />
                                <path d="M18 17V9" />
                                <path d="M13 17V5" />
                                <path d="M8 17v-3" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-[var(--text-1)] tracking-tight leading-none">Lawangsewu Analytics</h1>
                            <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest mt-0.5">Native Visitor Tracker</p>
                        </div>
                    </div>
                    <p class="text-[12.5px] text-[var(--text-2)] max-w-xl leading-relaxed">
                        Pantau statistik pengunjung halaman widget publik (seperti jadwal sidang, antrian, dan laporan perkara) secara real-time tanpa bergantung pada pihak ketiga.
                    </p>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <!-- Today -->
                <div class="relative overflow-hidden rounded-2xl border border-violet-500/20 bg-gradient-to-br from-violet-500/10 to-indigo-500/5 p-5 shadow-[0_4px_20px_-10px_rgba(139,92,246,0.3)]">
                    <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-violet-500/10 blur-2xl"></div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-violet-500">Pengunjung Hari Ini</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-4xl font-black tabular-nums tracking-tight text-[var(--text-1)]">{{ stats.today }}</span>
                        <span class="text-xs font-semibold text-[var(--text-3)]">kunjungan</span>
                    </div>
                    <div class="mt-3 inline-flex items-center gap-1 rounded-full bg-white/50 px-2 py-0.5 text-[10px] font-bold text-[var(--text-2)] dark:bg-black/20">
                        <span v-if="stats.today >= stats.yesterday" class="text-emerald-500">↑ Naiki</span>
                        <span v-else class="text-red-500">↓ Turun</span>
                        dibanding kemarin ({{ stats.yesterday }})
                    </div>
                </div>

                <!-- This Month -->
                <div class="relative overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.2)]">
                    <p class="text-[11px] font-bold uppercase tracking-widest text-[var(--text-3)]">Total Bulan Ini</p>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-4xl font-black tabular-nums tracking-tight text-[var(--text-1)]">{{ stats.this_month }}</span>
                        <span class="text-xs font-semibold text-[var(--text-3)]">kunjungan</span>
                    </div>
                </div>
                
                <!-- Live Users -->
                <div class="relative overflow-hidden rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-5 shadow-[0_4px_20px_-10px_rgba(16,185,129,0.2)]">
                    <div class="absolute right-4 top-4 flex h-3 w-3 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </div>
                    <p class="text-[11px] font-bold uppercase tracking-widest text-emerald-500">Status Pelacakan</p>
                    <div class="mt-2 flex flex-col">
                        <span class="text-xl font-black tracking-tight text-[var(--text-1)]">Aktif / Real-Time</span>
                        <span class="text-xs font-semibold text-[var(--text-3)] mt-1">Sistem berjalan normal</span>
                    </div>
                </div>
            </div>

            <!-- Charts & Tables -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- Trend Chart -->
                <div class="lg:col-span-2 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.2)]">
                    <h2 class="mb-4 text-[11px] font-black uppercase tracking-[0.15em] text-[var(--text-3)]">📈 Tren 7 Hari Terakhir</h2>
                    <div class="relative h-64 w-full">
                        <canvas ref="chartCanvas"></canvas>
                        <div v-if="stats.chart_data.length === 0" class="absolute inset-0 flex items-center justify-center bg-[var(--surface-1)]/80 backdrop-blur-sm">
                            <p class="text-sm font-bold text-[var(--text-3)]">Belum ada data kunjungan dalam 7 hari terakhir</p>
                        </div>
                    </div>
                </div>

                <!-- Top Widgets -->
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-0 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.2)] overflow-hidden">
                    <div class="p-5 border-b border-[var(--border)]">
                        <h2 class="text-[11px] font-black uppercase tracking-[0.15em] text-[var(--text-3)]">🏆 Widget Terpopuler</h2>
                    </div>
                    
                    <div v-if="stats.top_widgets.length === 0" class="p-8 text-center">
                        <p class="text-sm text-[var(--text-3)]">Data belum tersedia.</p>
                    </div>
                    
                    <ul v-else class="divide-y divide-[var(--border)]">
                        <li v-for="(widget, index) in stats.top_widgets" :key="widget.widget_name" class="flex items-center justify-between px-5 py-3.5 hover:bg-[var(--surface-2)] transition-colors">
                            <div class="flex items-center gap-3">
                                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-[var(--surface-3)] text-[10px] font-bold text-[var(--text-2)]">
                                    {{ index + 1 }}
                                </span>
                                <span class="text-[13px] font-semibold text-[var(--text-1)]">{{ widget.widget_name }}</span>
                            </div>
                            <span class="rounded-full bg-violet-500/10 px-2.5 py-0.5 text-[11px] font-bold text-violet-500">
                                {{ widget.total }}
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </LawangsewuLayout>
</template>
