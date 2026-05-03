<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appMeta:      { type: Object, default: () => ({}) },
    navGroups:    { type: Array,  default: () => [] },
    visitorStats: { type: Object, default: () => ({ today: 0, this_month: 0, breakdown: [], chart_data: [] }) },
});

const page = usePage();

// ── Form State ───────────────────────────────────────────
const title       = ref('Laporan Sistem Lawangsewu');
const orientation = ref('portrait');
const content     = ref('');

// ── UI State ─────────────────────────────────────────────
const isLoading  = ref(false);
const errorMsg   = ref('');
const successMsg = ref('');
const charCount  = computed(() => content.value.length);
const charLimit  = 50000;
const charPercent = computed(() => Math.min((charCount.value / charLimit) * 100, 100));

// ── Chart ────────────────────────────────────────────────
const chartCanvas = ref(null);

onMounted(() => {
    if (!chartCanvas.value) return;
    const chartData = props.visitorStats.chart_data || [];
    const labels = chartData.length ? chartData.map(d => d.visit_date) : ['—'];
    const data   = chartData.length ? chartData.map(d => d.total)      : [0];
    if (window.Chart) {
        new window.Chart(chartCanvas.value.getContext('2d'), {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Pengunjung',
                    data,
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139,92,246,0.08)',
                    borderWidth: 2.5,
                    tension: 0.4,
                    fill: true,
                    pointBackgroundColor: '#fff',
                    pointBorderColor: '#8b5cf6',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: '#94a3b8', stepSize: 1 },
                        grid: { color: 'rgba(148,163,184,0.1)', borderDash: [4, 4] },
                    },
                    x: {
                        ticks: { color: '#94a3b8', font: { size: 10 } },
                        grid: { display: false },
                    },
                },
            },
        });
    }
});

// ── QuickChart URL for PDF ───────────────────────────────
function getChartUrl() {
    const cd = props.visitorStats.chart_data || [];
    const labels = cd.length ? cd.map(d => d.visit_date) : ['—'];
    const data   = cd.length ? cd.map(d => d.total)      : [0];
    const cfg = {
        type: 'line',
        data: {
            labels,
            datasets: [{
                data,
                borderColor: '#1a5f7a',
                backgroundColor: 'rgba(26,95,122,0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
            }],
        },
        options: { legend: { display: false }, scales: { y: { beginAtZero: true } } },
    };
    return `https://quickchart.io/chart?w=700&h=280&c=${encodeURIComponent(JSON.stringify(cfg))}`;
}

// ── Templates ────────────────────────────────────────────
const templates = computed(() => [
    {
        id: 'visitor',
        label: '📈 Laporan Pengunjung Web',
        content: `# Laporan Statistik Pengunjung Widget Web

## Grafik Tren Kunjungan (7 Hari Terakhir)
![Grafik Tren Kunjungan](${getChartUrl()})

## Ringkasan Eksekutif

| Indikator       | Jumlah                               |
|-----------------|--------------------------------------|
| Hari Ini        | **${props.visitorStats.today}** kunjungan        |
| Bulan Ini       | **${props.visitorStats.this_month}** kunjungan   |

## Rincian Per Halaman Widget

| Halaman Widget  | Total Kunjungan |
|-----------------|-----------------|
${props.visitorStats.breakdown.length
    ? props.visitorStats.breakdown.map(w => `| ${w.widget_name} | ${w.total} |`).join('\n')
    : '| Belum ada data | 0 |'}

---
> *Data dikumpulkan oleh Lawangsewu Native Analytics — tanpa pihak ketiga.*`,
    },
    {
        id: 'statistik',
        label: '📊 Statistik Sistem',
        content: `# Laporan Statistik Sistem

## Ringkasan Periode

| Komponen       | Total | Perubahan |
|----------------|-------|-----------|
| Pengguna Aktif | —     | —         |
| Transaksi      | —     | —         |
| Error Rate     | —     | —         |

## Detail Temuan

_Isi detail temuan di sini._

## Kesimpulan

_Isi kesimpulan di sini._`,
    },
    {
        id: 'insiden',
        label: '🚨 Laporan Insiden',
        content: `# Laporan Insiden

## Informasi Insiden

| Field         | Detail      |
|---------------|-------------|
| Tanggal       | —           |
| Waktu Mulai   | —           |
| Waktu Selesai | —           |
| Tingkat       | ⚠️ Medium   |
| PIC           | —           |

## Kronologi

1. —
2. —
3. —

## Tindakan yang Diambil

_Deskripsikan tindakan penanganan._

## Pencegahan ke Depan

_Deskripsikan langkah pencegahan._`,
    },
    {
        id: 'bulanan',
        label: '📅 Laporan Bulanan',
        content: `# Laporan Bulanan

## Executive Summary

_Ringkasan singkat kondisi bulan ini._

## Kinerja Layanan

| Layanan        | Target SLA | Actual | Status |
|----------------|------------|--------|--------|
| PTSP           | 99%        | —      | —      |
| WA Caraka      | 98%        | —      | —      |
| SIPP Hub       | 99.5%      | —      | —      |
| Monitor Sistem | 100%       | —      | —      |

## Rekomendasi Bulan Depan

- —
- —
- —`,
    },
]);

