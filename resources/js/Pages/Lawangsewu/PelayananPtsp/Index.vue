<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { useReverb } from '@/composables/useReverb';

const props = defineProps({
    appMeta:      { type: Object, required: true },
    navGroups:    { type: Array,  default: () => [] },
    lokets:       { type: Array,  default: () => [] },
    antrian:      { type: Array,  default: () => [] },
    dipanggil:    { type: Object, default: null },
    summaryLoket: { type: Array,  default: () => [] },
    summary:      { type: Object, default: () => ({}) },
    canOperate:   { type: Boolean, default: false },
    flash:        { type: Object, default: () => ({}) },
});

// ── State ──────────────────────────────────────────────────────────────────
const antrian     = ref([...props.antrian]);
const dipanggil   = ref(props.dipanggil ? { ...props.dipanggil } : null);
const summary     = ref({ ...props.summary });
const summaryLoket = ref([...props.summaryLoket]);
const loading     = ref(null);
const activeTab   = ref(props.lokets[0]?.id ?? null);
const showForm    = ref(false);

const form = ref({
    loket_id:      props.lokets[0]?.id ?? '',
    nama_pemohon:  '',
    nomor_perkara: '',
    keperluan:     '',
});

const { connected: reverbConnected, subscribeQueue } = useReverb();
let unsubscribeQueue = null;

// ── Computed ───────────────────────────────────────────────────────────────
const antriandFiltered = computed(() =>
    activeTab.value
        ? antrian.value.filter(t => t.loket_id === activeTab.value)
        : antrian.value
);

const loketAktif = computed(() => props.lokets.filter(l => l.is_active));

