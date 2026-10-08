<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    appMeta:   { type: Object, default: () => ({}) },
    navGroups: { type: Array,  default: () => [] },
    schedules: { type: Array,  default: () => [] },
    assets:    { type: Array,  default: () => [] },
    authUser:  { type: Object, default: () => ({}) },
    filters:   { type: Object, default: () => ({}) },
});

const search       = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || '');
const schedules    = ref([...props.schedules]);
const showForm     = ref(false);
const formBusy     = ref(false);
const toast        = ref({ show: false, type: 'success', message: '' });

const form = ref({
    asset_id: '', maintenance_type: 'preventive', title: '',
    description: '', due_date: '', interval_days: '',
});

const filtered = computed(() => {
    let list = schedules.value;
    if (search.value.trim()) {
        const kw = search.value.toLowerCase();
        list = list.filter(s => [s.title, s.asset?.name].filter(Boolean).some(v => v.toLowerCase().includes(kw)));
    }
    if (statusFilter.value) list = list.filter(s => s.status === statusFilter.value);
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

const submitSchedule = async () => {
    formBusy.value = true;
    try {
        const result = await callApi('create-schedule', form.value);
        schedules.value.push(result.schedule);
        showForm.value = false;
        showToast('success', 'Jadwal perawatan berhasil ditambahkan.');
        form.value = { asset_id: '', maintenance_type: 'preventive', title: '', description: '', due_date: '', interval_days: '' };
    } catch (e) {
        showToast('error', e.message);
    } finally {
        formBusy.value = false;
    }
};

const markComplete = async (schedule) => {
    if (!confirm(`Tandai "${schedule.title}" sebagai selesai?`)) return;
    try {
        await callApi('complete-schedule', { id: schedule.id });
        const idx = schedules.value.findIndex(s => s.id === schedule.id);
        if (idx !== -1) schedules.value[idx] = { ...schedules.value[idx], status: 'completed', statusLabel: 'Selesai' };
        showToast('success', 'Jadwal ditandai selesai.');
    } catch (e) {
        showToast('error', e.message);
    }
};

const statusMeta = {
    pending:   { label: 'Menunggu', cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    overdue:   { label: 'Terlambat', cls: 'bg-rose-100 text-rose-700 border-rose-200' },
    completed: { label: 'Selesai', cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    cancelled: { label: 'Dibatalkan', cls: 'bg-slate-100 text-slate-600 border-slate-200' },
};

const badgeCls = (status) => `inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold border ${statusMeta[status]?.cls || 'bg-slate-100 text-slate-600'}`;
const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';
</script>

<template>
    <LawangsewuLayout :app-meta="appMeta" :nav-groups="navGroups">
        <Head title="TDMS — Jadwal Perawatan" />

        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-slate-900">📅 Jadwal Perawatan</h1>
                    <p class="mt-0.5 text-sm text-slate-500">{{ filtered.length }} jadwal ditemukan</p>
                </div>
                <button @click="showForm = true"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700">
                    + Tambah Jadwal
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <input v-model="search" type="text" placeholder="Cari judul, nama aset..."
                       class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs outline-none focus:border-sky-400 w-52" />
                <select v-model="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-sky-400">
                    <option value="">Semua Status</option>
                    <option v-for="(m, k) in statusMeta" :key="k" :value="k">{{ m.label }}</option>
                </select>
            </div>

            <!-- List -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div v-if="filtered.length === 0" class="py-16 text-center text-sm text-slate-400">
                    Belum ada jadwal perawatan yang terdaftar.
                </div>
                <table v-else class="w-full text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Judul</th>
                            <th class="px-4 py-3 text-left hidden sm:table-cell">Aset</th>
                            <th class="px-4 py-3 text-left">Jatuh Tempo</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="sch in filtered" :key="sch.id" class="hover:bg-slate-50/50 transition">
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ sch.title }}</p>
                                <p class="text-slate-400">{{ sch.maintenanceType }}
                                    <span v-if="sch.intervalDays" class="ml-1 text-sky-600">· Ulang tiap {{ sch.intervalDays }} hari</span>
                                </p>
                            </td>
                            <td class="hidden px-4 py-3 text-slate-600 sm:table-cell">{{ sch.asset?.name || '—' }}</td>
                            <td class="px-4 py-3 font-medium" :class="sch.status === 'overdue' ? 'text-rose-600' : 'text-slate-700'">
                                {{ formatDate(sch.dueDate) }}
                            </td>
                            <td class="px-4 py-3">
                                <span :class="badgeCls(sch.status)">{{ statusMeta[sch.status]?.label }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <button v-if="!['completed','cancelled'].includes(sch.status)"
                                        @click="markComplete(sch)"
                                        class="rounded-lg border border-emerald-200 px-2.5 py-1 text-[10px] font-bold text-emerald-700 hover:bg-emerald-50 transition">
                                    ✓ Selesai
                                </button>
                                <span v-else class="text-slate-400 text-[11px]">—</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Form -->
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-lg rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="font-black text-slate-900">📅 Tambah Jadwal Perawatan</h3>
                        <button @click="showForm = false" class="text-slate-400 hover:text-slate-700 text-xl">✕</button>
                    </div>
                    <form @submit.prevent="submitSchedule" class="space-y-4 p-6">
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Aset *</label>
                            <select v-model="form.asset_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400">
                                <option value="">Pilih aset...</option>
                                <option v-for="a in assets" :key="a.id" :value="a.id">{{ a.assetCode }} — {{ a.name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Tipe *</label>
                                <select v-model="form.maintenance_type" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400">
                                    <option value="preventive">Preventive</option>
                                    <option value="corrective">Corrective</option>
                                    <option value="inspection">Inspection</option>
                                    <option value="cleaning">Cleaning</option>
                                    <option value="backup">Backup</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Jatuh Tempo *</label>
                                <input v-model="form.due_date" required type="date" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Judul *</label>
                            <input v-model="form.title" required type="text" placeholder="misal: Bersihkan thermal paste CPU" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Deskripsi</label>
                            <textarea v-model="form.description" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400"></textarea>
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Ulang Tiap (hari, opsional)</label>
                            <input v-model="form.interval_days" type="number" min="1" placeholder="misal: 180 untuk setiap 6 bulan" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            <p class="mt-1 text-[11px] text-slate-400">Jika diisi, jadwal berikutnya akan dibuat otomatis setelah diselesaikan.</p>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showForm = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                            <button type="submit" :disabled="formBusy" class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-700 disabled:opacity-60">
                                {{ formBusy ? 'Menyimpan...' : 'Simpan Jadwal' }}
                            </button>
                        </div>
                    </form>
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
