<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue'

const props = defineProps({
    appMeta: Object,
    navGroups: Array,
})

// ── State ─────────────────────────────────────────────────────────
const catatanKasar   = ref('')
const jenisPerkara   = ref('Cerai Gugat')
const narasiBas      = ref('')
const statusEditor   = ref('idle') // idle | generating | reviewing | ready | error
const errorMessage   = ref('')
const showReviewModal= ref(false)
const reviewResult   = ref({ temuan: '', rekomendasi: '', versiPerbaikan: '' })
const editorRef      = ref(null)

const isRecording    = ref(false)
let recognition      = null

const JENIS_PERKARA_OPTIONS = [
    'Cerai Gugat', 'Cerai Talak', 'Harta Bersama', 'Hadhanah', 'Waris', 'Lainnya',
]

const isLoading = computed(() => ['generating', 'reviewing'].includes(statusEditor.value))
const canExport = computed(() => narasiBas.value.trim().length > 20)
const canReview = computed(() => canExport.value && statusEditor.value !== 'generating')

// ── Speech Recognition (Voice to Text) ─────────────────────────────
onMounted(() => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
        recognition = new SpeechRecognition();
        recognition.continuous = true;
        recognition.interimResults = true;
        recognition.lang = 'id-ID';

        recognition.onresult = (event) => {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; ++i) {
                if (event.results[i].isFinal) {
                    finalTranscript += event.results[i][0].transcript + ' ';
                } else {
                    interimTranscript += event.results[i][0].transcript;
                }
            }

            if (finalTranscript) {
                // Tambahkan spasi di akhir kalimat jika belum ada
                const currentText = catatanKasar.value;
                const prefix = currentText.length > 0 && !currentText.endsWith(' ') && !currentText.endsWith('\n') ? ' ' : '';
                catatanKasar.value += prefix + finalTranscript;
            }
        };

        recognition.onerror = (event) => {
            console.error("Speech recognition error", event.error);
            isRecording.value = false;
        };

        recognition.onend = () => {
            isRecording.value = false;
        };
    }
})

onUnmounted(() => {
    if (recognition && isRecording.value) {
        recognition.stop();
    }
})

function toggleDictation() {
    if (!recognition) {
        alert('Browser Anda tidak mendukung fitur Voice-to-Text. Gunakan Chrome atau Edge terbaru.');
        return;
    }

    if (isRecording.value) {
        recognition.stop();
        isRecording.value = false;
    } else {
        recognition.start();
        isRecording.value = true;
    }
}

// ── Generate BAS ──────────────────────────────────────────────────
async function generateBas() {
    if (!catatanKasar.value.trim() || catatanKasar.value.trim().length < 10) {
        statusEditor.value = 'error'
        errorMessage.value = 'Catatan kasar minimal 10 karakter.'
        return
    }

    statusEditor.value = 'generating'
    errorMessage.value = ''

    try {
        const res = await fetch(route('lawangsewu.pakpp.generate'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ catatan_kasar: catatanKasar.value, jenis_perkara: jenisPerkara.value }),
        })
        const data = await res.json()

        if (!res.ok || !data.ok) {
            if (data.errorType === 'insufficient_data') {
                statusEditor.value = 'error'
                errorMessage.value = data.errorMessage
            } else {
                throw new Error(data.error || 'Gagal menghubungi AI.')
            }
            return
        }

        narasiBas.value = data.narasi
        statusEditor.value = 'ready'
    } catch (e) {
        statusEditor.value = 'error'
        errorMessage.value = e.message || 'Terjadi kesalahan jaringan.'
    }
}

// ── AI Review ─────────────────────────────────────────────────────
async function reviewBas() {
    const currentNarasi = editorRef.value?.innerText || narasiBas.value
    if (!currentNarasi.trim()) return

    statusEditor.value = 'reviewing'
    errorMessage.value = ''

    try {
        const res = await fetch(route('lawangsewu.pakpp.review'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            body: JSON.stringify({ narasi_bas: currentNarasi }),
        })
        const data = await res.json()

        if (!res.ok || !data.ok) throw new Error(data.error || 'Gagal memulai review.')

        reviewResult.value = {
            temuan: data.temuan,
            rekomendasi: data.rekomendasi,
            versiPerbaikan: data.versiPerbaikan,
        }
        statusEditor.value = 'ready'
        showReviewModal.value = true
    } catch (e) {
        statusEditor.value = 'ready'
        errorMessage.value = e.message
    }
}

// ── Terapkan Perbaikan dari Reviewer ─────────────────────────────
function applyRevision() {
    narasiBas.value = reviewResult.value.versiPerbaikan
    if (editorRef.value) editorRef.value.innerText = reviewResult.value.versiPerbaikan
    showReviewModal.value = false
}

