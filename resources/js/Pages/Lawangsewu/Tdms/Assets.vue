<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    appMeta:    { type: Object, default: () => ({}) },
    navGroups:  { type: Array,  default: () => [] },
    assets:     { type: Array,  default: () => [] },
    categories: { type: Array,  default: () => [] },
    filters:    { type: Object, default: () => ({}) },
});

// ─── State ────────────────────────────────────────
const search      = ref(props.filters?.search || '');
const catFilter   = ref(props.filters?.category || '');
const statusFilter = ref(props.filters?.status || '');
const assets      = ref([...props.assets]);

const showForm    = ref(false);
const editTarget  = ref(null);
const formBusy    = ref(false);
const toast       = ref({ show: false, type: 'success', message: '' });
const showQr      = ref(null); // asset token untuk tampilkan modal QR

const form = ref({
    id: null, category_id: '', name: '', brand: '', model: '',
    serial_number: '', location: '', assigned_to: '', notes: '',
    purchase_date: '', purchase_price: '', status: 'active', specifications: {},
});

const specInput = ref(''); // JSON editor sederhana

// ─── Computed ─────────────────────────────────────
const filtered = computed(() => {
    let list = assets.value;
    if (search.value.trim()) {
        const kw = search.value.toLowerCase();
        list = list.filter(a =>
            [a.assetCode, a.name, a.brand, a.model, a.location, a.assignedTo]
                .filter(Boolean).some(v => v.toLowerCase().includes(kw)));
    }
    if (catFilter.value) list = list.filter(a => a.category?.id == catFilter.value);
    if (statusFilter.value) list = list.filter(a => a.status === statusFilter.value);
    return list;
});

// ─── Helpers ──────────────────────────────────────
const showToast = (type, msg) => {
    toast.value = { show: true, type, message: msg };
    setTimeout(() => toast.value.show = false, 3500);
};