function applyTemplate(t) {
    title.value   = t.label.replace(/^[^\w]+/, '').trim();
    content.value = t.content;
}

// ── Generate PDF ──────────────────────────────────────────
async function generatePdf() {
    if (!content.value.trim()) {
        errorMsg.value = 'Konten laporan tidak boleh kosong.';
        return;
    }
    isLoading.value  = true;
    errorMsg.value   = '';
    successMsg.value = '';
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || page.props.csrf_token || '';
        const response = await fetch(route('admin.laporan.generate'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/pdf', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ title: title.value, content: content.value, orientation: orientation.value }),
        });
        if (!response.ok) {
            const json = await response.json().catch(() => ({}));
            throw new Error(json.message || `Server error: ${response.status}`);
        }
        const blob  = await response.blob();
        const url   = URL.createObjectURL(blob);
        const link  = document.createElement('a');
        const disp  = response.headers.get('Content-Disposition') || '';
        const match = disp.match(/filename="([^"]+)"/);
        link.href     = url;
        link.download = match ? match[1] : 'laporan.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
        successMsg.value = `✅ PDF berhasil diunduh: ${link.download}`;
    } catch (err) {
        errorMsg.value = `❌ Gagal: ${err.message}`;
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <LawangsewuLayout current-route="admin-laporan" :nav-groups="navGroups" :app-meta="appMeta">
        <div class="space-y-5">

            <!-- ── Header ──────────────────────────────── -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 shadow-[0_8px_24px_-10px_rgba(139,92,246,0.6)]">
                        <svg class="h-5 w-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 3.5h6l4 4V20a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5.5a2 2 0 0 1 2-2z"/>
                            <path d="M14 3.5V8h4"/><path d="M9 17v-3"/><path d="M12 17v-5"/><path d="M15 17v-2"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-[var(--text-1)] tracking-tight leading-none">Laporan Sistem</h1>
                        <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest mt-0.5">PDF Generator • Markdown Engine</p>
                    </div>
                </div>
                <span class="mt-1 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-violet-500/10 border border-violet-500/20 text-violet-400">Superadmin</span>
            </div>

            <!-- ── Visitor Stats Bar ────────────────────── -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-2xl border border-violet-500/20 bg-violet-500/5 p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-violet-400 mb-1">Hari Ini</p>
                    <p class="text-3xl font-black tabular-nums text-[var(--text-1)]">{{ visitorStats.today }}</p>
                    <p class="text-[10px] text-[var(--text-3)] mt-0.5">pengunjung widget</p>
                </div>
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-3)] mb-1">Bulan Ini</p>
                    <p class="text-3xl font-black tabular-nums text-[var(--text-1)]">{{ visitorStats.this_month }}</p>
                    <p class="text-[10px] text-[var(--text-3)] mt-0.5">total kunjungan</p>
                </div>
                <div class="col-span-2 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-3)] mb-2">Tren 7 Hari Terakhir</p>
                    <div class="h-16 w-full">
                        <canvas ref="chartCanvas"></canvas>
                    </div>
                </div>
            </div>

            <!-- ── Alerts ──────────────────────────────── -->
            <div v-if="successMsg || errorMsg" class="flex flex-col gap-2">
                <div v-if="successMsg" class="flex items-center gap-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                    <svg class="h-4 w-4 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[12.5px] text-emerald-300 font-medium">{{ successMsg }}</p>
                </div>
                <div v-if="errorMsg" class="flex items-center gap-3 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3">
                    <svg class="h-4 w-4 shrink-0 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[12.5px] text-red-300 font-medium">{{ errorMsg }}</p>
                </div>
            </div>

            <!-- ── Main Grid ───────────────────────────── -->
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-4">

                <!-- Left: Config + Templates -->
                <div class="space-y-4 lg:col-span-1">

                    <!-- Konfigurasi -->
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 space-y-4 shadow-[0_4px_20px_-8px_rgba(0,0,0,0.3)]">
                        <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">⚙️ Konfigurasi</h2>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-wider" for="laporan-title">Judul</label>
                            <input id="laporan-title" v-model="title" type="text"
                                class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-0)] px-3.5 py-2.5 text-[13px] text-[var(--text-1)] placeholder-[var(--text-3)] outline-none transition focus:border-violet-500/60 focus:ring-2 focus:ring-violet-500/20"
                                placeholder="Judul laporan..." maxlength="200">
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-wider">Orientasi</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="opt in ['portrait','landscape']" :key="opt" type="button"
                                    :class="['flex items-center justify-center gap-1.5 rounded-xl border py-2 text-[10px] font-bold uppercase tracking-widest transition-all',
                                        orientation === opt
                                            ? 'border-violet-500/50 bg-violet-500/15 text-violet-400'
                                            : 'border-[var(--border)] bg-[var(--surface-0)] text-[var(--text-3)] hover:text-[var(--text-2)]']"
                                    @click="orientation = opt">
                                    <svg v-if="opt==='portrait'" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="14" height="18" rx="2"/></svg>
                                    <svg v-else class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
                                    {{ opt === 'portrait' ? 'Potret' : 'Lanskap' }}
                                </button>
                            </div>
                        </div>

                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-[var(--text-3)]">Karakter</span>
                                <span :class="charCount > 45000 ? 'text-red-400' : 'text-[var(--text-3)]'" class="font-bold tabular-nums">{{ charCount.toLocaleString() }}/{{ charLimit.toLocaleString() }}</span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-[var(--surface-2)] overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-300"
                                    :class="charPercent > 90 ? 'bg-red-500' : charPercent > 70 ? 'bg-amber-400' : 'bg-violet-500'"
                                    :style="{ width: charPercent + '%' }"></div>
                            </div>
                        </div>

                        <button id="btn-generate-laporan" type="button" :disabled="isLoading"
                            class="group relative w-full flex items-center justify-center gap-2 rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-600 px-4 py-3 text-[12px] font-black text-white uppercase tracking-widest shadow-[0_8px_24px_-10px_rgba(139,92,246,0.7)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_32px_-10px_rgba(139,92,246,0.8)] disabled:opacity-60 disabled:cursor-not-allowed disabled:translate-y-0"
                            @click="generatePdf">
                            <svg v-if="!isLoading" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                            <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                            {{ isLoading ? 'Generating...' : 'Download PDF (A4)' }}
                        </button>
                    </div>

                    <!-- Template Cepat -->
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[0_4px_20px_-8px_rgba(0,0,0,0.3)]">
                        <h2 class="mb-3 text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">📄 Template Cepat</h2>
                        <div class="space-y-2">
                            <button v-for="t in templates" :key="t.id" type="button"
                                class="group flex w-full items-center gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface-0)] px-3.5 py-2.5 text-left transition-all hover:border-violet-500/40 hover:bg-violet-500/5"
                                @click="applyTemplate(t)">
                                <span class="text-[12px] font-semibold text-[var(--text-2)] group-hover:text-[var(--text-1)] transition-colors">{{ t.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Widget Populer -->
                    <div v-if="visitorStats.breakdown.length" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] overflow-hidden shadow-[0_4px_20px_-8px_rgba(0,0,0,0.3)]">
                        <div class="px-5 py-3 border-b border-[var(--border)]">
                            <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">🏆 Widget Populer</h2>
                        </div>
                        <ul class="divide-y divide-[var(--border)]">
                            <li v-for="(w, i) in visitorStats.breakdown.slice(0,6)" :key="w.widget_name" class="flex items-center justify-between px-5 py-2.5 hover:bg-[var(--surface-2)] transition-colors">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex h-5 w-5 items-center justify-center rounded-md bg-[var(--surface-3)] text-[9px] font-bold text-[var(--text-3)]">{{ i+1 }}</span>
                                    <span class="text-[12px] text-[var(--text-2)] font-medium truncate max-w-[110px]">{{ w.widget_name }}</span>
                                </div>
                                <span class="rounded-full bg-violet-500/10 px-2 py-0.5 text-[10px] font-bold text-violet-400">{{ w.total }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Right: Editor -->
                <div class="lg:col-span-3 flex flex-col gap-4">

                    <!-- Editor Topbar -->
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex gap-1.5">
                                <div class="h-2.5 w-2.5 rounded-full bg-red-500/70"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-amber-400/70"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-emerald-400/70"></div>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Editor Markdown</span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] text-[var(--text-3)]">
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                            A4 · {{ orientation === 'portrait' ? 'Portrait' : 'Landscape' }}
                        </div>
                    </div>

                    <!-- Textarea -->
                    <div class="relative flex-1 rounded-2xl border border-[var(--border)] bg-[var(--surface-0)] shadow-[0_4px_20px_-8px_rgba(0,0,0,0.3)] overflow-hidden">
                        <textarea id="laporan-content" v-model="content"
                            class="w-full resize-none bg-transparent px-6 py-5 text-[12.5px] text-[var(--text-1)] font-mono leading-relaxed outline-none placeholder-[var(--text-3)]"
                            style="min-height:540px;"
                            :placeholder="'Pilih template di sebelah kiri atau tulis konten Markdown di sini...\n\n# Judul Laporan\n## Sub Judul\n| Kolom A | Kolom B |\n|---------|---------|\n| Data 1  | Data 2  |'"
                            :maxlength="charLimit"
                            spellcheck="false">
                        </textarea>
                    </div>

                    <!-- Panduan Markdown -->
                    <div class="grid grid-cols-4 gap-2">
                        <div v-for="tip in [
                            { s: '# Judul',    d: 'Heading H1' },
                            { s: '**tebal**',  d: 'Teks Tebal' },
                            { s: '| A | B |',  d: 'Tabel' },
                            { s: '> catatan',  d: 'Blockquote' },
                        ]" :key="tip.s" class="rounded-xl border border-[var(--border)] bg-[var(--surface-1)] px-3 py-2.5 text-center">
                            <code class="text-[10px] text-violet-400 font-mono">{{ tip.s }}</code>
                            <p class="text-[9px] text-[var(--text-3)] mt-1">{{ tip.d }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LawangsewuLayout>
</template>