// ── Export DOCX ───────────────────────────────────────────────────
async function exportDocx() {
    const currentNarasi = editorRef.value?.innerText || narasiBas.value
    submitFormDownload(route('lawangsewu.pakpp.export-docx'), { narasi_bas: currentNarasi, jenis_perkara: jenisPerkara.value })
}

// ── Export PDF ────────────────────────────────────────────────────
async function exportPdf() {
    const currentNarasi = editorRef.value?.innerText || narasiBas.value
    submitFormDownload(route('lawangsewu.pakpp.export-pdf'), { narasi_bas: currentNarasi, jenis_perkara: jenisPerkara.value })
}

// ── Helpers ───────────────────────────────────────────────────────
function getCsrf() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? ''
}

function submitFormDownload(url, fields) {
    const form = document.createElement('form')
    form.method = 'POST'
    form.action = url
    Object.entries({ ...fields, _token: getCsrf() }).forEach(([k, v]) => {
        const input = document.createElement('input')
        input.type = 'hidden'
        input.name = k
        input.value = v
        form.appendChild(input)
    })
    document.body.appendChild(form)
    form.submit()
    document.body.removeChild(form)
}

function onEditorInput(e) {
    narasiBas.value = e.target.innerText
}

function clearAll() {
    catatanKasar.value = ''
    narasiBas.value = ''
    if (editorRef.value) editorRef.value.innerText = ''
    statusEditor.value = 'idle'
    errorMessage.value = ''
}
</script>

