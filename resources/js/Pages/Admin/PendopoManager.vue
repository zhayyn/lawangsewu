<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    status: { type: String, default: null },
    settings: { type: Object, required: true },
    stats: { type: Object, required: true },
    monthlySummary: { type: Object, required: true },
    recentEntries: { type: Array, default: () => [] },
    photoCount: { type: Number, default: 0 },
    legacy: { type: Object, default: () => ({}) },
});

const settingsForm = useForm({
    per_page: props.settings.per_page ?? 10,
    require_identity_fields: Boolean(props.settings.require_identity_fields),
    event_name: props.settings.event_name ?? '',
});

const maxMonthlyValue = computed(() => Math.max(...(props.monthlySummary.data ?? [0]), 1));

const submitSettings = () => {
    settingsForm.patch(route('admin.pendopo.settings.update'), {
        preserveScroll: true,
    });
};

const syncLegacy = () => {
    router.post(route('admin.pendopo.sync'), {}, {
        preserveScroll: true,
    });
};

const destroyEntry = (entryId) => {
    router.delete(route('admin.pendopo.entries.destroy', entryId), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Kelola Pendopo" />

    <LawangsewuLayout
        current-route="admin-pendopo"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-2xl shadow-black/10">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex rounded-full border border-amber-500/20 bg-amber-500/10 px-4 py-2 text-[11px] font-black uppercase tracking-[0.22em] text-amber-400">
                            Superadmin Control
                        </span>
                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-[var(--text-1)]">
                                Kelola Pendopo
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm text-[var(--text-3)]">
                                Dashboard migrasi penuh Pendopo: sinkronisasi data legacy, statistik kunjungan, pengaturan event, dan status arsip.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <button type="button" class="secondary-button" @click="syncLegacy">
                            Sinkronkan Legacy Pendopo
                        </button>
                        <a :href="route('lawangsewu.guestbook.list', { period: 'all' })" class="github-button !bg-amber-600 hover:!bg-amber-700">
                            Buka Daftar Tamu
                        </a>
                    </div>
                </div>
            </section>

            <div
                v-if="status"
                class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-400"
            >
                {{ status }}
            </div>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Hari Ini</p>
                    <p class="mt-3 text-3xl font-black text-[var(--text-1)]">{{ stats.day }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Minggu Ini</p>
                    <p class="mt-3 text-3xl font-black text-[var(--text-1)]">{{ stats.week }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Bulan Ini</p>
                    <p class="mt-3 text-3xl font-black text-[var(--text-1)]">{{ stats.month }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Tahun Ini</p>
                    <p class="mt-3 text-3xl font-black text-[var(--text-1)]">{{ stats.year }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Total Tamu</p>
                    <p class="mt-3 text-3xl font-black text-[var(--text-1)]">{{ stats.all }}</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Tren Bulanan</h2>
                            <p class="mt-2 text-sm text-[var(--text-3)]">Distribusi kunjungan tamu sepanjang tahun berjalan.</p>
                        </div>
                        <span class="rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">
                            Foto tersalin {{ photoCount }}
                        </span>
                    </div>

                    <div class="mt-6 grid grid-cols-6 gap-3 md:grid-cols-12">
                        <div
                            v-for="(value, index) in monthlySummary.data"
                            :key="monthlySummary.labels[index]"
                            class="flex flex-col items-center gap-3"
                        >
                            <div class="flex h-48 items-end">
                                <div
                                    class="w-10 rounded-t-2xl bg-gradient-to-t from-amber-500 via-cyan-500 to-sky-300 shadow-lg shadow-cyan-500/10"
                                    :style="{ height: `${Math.max((value / maxMonthlyValue) * 100, value > 0 ? 10 : 0)}%` }"
                                />
                            </div>
                            <div class="text-center">
                                <p class="text-xs font-black text-[var(--text-1)]">{{ value }}</p>
                                <p class="text-[10px] font-bold uppercase tracking-[0.12em] text-[var(--text-3)]">{{ monthlySummary.labels[index] }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                        <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Status Migrasi</h2>
                        <div class="mt-5 space-y-3 text-sm">
                            <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                                <span class="text-[var(--text-3)]">Folder legacy</span>
                                <span class="font-black text-[var(--text-1)]">{{ legacy.exists ? legacy.root : 'Tidak ditemukan' }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                                <span class="text-[var(--text-3)]">Status arsip</span>
                                <span class="font-black" :class="legacy.is_archived ? 'text-emerald-400' : 'text-amber-400'">
                                    {{ legacy.is_archived ? 'Sudah diarsipkan' : 'Masih aktif / belum diarsipkan' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                                <span class="text-[var(--text-3)]">Data legacy</span>
                                <span class="font-black text-[var(--text-1)]">{{ legacy.entries ?? 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                                <span class="text-[var(--text-3)]">Foto legacy</span>
                                <span class="font-black text-[var(--text-1)]">{{ legacy.photos ?? 0 }}</span>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                        <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Pengaturan Pendopo</h2>
                        <form class="mt-5 space-y-4" @submit.prevent="submitSettings">
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Nama Acara</label>
                                <input v-model="settingsForm.event_name" type="text" class="input-surface w-full">
                            </div>
                            <div class="space-y-2">
                                <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Jumlah Data per Halaman</label>
                                <input v-model.number="settingsForm.per_page" type="number" min="5" max="100" class="input-surface w-full">
                            </div>
                            <label class="inline-flex items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-sm font-semibold text-[var(--text-2)]">
                                <input v-model="settingsForm.require_identity_fields" type="checkbox" class="rounded border-[var(--border)] bg-[var(--surface-2)]">
                                Wajibkan isian identitas lengkap
                            </label>
                            <button type="submit" class="github-button !w-full !bg-amber-600 hover:!bg-amber-700" :disabled="settingsForm.processing">
                                Simpan Pengaturan Pendopo
                            </button>
                        </form>
                    </section>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Tamu Terbaru</h2>
                        <p class="mt-2 text-sm text-[var(--text-3)]">Snapshot 10 entri terakhir yang sudah hidup di Lawangsewu.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--border)] text-left text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">
                                <th class="px-3 py-3">ID</th>
                                <th class="px-3 py-3">Nama</th>
                                <th class="px-3 py-3">Instansi</th>
                                <th class="px-3 py-3">Keperluan</th>
                                <th class="px-3 py-3">Checkin</th>
                                <th class="px-3 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in recentEntries" :key="entry.id" class="border-b border-[var(--border)]/70 text-[var(--text-2)]">
                                <td class="px-3 py-3 font-mono text-xs text-[var(--text-3)]">{{ entry.id }}</td>
                                <td class="px-3 py-3">
                                    <div class="font-bold text-[var(--text-1)]">{{ entry.name }}</div>
                                    <div class="text-xs text-[var(--text-3)]">{{ entry.position || '-' }}</div>
                                </td>
                                <td class="px-3 py-3">{{ entry.institution || '-' }}</td>
                                <td class="px-3 py-3">{{ entry.purpose || '-' }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">{{ entry.checkin }}</td>
                                <td class="px-3 py-3 text-right">
                                    <button
                                        type="button"
                                        class="rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-[10px] font-black uppercase tracking-[0.18em] text-rose-300 transition hover:bg-rose-500/20"
                                        @click="destroyEntry(entry.id)"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </LawangsewuLayout>
</template>
