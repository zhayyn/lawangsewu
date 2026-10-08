<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';

const props = defineProps({
    appMeta:    { type: Object, required: true },
    navGroups:  { type: Array,  default: () => [] },
    antrian:    { type: Array,  default: () => [] },
    penyerahan: { type: Array,  default: () => [] },
    dari:       { type: String, default: '' },
    sampai:     { type: String, default: '' },
    lokets:     { type: Array,  default: () => [] },
});

const formDari   = ref(props.dari);
const formSampai = ref(props.sampai);

function filterLaporan() {
    window.location.href = `/pelayanan-ptsp/laporan?dari=${formDari.value}&sampai=${formSampai.value}`;
}

const totalAntrian  = computed(() => props.antrian.reduce((s, r) => s + (r.jumlah || 0), 0));
const totalDilayani = computed(() => props.antrian.filter(r => r.status === 'served').reduce((s, r) => s + (r.jumlah || 0), 0));
const totalAc       = computed(() => props.penyerahan.filter(r => r.jenis === 'ac' || r.jenis === 'ac_salput').reduce((s, r) => s + (r.jumlah || 0), 0));
const totalSalput   = computed(() => props.penyerahan.filter(r => r.jenis === 'salput' || r.jenis === 'ac_salput').reduce((s, r) => s + (r.jumlah || 0), 0));
</script>

<template>
    <Head title="Laporan Pelayanan PTSP" />
    <LawangsewuLayout current-route="pelayanan-ptsp" :nav-groups="navGroups" :app-meta="appMeta">

        <section class="card-surface p-6">
            <a href="/pelayanan-ptsp" class="inline-flex items-center gap-1.5 text-xs text-indigo-400 hover:underline mb-3">← Kembali</a>
            <h1 class="text-2xl font-bold text-[var(--text-1)]">Laporan Pelayanan PTSP</h1>

            <div class="mt-5 flex flex-wrap gap-3 items-end">
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Dari</label>
                    <input v-model="formDari" type="date"
                           class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--text-1)] focus:border-violet-500/60 focus:outline-none">
                </div>
                <div class="space-y-1">
                    <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Sampai</label>
                    <input v-model="formSampai" type="date"
                           class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-sm text-[var(--text-1)] focus:border-violet-500/60 focus:outline-none">
                </div>
                <button @click="filterLaporan"
                        class="rounded-xl bg-violet-600 px-5 py-2 text-sm font-semibold text-white hover:bg-violet-500 transition">
                    Filter
                </button>
            </div>
        </section>

        <!-- Summary -->
        <section class="grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="card-surface p-5 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Total Antrian</p>
                <p class="mt-2 text-4xl font-black text-indigo-400">{{ totalAntrian }}</p>
            </div>
            <div class="card-surface p-5 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Dilayani</p>
                <p class="mt-2 text-4xl font-black text-emerald-400">{{ totalDilayani }}</p>
            </div>
            <div class="card-surface p-5 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Penyerahan AC</p>
                <p class="mt-2 text-4xl font-black text-amber-400">{{ totalAc }}</p>
            </div>
            <div class="card-surface p-5 text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Salinan Putusan</p>
                <p class="mt-2 text-4xl font-black text-violet-400">{{ totalSalput }}</p>
            </div>
        </section>

        <!-- Tabel Antrian per Loket per Hari -->
        <section class="card-surface p-6">
            <h2 class="mb-4 text-base font-semibold text-[var(--text-1)]">Rekap Antrian per Loket</h2>
            <div v-if="antrian.length === 0" class="py-10 text-center text-sm text-[var(--text-3)]">Tidak ada data.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--border)] text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--text-3)]">
                            <th class="pb-3 text-left">Tanggal</th>
                            <th class="pb-3 text-left">Loket</th>
                            <th class="pb-3 text-left">Status</th>
                            <th class="pb-3 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <tr v-for="(r, i) in antrian" :key="i" class="hover:bg-[var(--surface-2)] transition">
                            <td class="py-3 pr-4 text-xs text-[var(--text-2)]">{{ r.tanggal_antrian }}</td>
                            <td class="py-3 pr-4 text-xs text-[var(--text-1)]">Loket {{ r.loket_id }}</td>
                            <td class="py-3 pr-4">
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase"
                                      :class="{
                                        'bg-emerald-500/10 text-emerald-400': r.status === 'served',
                                        'bg-blue-500/10 text-blue-400': r.status === 'waiting',
                                        'bg-amber-500/10 text-amber-400': r.status === 'skipped',
                                        'bg-violet-500/10 text-violet-400': r.status === 'called',
                                      }">{{ r.status }}</span>
                            </td>
                            <td class="py-3 text-right font-bold text-[var(--text-1)]">{{ r.jumlah }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- Tabel Penyerahan -->
        <section class="card-surface p-6">
            <h2 class="mb-4 text-base font-semibold text-[var(--text-1)]">Rekap Penyerahan AC / Salinan Putusan</h2>
            <div v-if="penyerahan.length === 0" class="py-10 text-center text-sm text-[var(--text-3)]">Tidak ada data.</div>
            <div v-else class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-[var(--border)] text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--text-3)]">
                            <th class="pb-3 text-left">Tanggal</th>
                            <th class="pb-3 text-left">Jenis</th>
                            <th class="pb-3 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[var(--border)]">
                        <tr v-for="(r, i) in penyerahan" :key="i" class="hover:bg-[var(--surface-2)] transition">
                            <td class="py-3 pr-4 text-xs text-[var(--text-2)]">{{ r.tanggal_penyerahan }}</td>
                            <td class="py-3 pr-4">
                                <span class="rounded-full bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-bold uppercase text-amber-400">
                                    {{ { ac: 'Akta Cerai', salput: 'Salinan Putusan', ac_salput: 'AC + Salput' }[r.jenis] }}
                                </span>
                            </td>
                            <td class="py-3 text-right font-bold text-[var(--text-1)]">{{ r.jumlah }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </LawangsewuLayout>
</template>
