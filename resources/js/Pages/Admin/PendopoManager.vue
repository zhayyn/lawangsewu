<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    status: { type: String, default: null },
    settings: { type: Object, required: true },
    stats: { type: Object, required: true },
    monthlySummary: { type: Object, required: true },
    profileSummary: { type: Object, default: () => ({ positions: { total: 0, items: [] }, institutions: { total: 0, items: [] } }) },
    recentEntries: { type: Array, default: () => [] },
    photoCount: { type: Number, default: 0 },
    legacy: { type: Object, default: () => ({}) },
});

const maxMonthlyValue = computed(() => Math.max(...(props.monthlySummary.data ?? [0]), 1));
const chartColors = ['#f59e0b', '#06b6d4', '#8b5cf6', '#22c55e', '#ef4444', '#3b82f6', '#94a3b8'];

const donutStyle = (items = []) => {
    if (!items.length) {
        return { background: 'conic-gradient(#cbd5e1 0deg 360deg)' };
    }

    let cursor = 0;
    const segments = items.map((item, index) => {
        const degrees = Math.max(0, Number(item.percentage || 0) * 3.6);
        const start = cursor;
        cursor += degrees;
        return `${chartColors[index % chartColors.length]} ${start}deg ${cursor}deg`;
    });

    return { background: `conic-gradient(${segments.join(', ')})` };
};