const statusMeta = {
    active:      { label: 'Aktif',              cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    maintenance: { label: 'Dalam Servis',        cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    broken:      { label: 'Rusak',               cls: 'bg-rose-100 text-rose-700 border-rose-200' },
    retired:     { label: 'Pensiun',             cls: 'bg-slate-100 text-slate-600 border-slate-200' },
};

const badgeCls = (status) => `inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold border ${statusMeta[status]?.cls || 'bg-slate-100 text-slate-600 border-slate-200'}`;

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

// ─── Form Actions ─────────────────────────────────
const openCreate = () => {
    editTarget.value = null;
    form.value = { id: null, category_id: props.categories[0]?.id || '', name: '', brand: '', model: '', serial_number: '', location: '', assigned_to: '', notes: '', purchase_date: '', purchase_price: '', status: 'active', specifications: {} };
    specInput.value = '';
    showForm.value = true;
};

const openEdit = (asset) => {
    editTarget.value = asset;
    form.value = {
        id: asset.id, category_id: asset.category?.id || '', name: asset.name,
        brand: asset.brand || '', model: asset.model || '', serial_number: asset.serialNumber || '',
        location: asset.location || '', assigned_to: asset.assignedTo || '', notes: asset.notes || '',
        purchase_date: asset.purchaseDate || '', purchase_price: asset.purchasePrice || '',
        status: asset.status, specifications: asset.specifications || {},
    };
    specInput.value = Object.keys(asset.specifications || {}).length ? JSON.stringify(asset.specifications, null, 2) : '';
    showForm.value = true;
};

const saveForm = async () => {
    formBusy.value = true;
    try {
        let specs = {};
        if (specInput.value.trim()) {
            try { specs = JSON.parse(specInput.value); } catch { specs = {}; }
        }
        const payload = { ...form.value, specifications: specs };
        const action = form.value.id ? 'update-asset' : 'create-asset';
        const result = await callApi(action, payload);
        if (form.value.id) {
            const idx = assets.value.findIndex(a => a.id === form.value.id);
            if (idx !== -1) assets.value[idx] = result.asset;
        } else {
            assets.value.unshift(result.asset);
        }
        showForm.value = false;
        showToast('success', form.value.id ? 'Aset berhasil diperbarui.' : 'Aset baru berhasil ditambahkan.');
    } catch (e) {
        showToast('error', e.message);
    } finally {
        formBusy.value = false;
    }
};

const deleteAsset = async (asset) => {
    if (!confirm(`Hapus aset "${asset.name}" (${asset.assetCode})?`)) return;
    try {
        await callApi('delete-asset', { id: asset.id });
        assets.value = assets.value.filter(a => a.id !== asset.id);
        showToast('success', 'Aset dihapus.');
    } catch (e) {
        showToast('error', e.message);
    }
};

const printQr = (token) => {
    window.open(route('lawangsewu.tdms.qr', { token }), '_blank');
};
</script>

<template>
    <LawangsewuLayout :app-meta="appMeta" :nav-groups="navGroups">
        <Head title="TDMS — Inventaris Aset" />

        <div class="mx-auto max-w-7xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-black tracking-tight text-slate-900">📦 Inventaris Aset IT</h1>
                    <p class="mt-0.5 text-sm text-slate-500">{{ filtered.length }} aset ditemukan</p>
                </div>
                <button @click="openCreate"
                        class="inline-flex items-center gap-1.5 rounded-xl bg-sky-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700">
                    + Tambah Aset
                </button>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-2">
                <input v-model="search" type="text" placeholder="Cari nama, kode, lokasi..."
                       class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs outline-none focus:border-sky-400 w-52" />
                <select v-model="catFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-sky-400">
                    <option value="">Semua Kategori</option>
                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                </select>
                <select v-model="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs outline-none focus:border-sky-400">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif</option>
                    <option value="maintenance">Dalam Servis</option>
                    <option value="broken">Rusak</option>
                    <option value="retired">Pensiun</option>
                </select>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div v-if="filtered.length === 0" class="py-16 text-center text-sm text-slate-400">
                    Belum ada aset yang terdaftar.
                </div>
                <table v-else class="w-full text-xs">
                    <thead class="border-b border-slate-100 bg-slate-50 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3 text-left">Kode</th>
                            <th class="px-4 py-3 text-left">Nama / Kategori</th>
                            <th class="px-4 py-3 text-left hidden sm:table-cell">Merek / Model</th>
                            <th class="px-4 py-3 text-left hidden md:table-cell">Lokasi</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="asset in filtered" :key="asset.id" class="hover:bg-slate-50/50 transition">
                            <td class="px-4 py-3 font-mono font-bold text-slate-600">{{ asset.assetCode }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ asset.name }}</p>
                                <p class="text-slate-400">{{ asset.category?.icon }} {{ asset.category?.name }}</p>
                            </td>
                            <td class="hidden px-4 py-3 text-slate-600 sm:table-cell">{{ [asset.brand, asset.model].filter(Boolean).join(' · ') || '—' }}</td>
                            <td class="hidden px-4 py-3 text-slate-600 md:table-cell">{{ asset.location || '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="badgeCls(asset.status)">{{ statusMeta[asset.status]?.label }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button @click="printQr(asset.qrToken)" title="Lihat QR"
                                            class="rounded-lg border border-slate-200 px-2 py-1 text-[10px] font-bold text-slate-600 hover:bg-slate-100 transition">QR</button>
                                    <button @click="openEdit(asset)"
                                            class="rounded-lg border border-sky-200 px-2 py-1 text-[10px] font-bold text-sky-700 hover:bg-sky-50 transition">Edit</button>
                                    <button @click="deleteAsset(asset)"
                                            class="rounded-lg border border-rose-200 px-2 py-1 text-[10px] font-bold text-rose-700 hover:bg-rose-50 transition">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal Form -->
        <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" leave-to-class="opacity-0">
            <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="showForm = false">
                <div class="w-full max-w-2xl max-h-[90vh] overflow-y-auto rounded-3xl bg-white shadow-2xl">
                    <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                        <h3 class="font-black text-slate-900">{{ editTarget ? 'Edit Aset' : 'Tambah Aset Baru' }}</h3>
                        <button @click="showForm = false" class="text-slate-400 hover:text-slate-700 text-xl leading-none">✕</button>
                    </div>
                    <form @submit.prevent="saveForm" class="space-y-4 p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Kategori *</label>
                                <select v-model="form.category_id" required class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400">
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.icon }} {{ c.name }}</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Nama Aset *</label>
                                <input v-model="form.name" required type="text" placeholder="misal: PC HP EliteDesk 800 G6" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Merek</label>
                                <input v-model="form.brand" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Model / Tipe</label>
                                <input v-model="form.model" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Serial Number</label>
                                <input v-model="form.serial_number" type="text" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Status</label>
                                <select v-model="form.status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400">
                                    <option value="active">Aktif</option>
                                    <option value="maintenance">Dalam Servis</option>
                                    <option value="broken">Rusak</option>
                                    <option value="retired">Pensiun</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Lokasi / Ruangan</label>
                                <input v-model="form.location" type="text" placeholder="misal: Ruang PTSP Lt.1" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Pemegang / Pengguna</label>
                                <input v-model="form.assigned_to" type="text" placeholder="misal: Budi - Panitera Muda" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Tgl Perolehan</label>
                                <input v-model="form.purchase_date" type="date" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div>
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Harga Perolehan (Rp)</label>
                                <input v-model="form.purchase_price" type="number" min="0" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400" />
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Spesifikasi (JSON, opsional)</label>
                                <textarea v-model="specInput" rows="3" placeholder='{"cpu":"Intel i5-10400","ram":"8GB","storage":"256GB SSD"}'
                                          class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-xs outline-none focus:border-sky-400"></textarea>
                            </div>
                            <div class="col-span-2">
                                <label class="mb-1 block text-[11px] font-bold uppercase tracking-wide text-slate-600">Catatan</label>
                                <textarea v-model="form.notes" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm outline-none focus:border-sky-400"></textarea>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" @click="showForm = false" class="rounded-xl border border-slate-200 px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-50 transition">Batal</button>
                            <button type="submit" :disabled="formBusy" class="rounded-xl bg-sky-600 px-5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-sky-700 disabled:opacity-60">
                                {{ formBusy ? 'Menyimpan...' : editTarget ? 'Simpan Perubahan' : 'Tambah Aset' }}
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
