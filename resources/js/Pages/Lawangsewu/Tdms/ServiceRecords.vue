<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    appMeta:   { type: Object, default: () => ({}) },
    navGroups: { type: Array,  default: () => [] },
    records:   { type: Array,  default: () => [] },
    assets:    { type: Array,  default: () => [] },
    authUser:  { type: Object, default: () => ({}) },
    filters:   { type: Object, default: () => ({}) },
});

const search     = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const records    = ref([...props.records]);
const showForm   = ref(false);
const formBusy   = ref(false);
const activeRec  = ref(null); // record yang sedang dibuka detailnya
const toast      = ref({ show: false, type: 'success', message: '' });

const form = ref({
    asset_id: '', title: '', issue_description: '', priority: 'medium',
});
const updateForm = ref({
    id: null, status: '', action_taken: '', repair_cost: '', technician_id: '',
    parts_replaced: [],
});

const filtered = computed(() => {
    let list = records.value;
    if (search.value.trim()) {
        const kw = search.value.toLowerCase();
        list = list.filter(r => [r.ticketNumber, r.title, r.asset?.name].filter(Boolean).some(v => v.toLowerCase().includes(kw)));
    }
    if (statusFilter.value) list = list.filter(r => r.status === statusFilter.value);
    return list;
});

const showToast = (type, msg) => {
    toast.value = { show: true, type, message: msg };
    setTimeout(() => toast.value.show = false, 3500);
};

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content;
const callApi = async (action, data) => {
    const res = await fetch(route('lawangsewu.tdms.api', { action }), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        body: JSON.stringify(data),
    });
    const json = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(json.error || 'Terjadi kesalahan.');
    return json;
};

const submitTicket = async () => {
    formBusy.value = true;
    try {
        const result = await callApi('create-service-record', form.value);
        records.value.unshift(result.record);
        showForm.value = false;
        showToast('success', `Tiket ${result.record.ticketNumber} berhasil dibuat.`);
        form.value = { asset_id: '', title: '', issue_description: '', priority: 'medium' };
    } catch (e) {
        showToast('error', e.message);
    } finally {
        formBusy.value = false;
    }
};

const openDetail = (rec) => {
    activeRec.value = rec;
    updateForm.value = { id: rec.id, status: rec.status, action_taken: rec.actionTaken || '', repair_cost: rec.repairCost || '', technician_id: '', parts_replaced: rec.partsReplaced || [] };
};

const saveUpdate = async () => {
    formBusy.value = true;
    try {
        const result = await callApi('update-service-record', updateForm.value);
        const idx = records.value.findIndex(r => r.id === updateForm.value.id);
        if (idx !== -1) records.value[idx] = result.record;
        activeRec.value = result.record;
        showToast('success', 'Tiket berhasil diperbarui.');
    } catch (e) {
        showToast('error', e.message);
    } finally {
        formBusy.value = false;
    }
};

