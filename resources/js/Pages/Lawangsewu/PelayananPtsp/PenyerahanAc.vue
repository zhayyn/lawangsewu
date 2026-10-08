<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';

const props = defineProps({
    appMeta:    { type: Object, required: true },
    navGroups:  { type: Array,  default: () => [] },
    penyerahan: { type: Array,  default: () => [] },
    tanggal:    { type: String, default: '' },
    canOperate: { type: Boolean, default: false },
    flash:      { type: Object, default: () => ({}) },
});

// ── State ──────────────────────────────────────────────────────────────────
const showModal   = ref(false);
const saving      = ref(false);
const sippLoading = ref(false);
const sippData    = ref(null);
const sippError   = ref('');
const cameraActive = ref(false);
const capturedPhoto = ref('');   // base64
const flashMsg    = ref(props.flash?.status ?? '');

// Camera
let videoStream = null;
const videoRef  = ref(null);
const canvasRef = ref(null);

const form = ref({
    nomor_perkara:      '',
    nomor_ac:           '',
    tanggal_bht:        '',
    tanggal_penyerahan: props.tanggal,
    jenis:              'ac',
    nama_penerima:      '',
    pihak_penerima:     'pihak1',
    nik_penerima:       '',
    catatan:            '',
    foto_base64:        '',
});

const jenisMap = {
    ac:        'Penyerahan Akta Cerai (AC)',
    salput:    'Penyerahan Salinan Putusan',
    ac_salput: 'Penyerahan AC & Salinan Putusan',
};

// ── SIPP Lookup ────────────────────────────────────────────────────────────
let lookupTimer = null;
function onNomorChange() {
    sippData.value  = null;
    sippError.value = '';
    clearTimeout(lookupTimer);
    if (form.value.nomor_perkara.length < 5) return;
    lookupTimer = setTimeout(lookupSipp, 700);
}

async function lookupSipp() {
    sippLoading.value = true;
    try {
        const res = await fetch(`/pelayanan-ptsp/api/sipp-lookup?nomor=${encodeURIComponent(form.value.nomor_perkara)}`);
        const json = await res.json();
        if (json.ok) {
            sippData.value = json.data;
            // Auto-isi dari SIPP
            if (json.data.nomor_akta_cerai) form.value.nomor_ac = json.data.nomor_akta_cerai;
            if (json.data.tanggal_bht)      form.value.tanggal_bht = toInputDate(json.data.tanggal_bht);
        } else {
            sippError.value = json.msg;
        }
    } catch {
        sippError.value = 'Gagal menghubungi server.';
    } finally {
        sippLoading.value = false;
    }
}

function toInputDate(ddmmyyyy) {
    if (!ddmmyyyy) return '';
    const [d, m, y] = ddmmyyyy.split('/');
    return `${y}-${m}-${d}`;
}

// ── Camera ─────────────────────────────────────────────────────────────────
async function startCamera() {
    try {
        videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
        if (videoRef.value) videoRef.value.srcObject = videoStream;
        cameraActive.value = true;
    } catch {
        alert('Kamera tidak dapat diakses. Pastikan izin kamera diberikan.');
    }
}

function stopCamera() {
    videoStream?.getTracks().forEach(t => t.stop());
    videoStream       = null;
    cameraActive.value = false;
}

function capturePhoto() {
    if (!videoRef.value || !canvasRef.value) return;
    const canvas = canvasRef.value;
    canvas.width  = videoRef.value.videoWidth;
    canvas.height = videoRef.value.videoHeight;
    canvas.getContext('2d').drawImage(videoRef.value, 0, 0);
    capturedPhoto.value   = canvas.toDataURL('image/jpeg', 0.85);
    form.value.foto_base64 = capturedPhoto.value;
    stopCamera();
}

function retakePhoto() {
    capturedPhoto.value    = '';
    form.value.foto_base64 = '';
    startCamera();
}

// ── Submit ─────────────────────────────────────────────────────────────────
async function submitForm() {
    saving.value = true;
    try {
        const res = await fetch('/pelayanan-ptsp/penyerahan-ac', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'Accept': 'application/json',
            },
            body: JSON.stringify(form.value),
        });
        const json = await res.json();
        if (json.ok) {
            flashMsg.value = 'Penyerahan berhasil disimpan.';
            closeModal();
            router.reload({ only: ['penyerahan'] });
        } else {
            alert('Gagal menyimpan: ' + (json.message ?? json.msg));
        }
    } catch {
        alert('Terjadi kesalahan jaringan.');
    } finally {
        saving.value = false;
    }
}

function openModal() {
    form.value = {
        nomor_perkara: '', nomor_ac: '', tanggal_bht: '',
        tanggal_penyerahan: props.tanggal, jenis: 'ac',
        nama_penerima: '', pihak_penerima: 'pihak1',
        nik_penerima: '', catatan: '', foto_base64: '',
    };
    sippData.value  = null;
    sippError.value = '';
    capturedPhoto.value = '';
    cameraActive.value  = false;
    showModal.value = true;
}

