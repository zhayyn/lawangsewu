<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    appMeta:   { type: Object, default: () => ({}) },
    navGroups: { type: Array,  default: () => [] },
});

const page = usePage();

// ── Form State ─────────────────────────────────────────
const title       = ref('Laporan Sistem Lawangsewu');
const orientation = ref('portrait');
const content     = ref(`# Ringkasan Eksekutif

Dokumen ini merupakan laporan otomatis yang dihasilkan oleh **Super App Lawangsewu**.

## 📊 Statistik Utama

| Metrik              | Nilai     | Status   |
|---------------------|-----------|----------|
| Total Pengguna      | 142       | ✅ Normal |
| Antrian PTSP Hari Ini | 38      | ⚠️ Tinggi |
| Pesan WA Masuk      | 1.204     | ✅ Normal |
| Server Uptime       | 99.87%    | ✅ Optimal |

## 📋 Catatan Operasional

- Semua layanan berjalan normal per tanggal laporan ini dibuat.
- Antrian PTSP mengalami **peningkatan 23%** dibanding minggu lalu.
- WA Caraka berhasil merespons **98.4%** pesan dalam waktu < 5 detik.

## 🔧 Rekomendasi

1. Tambah kapasitas operator PTSP pada hari Senin dan Selasa.
2. Pantau penggunaan memori server pada pukul 10.00–12.00 WIB.
3. Lakukan backup database mingguan setiap Jumat malam.

---

> *Laporan ini digenerate secara otomatis. Untuk pertanyaan teknis, hubungi Tim PTIP.*
`);

// ── UI State ────────────────────────────────────────────
const isLoading   = ref(false);
const errorMsg    = ref('');
const successMsg  = ref('');
const charCount   = computed(() => content.value.length);
const charLimit   = 50000;
const charPercent = computed(() => Math.min((charCount.value / charLimit) * 100, 100));

// ── Preview ─────────────────────────────────────────────
const showPreview   = ref(false);
const previewLines  = computed(() => content.value.split('\n').slice(0, 30));

// ── Templates ───────────────────────────────────────────
const templates = [
    {
        id: 'statistik',
        label: '📊 Statistik Sistem',
        content: `# Laporan Statistik Sistem

## Ringkasan Periode

| Komponen       | Total     | Perubahan |
|----------------|-----------|-----------|
| Pengguna Aktif | —         | —         |
| Transaksi      | —         | —         |
| Error Rate     | —         | —         |

## Detail Temuan

_Isi detail temuan di sini._

## Kesimpulan

_Isi kesimpulan di sini._
`,
    },
    {
        id: 'insiden',
        label: '🚨 Laporan Insiden',
        content: `# Laporan Insiden

## Informasi Insiden

| Field         | Detail |
|---------------|--------|
| Tanggal       | —      |
| Waktu Mulai   | —      |
| Waktu Selesai | —      |
| Tingkat       | ⚠️ Medium |
| PIC           | —      |

## Kronologi

1. —
2. —
3. —

## Tindakan yang Diambil

_Deskripsikan tindakan penanganan._

## Pencegahan ke Depan

_Deskripsikan langkah pencegahan._
`,
    },
    {
        id: 'bulanan',
        label: '📅 Laporan Bulanan',
        content: `# Laporan Bulanan

## Executive Summary

_Ringkasan singkat kondisi bulan ini._

## Kinerja Layanan

| Layanan        | Target SLA | Actual | Status |
|----------------|-----------|--------|--------|
| PTSP           | 99%        | —      | —      |
| WA Caraka      | 98%        | —      | —      |
| SIPP Hub       | 99.5%      | —      | —      |
| Monitor Sistem | 100%       | —      | —      |

## Rekomendasi Bulan Depan

- —
- —
- —
`,
    },
];

function applyTemplate(t) {
    title.value   = t.label.replace(/^[^\w]+/, '').trim();
    content.value = t.content;
    showPreview.value = false;
}