<template>
    <Head title="PAK PP — Asisten Panitera Pengganti" />
    <LawangsewuLayout current-route="pakpp" :appMeta="appMeta" :navGroups="navGroups">

        <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-screen-2xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-violet-600 to-indigo-600 flex items-center justify-center text-white text-lg shadow-lg shadow-violet-500/30">
                            🤖
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-slate-100">PAK PP</h1>
                            <p class="text-xs text-slate-400">Personal Asisten Khusus Panitera Pengganti</p>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <button
                        id="btn-review-ai"
                        :disabled="!canReview || isLoading"
                        @click="reviewBas"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border transition-all duration-150"
                        :class="canReview && !isLoading
                            ? 'bg-amber-500/10 border-amber-500/40 text-amber-300 hover:bg-amber-500/20 cursor-pointer'
                            : 'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed'"
                    >
                        <span>🔍</span> Review AI
                    </button>
                    <button
                        id="btn-export-docx"
                        :disabled="!canExport || isLoading"
                        @click="exportDocx"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border transition-all duration-150"
                        :class="canExport && !isLoading
                            ? 'bg-blue-500/10 border-blue-500/40 text-blue-300 hover:bg-blue-500/20 cursor-pointer'
                            : 'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed'"
                    >
                        <span>📄</span> Export DOCX
                    </button>
                    <button
                        id="btn-export-pdf"
                        :disabled="!canExport || isLoading"
                        @click="exportPdf"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border transition-all duration-150"
                        :class="canExport && !isLoading
                            ? 'bg-rose-500/10 border-rose-500/40 text-rose-300 hover:bg-rose-500/20 cursor-pointer'
                            : 'bg-slate-800 border-slate-700 text-slate-500 cursor-not-allowed'"
                    >
                        <span>📕</span> Export PDF
                    </button>
                    <button
                        id="btn-clear-all"
                        @click="clearAll"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium rounded-lg border border-slate-700 text-slate-400 hover:text-slate-200 hover:border-slate-500 bg-slate-800/50 transition-all duration-150"
                    >
                        <span>🗑</span> Bersihkan
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 min-h-[70vh]">

                <div class="flex flex-col gap-4 bg-slate-900/50 border border-slate-700/50 rounded-2xl p-5 backdrop-blur-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-700/50">
                        <span class="text-slate-400 text-sm font-semibold uppercase tracking-widest">Input Catatan Sidang</span>
                        
                        <!-- Voice Dictation Toggle -->
                        <button
                            @click="toggleDictation"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg font-medium text-xs border transition-all"
                            :class="isRecording ? 'bg-rose-500/20 text-rose-300 border-rose-500/40 animate-pulse' : 'bg-slate-800 text-slate-300 border-slate-600 hover:bg-slate-700'"
                            title="Tekan untuk merekam suara (Dictation)"
                        >
                            <span v-if="isRecording">🎙️ Merekam Suara...</span>
                            <span v-else>🎙️ Input Suara (Dikte)</span>
                        </button>
                    </div>

                    <div>
                        <label for="select-jenis-perkara" class="block text-xs font-medium text-slate-400 mb-1.5">Jenis Perkara</label>
                        <select
                            id="select-jenis-perkara"
                            v-model="jenisPerkara"
                            :disabled="isLoading"
                            class="w-full bg-slate-800 border border-slate-600 text-slate-100 text-sm rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all disabled:opacity-50"
                        >
                            <option v-for="opt in JENIS_PERKARA_OPTIONS" :key="opt" :value="opt">{{ opt }}</option>
                        </select>
                    </div>

                    <div class="flex-1 flex flex-col">
                        <label for="textarea-catatan" class="block text-xs font-medium text-slate-400 mb-1.5">
                            Catatan Kasar Sidang
                            <span class="text-slate-600 font-normal ml-1">(singkatan, poin-poin, atau didiktekan)</span>
                        </label>
                        <textarea
                            id="textarea-catatan"
                            v-model="catatanKasar"
                            :disabled="isLoading"
                            rows="14"
                            placeholder="Ketik catatan kasar sidang di sini, atau tekan tombol Input Suara untuk mendikte..."
                            class="flex-1 w-full bg-slate-800/70 border border-slate-600 text-slate-100 text-sm rounded-xl px-4 py-3 placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 transition-all resize-none font-mono leading-relaxed disabled:opacity-50"
                            :class="isRecording ? 'ring-2 ring-rose-500/50 border-rose-500' : ''"
                        />
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-xs text-slate-600">{{ catatanKasar.length }} karakter</span>
                            <span class="text-xs text-slate-600">Maks 8.000 karakter</span>
                        </div>
                    </div>

                    <!-- Tombol Generate -->
                    <button
                        id="btn-generate-bas"
                        :disabled="isLoading || catatanKasar.trim().length < 10"
                        @click="generateBas"
                        class="w-full py-3 px-6 rounded-xl font-semibold text-sm transition-all duration-200 flex items-center justify-center gap-2"
                        :class="isLoading || catatanKasar.trim().length < 10
                            ? 'bg-slate-700 text-slate-500 cursor-not-allowed'
                            : 'bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500 text-white shadow-lg shadow-violet-500/30 hover:shadow-violet-500/50 hover:-translate-y-0.5 active:translate-y-0'"
                    >
                        <span v-if="statusEditor === 'generating'" class="animate-spin">⏳</span>
                        <span v-else>🚀</span>
                        <span v-if="statusEditor === 'generating'">Sedang Generate BAS...</span>
                        <span v-else>Generate Struktur BAS Otomatis</span>
                    </button>
                </div>

                <div class="flex flex-col gap-4 bg-slate-900/50 border border-slate-700/50 rounded-2xl p-5 backdrop-blur-sm">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-700/50">
                        <span class="text-slate-400 text-sm font-semibold uppercase tracking-widest">Editor BAS SIPP-Format</span>

                        <div class="flex items-center gap-2">
                            <span v-if="statusEditor === 'idle'" class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-slate-800 text-slate-500 border border-slate-700">
                                ⬜ Siap
                            </span>
                            <span v-else-if="statusEditor === 'generating'" class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-violet-500/10 text-violet-300 border border-violet-500/30 animate-pulse">
                                ⏳ Generating…
                            </span>
                            <span v-else-if="statusEditor === 'reviewing'" class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-amber-500/10 text-amber-300 border border-amber-500/30 animate-pulse">
                                🔍 Reviewing…
                            </span>
                            <span v-else-if="statusEditor === 'ready'" class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-emerald-500/10 text-emerald-300 border border-emerald-500/30">
                                ✅ Siap
                            </span>
                            <span v-else-if="statusEditor === 'error'" class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-rose-500/10 text-rose-300 border border-rose-500/30">
                                ⚠️ Periksa Input
                            </span>
                        </div>
                    </div>

                    <div v-if="isLoading" class="flex-1 flex flex-col items-center justify-center gap-4 min-h-64">
                        <div class="relative w-16 h-16">
                            <div class="absolute inset-0 rounded-full border-4 border-violet-500/20"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-t-violet-500 animate-spin"></div>
                        </div>
                        <div class="text-center">
                            <p class="text-slate-300 font-medium text-sm">
                                {{ statusEditor === 'generating' ? 'PAK Drafter sedang menyusun narasi…' : 'PAK Reviewer sedang menelaah draf…' }}
                            </p>
                            <p class="text-slate-600 text-xs mt-1">Harap tunggu, AI Agent sedang menyesuaikan template SIPP.</p>
                        </div>
                    </div>

                    <div v-else-if="statusEditor === 'error'" class="flex-1 flex flex-col gap-4">
                        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-sm">
                            <div class="font-semibold mb-1">⚠️ Tidak dapat diproses</div>
                            <div>{{ errorMessage }}</div>
                        </div>
                        <div v-if="narasiBas" class="flex-1">
                            <div
                                ref="editorRef"
                                id="editor-narasi-bas"
                                contenteditable="true"
                                @input="onEditorInput"
                                class="w-full min-h-full bg-slate-800/50 border border-slate-600 text-slate-100 text-sm rounded-xl p-4 focus:outline-none focus:ring-2 focus:ring-violet-500/50 leading-relaxed font-serif"
                                style="min-height: 300px; white-space: pre-wrap;"
                            >{{ narasiBas }}</div>
                        </div>
                    </div>

                    <div v-else-if="!narasiBas && statusEditor === 'idle'" class="flex-1 flex flex-col items-center justify-center gap-3 min-h-64 border-2 border-dashed border-slate-700 rounded-xl">
                        <div class="text-4xl opacity-30">📜</div>
                        <p class="text-slate-600 text-sm text-center">
                            Hasil narasi BAS sesuai format SIPP akan muncul di sini.<br>
                            <span class="text-slate-700">Masukkan/diktekan catatan di kiri, lalu tekan Generate.</span>
                        </p>
                    </div>

                    <div v-else-if="narasiBas" class="flex-1 flex flex-col gap-2">
                        <div class="flex items-center gap-2 text-xs text-slate-600">
                            <span>✏️ Teks dapat diedit langsung di bawah ini (SIPP Standard Format)</span>
                        </div>
                        <div
                            ref="editorRef"
                            id="editor-narasi-bas"
                            contenteditable="true"
                            @input="onEditorInput"
                            class="flex-1 w-full bg-slate-800/50 border border-slate-600 text-slate-100 text-sm rounded-xl p-5 focus:outline-none focus:ring-2 focus:ring-violet-500/50 focus:border-violet-500 leading-relaxed font-serif transition-all"
                            style="min-height: 380px; white-space: pre-wrap; line-height: 2;"
                        >{{ narasiBas }}</div>
                        <div class="flex justify-end">
                            <span class="text-xs text-slate-600">{{ narasiBas.length }} karakter</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Review Modal ──────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="showReviewModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);"
            >
                <div class="bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-700 bg-gradient-to-r from-amber-500/10 to-transparent">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">🔍</span>
                            <div>
                                <h2 class="text-slate-100 font-semibold text-base">Hasil Review PAK Reviewer</h2>
                                <p class="text-slate-500 text-xs">Ditelaah oleh Hakim Senior AI berdasarkan standar SIPP</p>
                            </div>
                        </div>
                        <button
                            id="btn-close-modal"
                            @click="showReviewModal = false"
                            class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-100 hover:bg-slate-700 transition-all"
                        >✕</button>
                    </div>

                    <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-widest text-rose-400 mb-2">🔴 Temuan</div>
                            <div class="bg-rose-500/5 border border-rose-500/20 rounded-xl p-4 text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">{{ reviewResult.temuan || 'Tidak ada temuan.' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-widest text-amber-400 mb-2">🟡 Rekomendasi</div>
                            <div class="bg-amber-500/5 border border-amber-500/20 rounded-xl p-4 text-sm text-slate-300 leading-relaxed whitespace-pre-wrap">{{ reviewResult.rekomendasi || '-' }}</div>
                        </div>
                        <div>
                            <div class="text-xs font-bold uppercase tracking-widest text-emerald-400 mb-2">🟢 Versi Perbaikan (Standar SIPP)</div>
                            <div class="bg-emerald-500/5 border border-emerald-500/20 rounded-xl p-4 text-sm text-slate-300 leading-relaxed whitespace-pre-wrap font-serif" style="line-height: 1.9;">{{ reviewResult.versiPerbaikan || '-' }}</div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-700 bg-slate-900/80">
                        <button
                            id="btn-close-review"
                            @click="showReviewModal = false"
                            class="px-4 py-2 text-sm text-slate-400 hover:text-slate-200 border border-slate-700 hover:border-slate-500 rounded-xl transition-all"
                        >Tutup</button>
                        <button
                            v-if="reviewResult.versiPerbaikan"
                            id="btn-apply-revision"
                            @click="applyRevision"
                            class="px-5 py-2 text-sm font-semibold bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white rounded-xl shadow-lg shadow-emerald-500/20 transition-all hover:-translate-y-0.5 active:translate-y-0"
                        >✅ Terapkan Perbaikan</button>
                    </div>
                </div>
            </div>
        </Teleport>

    </LawangsewuLayout>
</template>