const statusMeta = {
    open:          { label: 'Buka',                cls: 'bg-sky-100 text-sky-700 border-sky-200' },
    in_progress:   { label: 'Dalam Pengerjaan',    cls: 'bg-violet-100 text-violet-700 border-violet-200' },
    waiting_parts: { label: 'Menunggu Sparepart',  cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    resolved:      { label: 'Selesai',             cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    closed:        { label: 'Ditutup',             cls: 'bg-slate-100 text-slate-600 border-slate-200' },
    cancelled:     { label: 'Dibatalkan',          cls: 'bg-rose-100 text-rose-700 border-rose-200' },
};
const priorityMeta = {
    critical: { label: 'Kritis', cls: 'bg-rose-100 text-rose-700 border-rose-200' },
    high:     { label: 'Tinggi', cls: 'bg-orange-100 text-orange-700 border-orange-200' },
    medium:   { label: 'Sedang', cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    low:      { label: 'Rendah', cls: 'bg-slate-100 text-slate-600 border-slate-200' },
};
const badgeCls = (meta, val) => `inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold border ${meta[val]?.cls || 'bg-slate-100 text-slate-600 border-slate-200'}`;

const formatDt = (iso) => iso ? new Date(iso).toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '—';
</script>

<template>
    <LawangsewuLayout :app-meta="appMeta" :nav-groups="navGroups">
        <Head title="TDMS — Tiket Perbaikan" />

        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-slate-900">🎫 Tiket Perbaikan</h1>
                    <p class="mt-0.5 text-sm text-slate-500">{{ filtered.length }} tiket ditemukan</p>
                </div>
                <button @click="showForm = true"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-violet-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-violet-700">
                    + Buka Tiket Baru
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <input v-model="search" type="text" placeholder="Cari nomor tiket, judul..."
                       class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs outline-none focus:border-sky-400 w-52" />
                <select v-model="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-sky-400">
                    <option value="">Semua Status</option>
                    <option v-for="(m, k) in statusMeta" :key="k" :value="k">{{ m.label }}</option>
                </select>
            </div>

            <!-- Kanban-style list -->
            <div class="space-y-3">
                <div v-if="filtered.length === 0" class="rounded-2xl border border-dashed border-slate-200 py-16 text-center text-sm text-slate-400">
                    Belum ada tiket perbaikan.
                </div>
                <div v-for="rec in filtered" :key="rec.id"
                     @click="openDetail(rec)"
                     class="cursor-pointer rounded-2xl border border-slate-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:border-sky-300">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="font-mono text-[11px] font-bold text-slate-500">{{ rec.ticketNumber }}</span>
                                <span :class="badgeCls(statusMeta, rec.status)">{{ statusMeta[rec.status]?.label }}</span>
                                <span :class="badgeCls(priorityMeta, rec.priority)">{{ priorityMeta[rec.priority]?.label }}</span>
                                <span v-if="rec.isBreached" class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200">⚠ SLA Breach</span>
                            </div>
                            <p class="font-bold text-slate-900">{{ rec.title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500 line-clamp-1">{{ rec.issueDescription }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-xs font-semibold text-slate-700">{{ rec.asset?.name || '—' }}</p>
                            <p class="text-[11px] text-slate-400">{{ rec.reporter?.alias || rec.reporter?.name || '—' }}</p>
                            <p class="mt-1 text-[11px] text-slate-400">SLA: {{ formatDt(rec.slaDeadline) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Buka Tiket Baru -->
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="font-black text-slate-900">🎫 Buka Tiket Perbaikan</h3>
                        <button @click="showForm = false" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
                    </div>
                    <form @submit.prevent="submitTicket" class="space-y-4 p-6">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Aset yang Bermasalah *</label>
                            <select v-model="form.asset_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400">
                                <option value="">Pilih aset...</option>
                                <option v-for="a in assets" :key="a.id" :value="a.id">{{ a.assetCode }} — {{ a.name }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Judul Masalah *</label>
                            <input v-model="form.title" required type="text" placeholder="Deskripsi singkat masalah" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Deskripsi Lengkap *</label>
                            <textarea v-model="form.issue_description" required rows="3" placeholder="Jelaskan gejala kerusakan secara detail..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Prioritas</label>
                            <div class="flex gap-2 flex-wrap">
                                <label v-for="(m, k) in priorityMeta" :key="k"
                                       class="flex cursor-pointer items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition"
                                       :class="form.priority === k ? m.cls + ' !border-current shadow-sm' : 'border-slate-200 text-slate-600 hover:bg-slate-50'">
                                    <input v-model="form.priority" :value="k" type="radio" class="sr-only" />
                                    {{ m.label }}
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showForm = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                            <button type="submit" :disabled="formBusy" class="rounded-xl bg-violet-600 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-violet-700 disabled:opacity-60">
                                {{ formBusy ? 'Membuat...' : 'Buka Tiket' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- Modal: Detail & Update Tiket -->
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
            <div v-if="activeRec" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="activeRec = null">
                <div class="w-full max-w-xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <div>
                            <p class="font-mono text-xs font-bold text-slate-500">{{ activeRec.ticketNumber }}</p>
                            <h3 class="font-black text-slate-900">{{ activeRec.title }}</h3>
                        </div>
                        <button @click="activeRec = null" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="flex flex-wrap gap-2">
                            <span :class="badgeCls(statusMeta, activeRec.status)">{{ statusMeta[activeRec.status]?.label }}</span>
                            <span :class="badgeCls(priorityMeta, activeRec.priority)">{{ priorityMeta[activeRec.priority]?.label }}</span>
                            <span v-if="activeRec.isBreached" class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200">⚠ SLA Breach</span>
                        </div>
                        <div class="rounded-xl bg-slate-50 p-4 text-sm text-slate-700 space-y-1">
                            <p><span class="font-bold">Aset:</span> {{ activeRec.asset?.assetCode }} — {{ activeRec.asset?.name }}</p>
                            <p><span class="font-bold">Pelapor:</span> {{ activeRec.reporter?.alias || activeRec.reporter?.name }}</p>
                            <p><span class="font-bold">SLA:</span> {{ formatDt(activeRec.slaDeadline) }}</p>
                            <p class="mt-2 whitespace-pre-wrap">{{ activeRec.issueDescription }}</p>
                        </div>
                        <!-- Update Form -->
                        <form @submit.prevent="saveUpdate" class="space-y-3 border-t border-slate-100 pt-4">
                            <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500">Update Status Tiket</h4>
                            <select v-model="updateForm.status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400">
                                <option v-for="(m, k) in statusMeta" :key="k" :value="k">{{ m.label }}</option>
                            </select>
                            <textarea v-model="updateForm.action_taken" rows="2" placeholder="Tindakan yang diambil..." class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400"></textarea>
                            <input v-model="updateForm.repair_cost" type="number" min="0" placeholder="Biaya perbaikan (Rp)" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            <div class="flex justify-end">
                                <button type="submit" :disabled="formBusy" class="rounded-xl bg-sky-600 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:opacity-60">
                                    {{ formBusy ? 'Menyimpan...' : 'Simpan Update' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- Toast -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" leave-to-class="opacity-0 translate-y-2">
            <div v-if="toast.show" class="fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold shadow-xl"
                 :class="toast.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'">
                {{ toast.type === 'success' ? '✓' : '✕' }} {{ toast.message }}
            </div>
        </Transition>
    </LawangsewuLayout>
</template>