// ── Generate PDF ─────────────────────────────────────────
async function generatePdf() {
    if (!content.value.trim()) {
        errorMsg.value  = 'Konten laporan tidak boleh kosong.';
        successMsg.value = '';
        return;
    }

    isLoading.value  = true;
    errorMsg.value   = '';
    successMsg.value = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
            || page.props.csrf_token
            || '';

        const response = await fetch(route('admin.laporan.generate'), {
            method: 'POST',
            headers: {
                'Content-Type':  'application/json',
                'Accept':        'application/pdf',
                'X-CSRF-TOKEN':  csrfToken,
            },
            body: JSON.stringify({
                title:       title.value,
                content:     content.value,
                orientation: orientation.value,
            }),
        });

        if (!response.ok) {
            const json = await response.json().catch(() => ({}));
            throw new Error(json.message || `Server error: ${response.status}`);
        }

        // Trigger unduhan PDF di browser
        const blob        = await response.blob();
        const url         = URL.createObjectURL(blob);
        const link        = document.createElement('a');
        const disposition = response.headers.get('Content-Disposition') || '';
        const match       = disposition.match(/filename="([^"]+)"/);
        link.href         = url;
        link.download     = match ? match[1] : 'laporan.pdf';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        successMsg.value = `✅ Laporan berhasil digenerate: ${link.download}`;
    } catch (err) {
        errorMsg.value = `❌ Gagal generate laporan: ${err.message}`;
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <LawangsewuLayout
        current-route="admin-laporan"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">

            <!-- ── Page Header ──────────────────────────────── -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-fuchsia-500 shadow-[0_8px_20px_-10px_rgba(139,92,246,0.7)]">
                            <svg class="h-4.5 w-4.5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 3.5h6l4 4V20a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5.5a2 2 0 0 1 2-2z"/>
                                <path d="M14 3.5V8h4"/>
                                <path d="M9 17v-3"/><path d="M12 17v-5"/><path d="M15 17v-2"/>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-black text-[var(--text-1)] tracking-tight leading-none">Laporan</h1>
                            <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest mt-0.5">Document Styler — PDF Generator</p>
                        </div>
                    </div>
                    <p class="text-[12.5px] text-[var(--text-2)] max-w-xl leading-relaxed">
                        Generate laporan PDF berkualitas tinggi dari konten Markdown.
                        Mendukung tabel, emoji, kode, dan highlight.
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-violet-500/10 border border-violet-500/20 text-violet-400">
                        Superadmin
                    </span>
                    <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-widest bg-[var(--surface-3)] border border-[var(--border)] text-[var(--text-3)]">
                        developed by zhayyn™
                    </span>
                </div>
            </div>

            <!-- ── Alert Messages ───────────────────────────── -->
            <div v-if="successMsg || errorMsg" class="flex flex-col gap-2">
                <div v-if="successMsg"
                    class="flex items-start gap-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                    <svg class="h-4 w-4 text-emerald-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[12.5px] text-emerald-300 font-medium">{{ successMsg }}</p>
                </div>
                <div v-if="errorMsg"
                    class="flex items-start gap-3 rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3">
                    <svg class="h-4 w-4 text-red-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-[12.5px] text-red-300 font-medium">{{ errorMsg }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

                <!-- ── Left Panel: Settings + Templates ────── -->
                <div class="space-y-4 lg:col-span-1">

                    <!-- Settings Card -->
                    <div class="card-surface rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 space-y-4 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.4)]">
                        <h2 class="text-[11px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">⚙️ Konfigurasi</h2>

                        <!-- Judul -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-[var(--text-2)] uppercase tracking-wider" for="laporan-title">
                                Judul Laporan
                            </label>
                            <input
                                id="laporan-title"
                                v-model="title"
                                type="text"
                                class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-0)] px-3.5 py-2.5 text-[13px] text-[var(--text-1)] placeholder-[var(--text-3)] outline-none transition focus:border-violet-500/60 focus:ring-2 focus:ring-violet-500/20"
                                placeholder="Judul laporan PDF..."
                                maxlength="200"
                            >
                        </div>

                        <!-- Orientasi -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-bold text-[var(--text-2)] uppercase tracking-wider">Orientasi Halaman</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="opt in ['portrait', 'landscape']"
                                    :key="opt"
                                    type="button"
                                    :class="[
                                        'flex items-center justify-center gap-2 rounded-xl border py-2.5 text-[11px] font-bold uppercase tracking-widest transition-all duration-200',
                                        orientation === opt
                                            ? 'border-violet-500/50 bg-violet-500/15 text-violet-400 shadow-[0_0_12px_-4px_rgba(139,92,246,0.5)]'
                                            : 'border-[var(--border)] bg-[var(--surface-0)] text-[var(--text-3)] hover:border-[var(--border-hover)] hover:text-[var(--text-2)]'
                                    ]"
                                    @click="orientation = opt"
                                >
                                    <svg v-if="opt === 'portrait'" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="5" y="3" width="14" height="18" rx="2"/></svg>
                                    <svg v-else class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"/></svg>
                                    {{ opt === 'portrait' ? 'Potret' : 'Lanskap' }}
                                </button>
                            </div>
                        </div>

                        <!-- Char Count -->
                        <div class="space-y-1">
                            <div class="flex justify-between">
                                <span class="text-[10px] text-[var(--text-3)]">Panjang Konten</span>
                                <span :class="charCount > 45000 ? 'text-red-400' : 'text-[var(--text-3)]'" class="text-[10px] font-bold tabular-nums">
                                    {{ charCount.toLocaleString() }} / {{ charLimit.toLocaleString() }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-[var(--surface-2)] overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-300"
                                    :class="charPercent > 90 ? 'bg-red-500' : charPercent > 70 ? 'bg-amber-400' : 'bg-violet-500'"
                                    :style="{ width: charPercent + '%' }"
                                ></div>
                            </div>
                        </div>

                        <!-- Generate Button -->
                        <button
                            id="btn-generate-laporan"
                            type="button"
                            :disabled="isLoading"
                            class="group relative w-full flex items-center justify-center gap-2.5 rounded-xl bg-gradient-to-br from-violet-600 to-fuchsia-600 px-4 py-3 text-[12.5px] font-black text-white uppercase tracking-widest shadow-[0_8px_24px_-10px_rgba(139,92,246,0.7)] transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_14px_32px_-10px_rgba(139,92,246,0.8)] disabled:opacity-60 disabled:cursor-not-allowed disabled:translate-y-0"
                            @click="generatePdf"
                        >
                            <span class="pointer-events-none absolute inset-0 rounded-xl bg-white/0 group-hover:bg-white/5 transition-colors duration-300"></span>

                            <svg v-if="!isLoading" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                            </svg>

                            {{ isLoading ? 'Generating PDF...' : 'Download PDF' }}
                        </button>
                    </div>

                    <!-- Templates Card -->
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.4)]">
                        <h2 class="mb-3 text-[11px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">📄 Template Cepat</h2>
                        <div class="space-y-2">
                            <button
                                v-for="t in templates"
                                :key="t.id"
                                type="button"
                                class="group flex w-full items-center gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface-0)] px-3.5 py-2.5 text-left transition-all duration-200 hover:border-violet-500/40 hover:bg-violet-500/5"
                                @click="applyTemplate(t)"
                            >
                                <span class="text-[12.5px] font-semibold text-[var(--text-2)] group-hover:text-[var(--text-1)] transition-colors">{{ t.label }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Tips Card -->
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[0_4px_20px_-10px_rgba(0,0,0,0.4)]">
                        <h2 class="mb-3 text-[11px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">💡 Markdown Tips</h2>
                        <div class="space-y-2">
                            <div v-for="tip in [
                                { syntax: '# Judul',           desc: 'Heading H1' },
                                { syntax: '## Sub-judul',      desc: 'Heading H2' },
                                { syntax: '**teks**',          desc: 'Tebal' },
                                { syntax: '| A | B |',         desc: 'Tabel' },
                                { syntax: '- item',            desc: 'Bullet list' },
                                { syntax: '> catatan',         desc: 'Blockquote' },
                                { syntax: '`kode`',            desc: 'Inline code' },
                                { syntax: '---',               desc: 'Garis pemisah' },
                            ]" :key="tip.syntax"
                                class="flex items-center justify-between gap-3 rounded-lg px-3 py-1.5 bg-[var(--surface-0)]">
                                <code class="text-[10px] text-violet-400 font-mono">{{ tip.syntax }}</code>
                                <span class="text-[10px] text-[var(--text-3)]">{{ tip.desc }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── Right Panel: Editor ──────────────────── -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Editor Header -->
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex gap-1.5">
                                <div class="h-2.5 w-2.5 rounded-full bg-red-500/70"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-amber-400/70"></div>
                                <div class="h-2.5 w-2.5 rounded-full bg-emerald-400/70"></div>
                            </div>
                            <span class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">
                                Editor Markdown
                            </span>
                        </div>
                        <button
                            type="button"
                            :class="[
                                'flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[10px] font-bold uppercase tracking-widest transition-all duration-200',
                                showPreview
                                    ? 'bg-violet-500/20 text-violet-400 border border-violet-500/30'
                                    : 'bg-[var(--surface-2)] text-[var(--text-3)] border border-[var(--border)] hover:text-[var(--text-2)]'
                            ]"
                            @click="showPreview = !showPreview"
                        >
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            {{ showPreview ? 'Editor' : 'Preview' }}
                        </button>
                    </div>

                    <!-- Textarea / Preview -->
                    <div class="relative rounded-2xl border border-[var(--border)] bg-[var(--surface-0)] shadow-[0_4px_20px_-10px_rgba(0,0,0,0.4)] overflow-hidden">
                        <textarea
                            v-if="!showPreview"
                            id="laporan-content"
                            v-model="content"
                            class="w-full resize-none bg-transparent px-5 py-4 text-[12.5px] text-[var(--text-1)] font-mono leading-relaxed outline-none placeholder-[var(--text-3)] scrollbar-thin scrollbar-track-transparent scrollbar-thumb-[var(--border)]"
                            style="min-height: 520px;"
                            placeholder="Tulis konten laporan dalam format Markdown di sini...