function closeModal() {
    stopCamera();
    showModal.value = false;
}

onUnmounted(() => stopCamera());
</script>

<template>
    <Head title="Penyerahan AC / Salinan Putusan" />
    <LawangsewuLayout current-route="pelayanan-ptsp" :nav-groups="navGroups" :app-meta="appMeta">

        <!-- ── Header ────────────────────────────────────────────────────── -->
        <section class="card-surface p-6 lg:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-1.5">
                    <a href="/pelayanan-ptsp" class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:underline">
                        ← Kembali ke Dashboard
                    </a>
                    <h1 class="text-2xl font-bold text-[var(--text-1)]">Penyerahan AC &amp; Salinan Putusan</h1>
                    <p class="text-sm text-[var(--text-2)]">Rekam penyerahan Akta Cerai (AC) dan Salinan Putusan, terintegrasi SIPP. Data tersimpan di database lokal.</p>
                </div>
                <button v-if="canOperate" @click="openModal"
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500 active:scale-95">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Catat Penyerahan
                </button>
            </div>
        </section>

        <!-- Flash -->
        <div v-if="flashMsg" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-3.5 text-sm font-medium text-emerald-300">
            {{ flashMsg }}
        </div>

        <!-- ── Tabel ──────────────────────────────────────────────────────── -->
        <section class="card-surface p-6">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="text-base font-semibold text-[var(--text-1)]">
                    Riwayat Penyerahan — {{ tanggal }}
                </h2>
                <span class="rounded-full bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400">
                    {{ penyerahan.length }} record
                </span>
            </div>

            <div v-if="penyerahan.length === 0" class="py-14 text-center text-sm text-[var(--text-3)]">
                Belum ada penyerahan pada tanggal ini.
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--border)] text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--text-3)]">
                            <th class="pb-3 text-left">No. Perkara</th>
                            <th class="pb-3 text-left">No. AC</th>
                            <th class="pb-3 text-left">Tgl. BHT</th>
                            <th class="pb-3 text-left">Jenis</th>
                            <th class="pb-3 text-left">Penerima</th>
                            <th class="pb-3 text-left">Foto</th>
                            <th class="pb-3 text-left">Petugas</th>
                            <th class="pb-3 text-left">Jam</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <tr v-for="p in penyerahan" :key="p.id" class="transition hover:bg-[var(--surface-2)]">
                            <td class="py-3 pr-4 font-mono text-xs text-[var(--text-1)]">{{ p.nomor_perkara }}</td>
                            <td class="py-3 pr-4 text-xs text-[var(--text-2)]">{{ p.nomor_ac || '—' }}</td>
                            <td class="py-3 pr-4 text-xs text-[var(--text-2)]">{{ p.tanggal_bht || '—' }}</td>
                            <td class="py-3 pr-4">
                                <span class="rounded-full bg-amber-500/10 px-2 py-0.5 text-[10px] font-semibold text-amber-400">
                                    {{ { ac: 'AC', salput: 'Salput', ac_salput: 'AC+Salput' }[p.jenis] }}
                                </span>
                            </td>
                            <td class="py-3 pr-4">
                                <p class="text-xs font-medium text-[var(--text-1)]">{{ p.nama_penerima || '—' }}</p>
                                <p class="text-[10px] text-[var(--text-3)]">{{ p.pihak_penerima }}</p>
                            </td>
                            <td class="py-3 pr-4">
                                <a v-if="p.foto_path" :href="p.foto_path" target="_blank"
                                   class="inline-flex items-center gap-1 text-[10px] text-indigo-400 hover:underline">
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                    Lihat
                                </a>
                                <span v-else class="text-[10px] text-[var(--text-3)]">—</span>
                            </td>
                            <td class="py-3 pr-4 text-xs text-[var(--text-2)]">{{ p.petugas || '—' }}</td>
                            <td class="py-3 text-xs text-[var(--text-3)]">{{ p.created_at }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- ── Modal Catat Penyerahan ──────────────────────────────────────── -->
        <Teleport to="body">
            <Transition enter-from-class="opacity-0" enter-to-class="opacity-100"
                        leave-from-class="opacity-100" leave-to-class="opacity-0"
                        enter-active-class="transition duration-200" leave-active-class="transition duration-150">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" @click.self="closeModal">
                    <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-2xl">

                        <div class="mb-5 flex items-center justify-between">
                            <h3 class="text-lg font-bold text-[var(--text-1)]">Catat Penyerahan AC / Salinan Putusan</h3>
                            <button @click="closeModal" class="rounded-lg p-1.5 text-[var(--text-3)] hover:bg-[var(--surface-2)]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <form class="space-y-4" @submit.prevent="submitForm">
                            <!-- Nomor Perkara + SIPP lookup -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Nomor Perkara *</label>
                                <input v-model="form.nomor_perkara" @input="onNomorChange"
                                       placeholder="0001/Pdt.G/2025/PA.Smg" required maxlength="80"
                                       class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                <div v-if="sippLoading" class="mt-2 text-xs text-[var(--text-3)]">🔍 Mencari di SIPP…</div>
                                <div v-if="sippError" class="mt-2 text-xs text-red-400">⚠ {{ sippError }}</div>
                                <div v-if="sippData" class="mt-2 rounded-xl border border-emerald-500/20 bg-emerald-500/8 p-3 text-xs space-y-1">
                                    <p class="font-semibold text-emerald-400">✓ Data SIPP ditemukan</p>
                                    <p class="text-[var(--text-2)]">{{ sippData.pihak1_text }} vs {{ sippData.pihak2_text }}</p>
                                    <p class="text-[var(--text-3)]">{{ sippData.jenis_perkara_text }} · BHT: {{ sippData.tanggal_bht || '—' }} · No. AC: {{ sippData.nomor_akta_cerai || '—' }}</p>
                                </div>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Nomor AC</label>
                                    <input v-model="form.nomor_ac" maxlength="60" placeholder="Auto dari SIPP"
                                           class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Tanggal BHT</label>
                                    <input v-model="form.tanggal_bht" type="date"
                                           class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Tanggal Penyerahan *</label>
                                    <input v-model="form.tanggal_penyerahan" type="date" required
                                           class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Jenis *</label>
                                    <select v-model="form.jenis"
                                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                        <option value="ac">Penyerahan AC</option>
                                        <option value="salput">Salinan Putusan Saja</option>
                                        <option value="ac_salput">AC &amp; Salinan Putusan</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Nama Penerima</label>
                                    <input v-model="form.nama_penerima" maxlength="120" placeholder="Nama penerima"
                                           class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Pihak Penerima</label>
                                    <select v-model="form.pihak_penerima"
                                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                        <option value="pihak1">Pihak I (Penggugat)</option>
                                        <option value="pihak2">Pihak II (Tergugat)</option>
                                        <option value="kuasa">Kuasa Hukum</option>
                                        <option value="lainnya">Lainnya</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">NIK Penerima</label>
                                    <input v-model="form.nik_penerima" maxlength="20" placeholder="Nomor KTP penerima"
                                           class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20">
                                </div>
                            </div>

                            <!-- ── Kamera ── -->
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-2">Foto Penerima (via Kamera)</label>
                                <div v-if="!cameraActive && !capturedPhoto" class="flex gap-2">
                                    <button type="button" @click="startCamera"
                                            class="inline-flex items-center gap-2 rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-xs font-semibold text-amber-400 transition hover:bg-amber-500/20">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><circle cx="12" cy="13" r="3"/></svg>
                                        Buka Kamera
                                    </button>
                                    <span class="self-center text-xs text-[var(--text-3)]">Opsional — untuk dokumentasi</span>
                                </div>
                                <div v-if="cameraActive" class="space-y-2">
                                    <div class="relative overflow-hidden rounded-xl bg-black aspect-video max-h-60">
                                        <video ref="videoRef" autoplay playsinline class="h-full w-full object-cover" />
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" @click="capturePhoto"
                                                class="flex-1 rounded-xl bg-emerald-600 py-2 text-xs font-semibold text-white hover:bg-emerald-500">
                                            📸 Ambil Foto
                                        </button>
                                        <button type="button" @click="stopCamera"
                                                class="rounded-xl border border-[var(--border)] px-4 py-2 text-xs font-semibold text-[var(--text-2)] hover:bg-[var(--surface-2)]">
                                            Batal
                                        </button>
                                    </div>
                                </div>
                                <div v-if="capturedPhoto" class="space-y-2">
                                    <img :src="capturedPhoto" class="w-full max-h-48 rounded-xl object-cover border border-[var(--border)]" />
                                    <button type="button" @click="retakePhoto"
                                            class="text-xs text-indigo-400 hover:underline">🔄 Ulangi Foto</button>
                                </div>
                                <canvas ref="canvasRef" class="hidden" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)] mb-1.5">Catatan</label>
                                <textarea v-model="form.catatan" rows="2" maxlength="500" placeholder="Catatan tambahan…"
                                          class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-amber-500/60 focus:outline-none focus:ring-2 focus:ring-amber-500/20 resize-none" />
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="submit" :disabled="saving"
                                        class="flex-1 rounded-xl bg-amber-600 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-500 disabled:opacity-60">
                                    {{ saving ? 'Menyimpan…' : '💾 Simpan Penyerahan' }}
                                </button>
                                <button type="button" @click="closeModal"
                                        class="rounded-xl border border-[var(--border)] px-5 py-2.5 text-sm font-semibold text-[var(--text-2)] hover:bg-[var(--surface-2)]">
                                    Batal
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </LawangsewuLayout>
</template>