const segmentColor = (index) => ({
    backgroundColor: chartColors[index % chartColors.length],
});

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
                                Dashboard statistik Buku Tamu/Pendopo untuk memantau kunjungan, profil jabatan, asal instansi, dan entri terbaru.
                            </p>
                        </div>
                    </div>

                    <a :href="route('lawangsewu.guestbook.list', { period: 'all' })" class="github-button !bg-amber-600 hover:!bg-amber-700">
                        Buka Daftar Tamu
                    </a>
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

            <section>
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
            </section>

            <section class="grid gap-6 xl:grid-cols-2">
                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center">
                        <div class="relative mx-auto h-44 w-44 shrink-0 rounded-full p-4" :style="donutStyle(profileSummary.positions.items)">
                            <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-[var(--surface-1)] text-center shadow-inner shadow-black/10">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Jabatan</p>
                                <p class="text-3xl font-black text-[var(--text-1)]">{{ profileSummary.positions.total }}</p>
                                <p class="text-[10px] font-bold text-[var(--text-3)]">profil</p>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Profil Jabatan</h2>
                            <p class="mt-2 text-sm text-[var(--text-3)]">Komposisi pekerjaan dan jabatan tamu yang tercatat.</p>
                            <div class="mt-5 space-y-3">
                                <div v-for="(item, index) in profileSummary.positions.items" :key="`position-${item.label}`" class="space-y-1.5">
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <span class="flex min-w-0 items-center gap-2 font-bold text-[var(--text-2)]">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="segmentColor(index)"></span>
                                            <span class="truncate">{{ item.label }}</span>
                                        </span>
                                        <span class="shrink-0 font-black text-[var(--text-1)]">{{ item.total }} / {{ item.percentage }}%</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-[var(--surface-3)]">
                                        <div class="h-full rounded-full" :style="{ ...segmentColor(index), width: `${item.percentage}%` }"></div>
                                    </div>
                                </div>
                                <p v-if="!profileSummary.positions.items.length" class="text-sm font-bold text-[var(--text-3)]">Belum ada data jabatan.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                    <div class="flex flex-col gap-5 md:flex-row md:items-center">
                        <div class="relative mx-auto h-44 w-44 shrink-0 rounded-full p-4" :style="donutStyle(profileSummary.institutions.items)">
                            <div class="flex h-full w-full flex-col items-center justify-center rounded-full bg-[var(--surface-1)] text-center shadow-inner shadow-black/10">
                                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Instansi</p>
                                <p class="text-3xl font-black text-[var(--text-1)]">{{ profileSummary.institutions.total }}</p>
                                <p class="text-[10px] font-bold text-[var(--text-3)]">asal</p>
                            </div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Asal Instansi</h2>
                            <p class="mt-2 text-sm text-[var(--text-3)]">Distribusi kategori asal instansi atau satuan tamu.</p>
                            <div class="mt-5 space-y-3">
                                <div v-for="(item, index) in profileSummary.institutions.items" :key="`institution-${item.label}`" class="space-y-1.5">
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <span class="flex min-w-0 items-center gap-2 font-bold text-[var(--text-2)]">
                                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="segmentColor(index)"></span>
                                            <span class="truncate">{{ item.label }}</span>
                                        </span>
                                        <span class="shrink-0 font-black text-[var(--text-1)]">{{ item.total }} / {{ item.percentage }}%</span>
                                    </div>
                                    <div class="h-2 overflow-hidden rounded-full bg-[var(--surface-3)]">
                                        <div class="h-full rounded-full" :style="{ ...segmentColor(index), width: `${item.percentage}%` }"></div>
                                    </div>
                                </div>
                                <p v-if="!profileSummary.institutions.items.length" class="text-sm font-bold text-[var(--text-3)]">Belum ada data instansi.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                <div class="mb-5 flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Tamu Terbaru</h2>
                        <p class="mt-2 text-sm text-[var(--text-3)]">Snapshot 10 entri terakhir yang sudah hidup di Lawangsewu.</p>
                    </div>
                </div>

                <div class="grid gap-3 lg:hidden">
                    <article
                        v-for="entry in recentEntries"
                        :key="`card-${entry.id}`"
                        class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="break-words text-sm font-black text-[var(--text-1)]">{{ entry.name }}</p>
                                <p class="mt-1 break-words text-xs font-semibold text-[var(--text-3)]">{{ entry.position || '-' }}</p>
                            </div>
                            <button
                                type="button"
                                class="shrink-0 rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-[10px] font-black uppercase tracking-[0.12em] text-rose-400 transition hover:bg-rose-500/20"
                                @click="destroyEntry(entry.id)"
                            >
                                Hapus
                            </button>
                        </div>
                        <dl class="mt-4 grid gap-3 text-xs sm:grid-cols-2">
                            <div class="min-w-0">
                                <dt class="font-black uppercase tracking-[0.16em] text-[var(--text-3)]">ID</dt>
                                <dd class="mt-1 break-all font-mono text-[var(--text-2)]">{{ entry.id }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="font-black uppercase tracking-[0.16em] text-[var(--text-3)]">Checkin</dt>
                                <dd class="mt-1 text-[var(--text-2)]">{{ entry.checkin || '-' }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="font-black uppercase tracking-[0.16em] text-[var(--text-3)]">Instansi</dt>
                                <dd class="mt-1 break-words text-[var(--text-2)]">{{ entry.institution || '-' }}</dd>
                            </div>
                            <div class="min-w-0">
                                <dt class="font-black uppercase tracking-[0.16em] text-[var(--text-3)]">Keperluan</dt>
                                <dd class="mt-1 break-words text-[var(--text-2)]">{{ entry.purpose || '-' }}</dd>
                            </div>
                        </dl>
                    </article>
                    <p v-if="!recentEntries.length" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-6 text-center text-sm font-bold text-[var(--text-3)]">
                        Belum ada data tamu terbaru.
                    </p>
                </div>

                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-[980px] table-fixed text-sm">
                        <thead>
                            <tr class="border-b border-[var(--border)] text-left text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">
                                <th class="w-[17%] px-3 py-3">ID</th>
                                <th class="w-[22%] px-3 py-3">Nama</th>
                                <th class="w-[22%] px-3 py-3">Instansi</th>
                                <th class="w-[17%] px-3 py-3">Keperluan</th>
                                <th class="w-[14%] px-3 py-3">Checkin</th>
                                <th class="w-[8%] px-3 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in recentEntries" :key="entry.id" class="border-b border-[var(--border)]/70 text-[var(--text-2)]">
                                <td class="break-all px-3 py-3 font-mono text-xs text-[var(--text-3)]">{{ entry.id }}</td>
                                <td class="px-3 py-3 align-top">
                                    <div class="break-words font-bold text-[var(--text-1)]">{{ entry.name }}</div>
                                    <div class="break-words text-xs text-[var(--text-3)]">{{ entry.position || '-' }}</div>
                                </td>
                                <td class="break-words px-3 py-3 align-top">{{ entry.institution || '-' }}</td>
                                <td class="break-words px-3 py-3 align-top">{{ entry.purpose || '-' }}</td>
                                <td class="px-3 py-3 align-top">{{ entry.checkin }}</td>
                                <td class="px-3 py-3 text-right align-top">
                                    <button
                                        type="button"
                                        class="whitespace-nowrap rounded-xl border border-rose-500/20 bg-rose-500/10 px-3 py-2 text-[10px] font-black uppercase tracking-[0.12em] text-rose-400 transition hover:bg-rose-500/20"
                                        @click="destroyEntry(entry.id)"
                                    >
                                        Hapus
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!recentEntries.length">
                                <td colspan="6" class="px-3 py-8 text-center text-sm font-bold text-[var(--text-3)]">Belum ada data tamu terbaru.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </LawangsewuLayout>
</template>