# Judul Laporan
## Sub-judul
| Kolom A | Kolom B |
|---------|---------|
| Data 1  | Data 2  |"
                            :maxlength="charLimit"
                            spellcheck="false"
                        ></textarea>

                        <!-- Simple Preview (text lines) -->
                        <div v-else class="px-5 py-4 space-y-1" style="min-height: 520px;">
                            <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest mb-3">Preview (30 baris pertama)</p>
                            <div v-for="(line, i) in previewLines" :key="i"
                                class="text-[12px] font-mono leading-relaxed"
                                :class="{
                                    'text-violet-400 font-black text-sm': line.startsWith('# ') && !line.startsWith('## '),
                                    'text-sky-400 font-bold': line.startsWith('## '),
                                    'text-teal-400': line.startsWith('### '),
                                    'text-[var(--text-3)]': line.startsWith('|'),
                                    'text-amber-400': line.startsWith('>'),
                                    'text-[var(--text-2)]': !line.startsWith('#') && !line.startsWith('|') && !line.startsWith('>'),
                                }"
                            >
                                <span v-if="line === ''" class="text-[var(--text-3)]">&nbsp;</span>
                                <span v-else>{{ line }}</span>
                            </div>
                            <div v-if="content.split('\n').length > 30" class="pt-2 text-[10px] text-[var(--text-3)] italic">
                                ... dan {{ content.split('\n').length - 30 }} baris lagi.
                            </div>
                        </div>
                    </div>

                    <!-- Feature Info -->
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div v-for="feat in [
                            { icon: '📊', label: 'Tabel Berwarna', desc: 'Tema Lawangsewu' },
                            { icon: '😀', label: 'Emoji Support', desc: 'Unicode native' },
                            { icon: '🎨', label: 'PDF Styled', desc: 'DomPDF engine' },
                            { icon: '📝', label: 'Markdown', desc: 'Parsedown parser' },
                        ]" :key="feat.label"
                            class="rounded-xl border border-[var(--border)] bg-[var(--surface-1)] px-3.5 py-3 text-center">
                            <div class="text-xl mb-1">{{ feat.icon }}</div>
                            <p class="text-[11px] font-bold text-[var(--text-1)]">{{ feat.label }}</p>
                            <p class="text-[9.5px] text-[var(--text-3)]">{{ feat.desc }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LawangsewuLayout>
</template>