const statusMap = {
    waiting:   { label: 'Menunggu',  cls: 'bg-blue-500/10 text-blue-400 border-blue-500/20' },
    called:    { label: 'Dipanggil', cls: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' },
    served:    { label: 'Selesai',   cls: 'bg-violet-500/10 text-violet-400 border-violet-500/20' },
    skipped:   { label: 'Dilewati', cls: 'bg-amber-500/10 text-amber-400 border-amber-500/20' },
    cancelled: { label: 'Batal',     cls: 'bg-red-500/10 text-red-400 border-red-500/20' },
};

// ── Actions ────────────────────────────────────────────────────────────────
function submitAntrian() {
    loading.value = 'form';
    router.post('/pelayanan-ptsp/antrian', form.value, {
        preserveScroll: true,
        onFinish: () => { loading.value = null; },
        onSuccess: () => {
            form.value.nama_pemohon  = '';
            form.value.nomor_perkara = '';
            form.value.keperluan     = '';
            showForm.value = false;
        },
    });
}

function callAntrian(t) {
    loading.value = t.id;
    router.post(`/pelayanan-ptsp/antrian/${t.id}/call`, {}, {
        preserveScroll: true,
        onFinish: () => { loading.value = null; },
    });
}

function serveAntrian(t) {
    loading.value = t.id;
    router.post(`/pelayanan-ptsp/antrian/${t.id}/serve`, {}, {
        preserveScroll: true,
        onFinish: () => { loading.value = null; },
    });
}

function skipAntrian(t) {
    loading.value = t.id;
    router.post(`/pelayanan-ptsp/antrian/${t.id}/skip`, {}, {
        preserveScroll: true,
        onFinish: () => { loading.value = null; },
    });
}

// ── Realtime ───────────────────────────────────────────────────────────────
onMounted(() => {
    unsubscribeQueue = subscribeQueue('ptsp', (event) => {
        if (event?.summary) summary.value = { ...event.summary };

        if (event?.ticket?.id) {
            const idx = antrian.value.findIndex(i => i.id === event.ticket.id);
            if (idx >= 0) antrian.value[idx] = { ...antrian.value[idx], ...event.ticket };
            else antrian.value.unshift({ ...event.ticket });
        }

        if (event?.ticket?.status === 'called') {
            dipanggil.value = event.ticket;
        } else if (dipanggil.value?.id === event?.ticket?.id && event?.ticket?.status !== 'called') {
            dipanggil.value = antrian.value.find(i => i.status === 'called') ?? null;
        }
    });
});

onUnmounted(() => unsubscribeQueue?.());
</script>

<template>
    <Head title="Pelayanan PTSP" />
    <LawangsewuLayout current-route="pelayanan-ptsp" :nav-groups="navGroups" :app-meta="appMeta">

        <!-- ── Hero ──────────────────────────────────────────────────────── -->
        <section class="card-surface overflow-hidden p-6 lg:p-8">
            <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.22em] text-indigo-400">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                        Pelayanan Terpadu Satu Pintu
                    </div>
                    <h1 class="text-3xl font-bold tracking-tight text-[var(--text-1)] sm:text-4xl">
                        Pelayanan PTSP
                    </h1>
                    <p class="max-w-xl text-sm leading-relaxed text-[var(--text-2)]">
                        Kelola antrian loket, penyerahan AC &amp; Salinan Putusan, terintegrasi langsung dengan database SIPP.
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em]"
                         :class="reverbConnected ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-300'">
                        <span class="h-2 w-2 rounded-full animate-pulse" :class="reverbConnected ? 'bg-emerald-400' : 'bg-amber-400'" />
                        {{ reverbConnected ? 'Realtime aktif' : 'Offline mode' }}
                    </div>
                </div>

                <!-- Active Call Banner -->
                <div v-if="dipanggil"
                     class="min-w-[220px] rounded-2xl border border-emerald-500/30 bg-gradient-to-br from-emerald-500/20 to-emerald-600/10 p-5 text-center shadow-lg shadow-emerald-500/10">
                    <p class="text-[10px] font-bold uppercase tracking-[0.25em] text-emerald-400">🔊 Sedang Dipanggil</p>
                    <p class="mt-1 text-5xl font-black tracking-tight text-emerald-300">{{ dipanggil.nomor_antrian }}</p>
                    <p class="mt-1 text-xs text-[var(--text-2)]">{{ dipanggil.loket_kode }}</p>
                </div>
                <div v-else class="min-w-[220px] rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-5 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-[var(--text-3)]">Panggilan Aktif</p>
                    <p class="mt-2 text-sm text-[var(--text-3)]">Belum ada</p>
                </div>
            </div>
        </section>

        <!-- ── Flash ──────────────────────────────────────────────────────── -->
        <div v-if="flash?.status"
             class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-3.5 text-sm font-medium text-emerald-300">
            {{ flash.status }}
        </div>

        <!-- ── Summary Cards ───────────────────────────────────────────────── -->
        <section class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div v-for="(val, key, i) in summary" :key="key"
                 class="card-surface p-5 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">
                    {{ { waiting: 'Menunggu', called: 'Dipanggil', served: 'Selesai', skipped: 'Dilewati' }[key] }}
                </p>
                <p class="mt-2 text-4xl font-black"
                   :class="[['text-blue-400','text-emerald-400','text-violet-400','text-amber-400'][i]]">
                    {{ val ?? 0 }}
                </p>
            </div>
        </section>

        <!-- ── Action Buttons ─────────────────────────────────────────────── -->
        <div v-if="canOperate" class="flex flex-wrap gap-3">
            <button @click="showForm = !showForm"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 active:scale-95">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Tambah Antrian
            </button>
            <a href="/pelayanan-ptsp/penyerahan-ac"
               class="inline-flex items-center gap-2 rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-5 py-2.5 text-sm font-semibold text-[var(--text-1)] transition hover:bg-[var(--surface-3)]">
                <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                Penyerahan AC / Salput
            </a>
            <a href="/pelayanan-ptsp/laporan"
               class="inline-flex items-center gap-2 rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-5 py-2.5 text-sm font-semibold text-[var(--text-1)] transition hover:bg-[var(--surface-3)]">
                <svg class="h-4 w-4 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                Laporan
            </a>
        </div>

        <!-- ── Form Tambah Antrian ─────────────────────────────────────────── -->
        <Transition enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0"
                    leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-2"
                    enter-active-class="transition duration-200" leave-active-class="transition duration-150">
            <section v-if="showForm && canOperate" class="card-surface p-6">
                <h2 class="mb-5 text-base font-semibold text-[var(--text-1)]">Tambah Nomor Antrian</h2>
                <form class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="submitAntrian">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Loket *</label>
                        <select v-model="form.loket_id"
                                class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-indigo-500/60 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                            <option v-for="l in loketAktif" :key="l.id" :value="l.id">{{ l.nama }}</option>
                        </select>
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Nama Pemohon</label>
                        <input v-model="form.nama_pemohon" maxlength="120" placeholder="Nama pengunjung"
                               class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-indigo-500/60 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">No. Perkara</label>
                        <input v-model="form.nomor_perkara" maxlength="80" placeholder="0001/Pdt.G/2025/PA.Smg"
                               class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-indigo-500/60 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Keperluan</label>
                        <input v-model="form.keperluan" maxlength="255" placeholder="Mis: Penyerahan AC"
                               class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-indigo-500/60 focus:outline-none focus:ring-2 focus:ring-indigo-500/20">
                    </div>
                    <div class="sm:col-span-2 lg:col-span-4 flex gap-3">
                        <button type="submit" :disabled="loading === 'form'"
                                class="rounded-xl bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500 disabled:opacity-60">
                            {{ loading === 'form' ? 'Memproses…' : '+ Ambil Nomor' }}
                        </button>
                        <button type="button" @click="showForm = false"
                                class="rounded-xl border border-[var(--border)] px-5 py-2.5 text-sm font-semibold text-[var(--text-2)] transition hover:bg-[var(--surface-2)]">
                            Batal
                        </button>
                    </div>
                </form>
            </section>
        </Transition>

        <!-- ── Per-Loket Summary ───────────────────────────────────────────── -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="ls in summaryLoket" :key="ls.id"
                 @click="activeTab = ls.id"
                 class="card-surface cursor-pointer p-4 transition hover:border-indigo-500/30"
                 :class="activeTab === ls.id ? 'border-indigo-500/40 bg-indigo-500/5' : ''">
                <p class="text-xs font-semibold text-[var(--text-2)]">{{ ls.kode }}</p>
                <p class="mt-0.5 text-[11px] text-[var(--text-3)] truncate">{{ ls.nama }}</p>
                <div class="mt-3 flex gap-4 text-center text-xs">
                    <div>
                        <p class="font-bold text-blue-400">{{ ls.waiting }}</p>
                        <p class="text-[10px] text-[var(--text-3)]">Tunggu</p>
                    </div>
                    <div>
                        <p class="font-bold text-emerald-400">{{ ls.called }}</p>
                        <p class="text-[10px] text-[var(--text-3)]">Panggil</p>
                    </div>
                    <div>
                        <p class="font-bold text-violet-400">{{ ls.served }}</p>
                        <p class="text-[10px] text-[var(--text-3)]">Selesai</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Antrian Table ───────────────────────────────────────────────── -->
        <section class="card-surface p-6">
            <div class="mb-5 flex items-center justify-between">
                <h2 class="text-base font-semibold text-[var(--text-1)]">
                    Daftar Antrian — {{ activeTab ? (loketAktif.find(l => l.id === activeTab)?.kode ?? 'Semua') : 'Semua Loket' }}
                </h2>
                <button @click="activeTab = null"
                        v-if="activeTab"
                        class="text-xs text-indigo-400 hover:underline">Tampilkan semua</button>
            </div>

            <div v-if="antriandFiltered.length === 0" class="py-14 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-[var(--surface-2)]">
                    <svg class="h-8 w-8 text-[var(--text-3)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <p class="text-sm text-[var(--text-3)]">Belum ada antrian hari ini.</p>
            </div>

            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--border)] text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--text-3)]">
                            <th class="pb-3 text-left">No. Antrian</th>
                            <th class="pb-3 text-left">Loket</th>
                            <th class="pb-3 text-left">Nama / Keperluan</th>
                            <th class="pb-3 text-left">No. Perkara</th>
                            <th class="pb-3 text-left">Status</th>
                            <th class="pb-3 text-left">Waktu</th>
                            <th v-if="canOperate" class="pb-3 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <tr v-for="t in antriandFiltered" :key="t.id"
                            class="group transition hover:bg-[var(--surface-2)]"
                            :class="t.status === 'called' ? 'bg-emerald-500/5' : ''">
                            <td class="py-3.5 pr-4">
                                <span class="text-base font-black" :class="t.status === 'called' ? 'text-emerald-300' : 'text-[var(--text-1)]'">
                                    {{ t.nomor_antrian }}
                                </span>
                            </td>
                            <td class="py-3.5 pr-4 text-[11px] text-[var(--text-3)]">{{ t.loket_kode }}</td>
                            <td class="py-3.5 pr-4">
                                <p class="font-medium text-[var(--text-1)]">{{ t.nama_pemohon || '—' }}</p>
                                <p class="text-xs text-[var(--text-3)]">{{ t.keperluan || '' }}</p>
                            </td>
                            <td class="py-3.5 pr-4 font-mono text-xs text-[var(--text-2)]">{{ t.nomor_perkara || '—' }}</td>
                            <td class="py-3.5 pr-4">
                                <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide"
                                      :class="statusMap[t.status]?.cls ?? 'text-[var(--text-3)]'">
                                    {{ statusMap[t.status]?.label ?? t.status }}
                                </span>
                            </td>
                            <td class="py-3.5 pr-4 text-xs text-[var(--text-3)]">
                                {{ t.dipanggil_at || '—' }}
                            </td>
                            <td v-if="canOperate" class="py-3.5 text-right">
                                <div class="inline-flex gap-2">
                                    <button v-if="['waiting','skipped'].includes(t.status)"
                                            :disabled="loading === t.id"
                                            @click="callAntrian(t)"
                                            class="rounded-lg border border-indigo-500/30 bg-indigo-500/10 px-3 py-1.5 text-xs font-semibold text-indigo-400 transition hover:bg-indigo-500/20 disabled:opacity-50">
                                        Panggil
                                    </button>
                                    <button v-if="t.status === 'called'"
                                            :disabled="loading === t.id"
                                            @click="serveAntrian(t)"
                                            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-400 transition hover:bg-emerald-500/20 disabled:opacity-50">
                                        Layani
                                    </button>
                                    <button v-if="t.status === 'called'"
                                            :disabled="loading === t.id"
                                            @click="skipAntrian(t)"
                                            class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-400 transition hover:bg-amber-500/20 disabled:opacity-50">
                                        Lewati
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </LawangsewuLayout>
</template>
