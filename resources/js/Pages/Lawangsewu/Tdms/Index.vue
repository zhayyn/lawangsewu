<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    appMeta:   { type: Object, default: () => ({}) },
    navGroups: { type: Array,  default: () => [] },
    summary:   { type: Object, default: () => ({}) },
    recentServiceRecords: { type: Array, default: () => [] },
    upcomingMaintenance:  { type: Array, default: () => [] },
    assetsByCategory:     { type: Array, default: () => [] },
});

const toast = ref({ show: false, type: 'success', message: '' });
const showToast = (type, message) => {
    toast.value = { show: true, type, message };
    setTimeout(() => toast.value.show = false, 3500);
};

const statusColor = {
    active: 'emerald', maintenance: 'amber', broken: 'rose', retired: 'slate',
    open: 'sky', in_progress: 'violet', waiting_parts: 'amber',
    resolved: 'emerald', closed: 'slate', cancelled: 'rose',
    pending: 'amber', overdue: 'rose', completed: 'emerald',
};

const priorityColor = {
    critical: 'rose', high: 'orange', medium: 'amber', low: 'slate',
};

const statusBadgeClass = (status) => {
    const c = statusColor[status] || 'slate';
    return `inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-${c}-100 text-${c}-700 border border-${c}-200`;
};

const priorityBadgeClass = (priority) => {
    const c = priorityColor[priority] || 'slate';
    return `inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold bg-${c}-100 text-${c}-700 border border-${c}-200`;
};

const formatDate = (iso) => {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
};

const formatRelative = (iso) => {
    if (!iso) return '—';
    const diff = Date.now() - new Date(iso).getTime();
    const minutes = Math.floor(diff / 60000);
    if (minutes < 60) return `${minutes}m lalu`;
    if (minutes < 1440) return `${Math.floor(minutes / 60)}j lalu`;
    return `${Math.floor(minutes / 1440)}h lalu`;
};

const assetSummary  = computed(() => props.summary?.assets       || { total: 0, active: 0, broken: 0, maintenance: 0 });
const ticketSummary = computed(() => props.summary?.tickets      || { open: 0, breached: 0 });
const maintSummary  = computed(() => props.summary?.maintenance  || { overdue: 0, dueToday: 0 });
const replSummary   = computed(() => props.summary?.replacements || { pending: 0 });

const statCards = computed(() => [
    { label: 'Total Aset', value: assetSummary.value.total, sub: `${assetSummary.value.active} aktif`, icon: '🖥️', tone: 'sky' },
    { label: 'Aset Rusak', value: assetSummary.value.broken, sub: `${assetSummary.value.maintenance} dalam servis`, icon: '🔧', tone: assetSummary.value.broken > 0 ? 'rose' : 'emerald' },
    { label: 'Tiket Terbuka', value: ticketSummary.value.open, sub: ticketSummary.value.breached > 0 ? `⚠ ${ticketSummary.value.breached} breach SLA` : 'Semua dalam SLA', icon: '🎫', tone: ticketSummary.value.breached > 0 ? 'rose' : 'violet' },
    { label: 'Jadwal Perawatan', value: maintSummary.value.dueToday, sub: maintSummary.value.overdue > 0 ? `${maintSummary.value.overdue} terlambat` : 'Tidak ada keterlambatan', icon: '📅', tone: maintSummary.value.overdue > 0 ? 'amber' : 'emerald' },
]);

const toneClasses = (tone) => ({
    sky:     { card: 'border-sky-200/60 bg-sky-50/40',     icon: 'bg-sky-100 text-sky-600',     num: 'text-sky-700' },
    rose:    { card: 'border-rose-200/60 bg-rose-50/40',   icon: 'bg-rose-100 text-rose-600',   num: 'text-rose-700' },
    emerald: { card: 'border-emerald-200/60 bg-emerald-50/40', icon: 'bg-emerald-100 text-emerald-600', num: 'text-emerald-700' },
    violet:  { card: 'border-violet-200/60 bg-violet-50/40', icon: 'bg-violet-100 text-violet-600', num: 'text-violet-700' },
    amber:   { card: 'border-amber-200/60 bg-amber-50/40', icon: 'bg-amber-100 text-amber-600', num: 'text-amber-700' },
    slate:   { card: 'border-slate-200/60 bg-slate-50/40', icon: 'bg-slate-100 text-slate-600', num: 'text-slate-700' },
}[tone] || { card: 'border-slate-200/60 bg-white', icon: 'bg-slate-100', num: 'text-slate-800' });
</script>

<template>
    <LawangsewuLayout :app-meta="appMeta" :nav-groups="navGroups">
        <Head title="TDMS — Infrastruktur IT" />

        <div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-black tracking-tight text-slate-900">
                        <span class="mr-2">🖥️</span>TDMS
                        <span class="ml-2 rounded-full bg-sky-100 px-2.5 py-0.5 text-xs font-bold text-sky-700 border border-sky-200">Tech Device Management</span>
                    </h1>
                    <p class="mt-1 text-sm text-slate-500">Pantau infrastruktur IT — aset, tiket perbaikan, dan jadwal perawatan preventive.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route('lawangsewu.tdms.service-records')"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-violet-300 bg-violet-50 px-3.5 py-2 text-xs font-bold text-violet-700 transition hover:bg-violet-100">
                        🎫 Buka Tiket
                    </Link>
                    <Link :href="route('lawangsewu.tdms.assets')"
                          class="inline-flex items-center gap-1.5 rounded-xl border border-sky-300 bg-sky-600 px-3.5 py-2 text-xs font-bold text-white transition hover:bg-sky-700">
                        + Kelola Aset
                    </Link>
                </div>
            </div>

            <!-- Stat Cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div v-for="card in statCards" :key="card.label"
                     class="rounded-2xl border p-4 shadow-sm transition hover:shadow-md"
                     :class="toneClasses(card.tone).card">
                    <div class="flex items-center justify-between">
                        <span class="rounded-xl p-2 text-xl" :class="toneClasses(card.tone).icon">{{ card.icon }}</span>
                    </div>
                    <p class="mt-3 text-3xl font-black" :class="toneClasses(card.tone).num">{{ card.value }}</p>
                    <p class="text-xs font-semibold text-slate-700">{{ card.label }}</p>
                    <p class="mt-0.5 text-[11px] text-slate-500">{{ card.sub }}</p>
                </div>
            </div>

            <!-- 2-Column Content -->
            <div class="grid gap-6 lg:grid-cols-2">

                <!-- Recent Service Records -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                        <h2 class="font-bold text-slate-800">🎫 Tiket Terbaru</h2>
                        <Link :href="route('lawangsewu.tdms.service-records')" class="text-xs font-semibold text-sky-600 hover:underline">Lihat Semua →</Link>
                    </div>
                    <div v-if="recentServiceRecords.length === 0" class="px-5 py-10 text-center text-sm text-slate-400">
                        Belum ada tiket perbaikan.
                    </div>
                    <ul v-else class="divide-y divide-slate-50">
                        <li v-for="rec in recentServiceRecords" :key="rec.id" class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-[11px] font-bold text-slate-500">{{ rec.ticketNumber }}</span>
                                    <span :class="statusBadgeClass(rec.status)">{{ rec.statusLabel }}</span>
                                    <span :class="priorityBadgeClass(rec.priority)">{{ rec.priorityLabel }}</span>
                                    <span v-if="rec.isBreached" class="rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold text-rose-700 border border-rose-200">⚠ SLA Breach</span>
                                </div>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ rec.title }}</p>
                                <p class="text-[11px] text-slate-500">{{ rec.asset?.name || '—' }} · {{ formatRelative(rec.createdAt) }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Upcoming Maintenance -->
                <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                        <h2 class="font-bold text-slate-800">📅 Jadwal Perawatan Minggu Ini</h2>
                        <Link :href="route('lawangsewu.tdms.maintenance')" class="text-xs font-semibold text-sky-600 hover:underline">Lihat Semua →</Link>
                    </div>
                    <div v-if="upcomingMaintenance.length === 0" class="px-5 py-10 text-center text-sm text-slate-400">
                        Tidak ada jadwal perawatan minggu ini.
                    </div>
                    <ul v-else class="divide-y divide-slate-50">
                        <li v-for="sch in upcomingMaintenance" :key="sch.id" class="flex items-start gap-3 px-5 py-3 hover:bg-slate-50/60">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span :class="statusBadgeClass(sch.status)">{{ sch.statusLabel }}</span>
                                    <span class="text-[11px] text-slate-500">{{ sch.maintenanceType }}</span>
                                </div>
                                <p class="mt-0.5 truncate text-sm font-semibold text-slate-800">{{ sch.title }}</p>
                                <p class="text-[11px] text-slate-500">{{ sch.asset?.name || '—' }} · Jatuh tempo: {{ formatDate(sch.dueDate) }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Assets by Category -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-3.5">
                    <h2 class="font-bold text-slate-800">📦 Aset per Kategori</h2>
                    <Link :href="route('lawangsewu.tdms.assets')" class="text-xs font-semibold text-sky-600 hover:underline">Kelola Aset →</Link>
                </div>
                <div v-if="assetsByCategory.length === 0" class="px-5 py-12 text-center">
                    <p class="text-slate-400 text-sm">Belum ada kategori aset. Mulai tambahkan perangkat IT.</p>
                    <Link :href="route('lawangsewu.tdms.assets')" class="mt-3 inline-flex items-center rounded-xl bg-sky-600 px-4 py-2 text-xs font-bold text-white hover:bg-sky-700">
                        + Tambah Aset Pertama
                    </Link>
                </div>
                <div v-else class="grid grid-cols-2 gap-px bg-slate-100 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 rounded-b-2xl overflow-hidden">
                    <div v-for="cat in assetsByCategory" :key="cat.id"
                         class="flex flex-col items-center gap-1.5 bg-white px-4 py-5 text-center hover:bg-slate-50 transition">
                        <span class="text-3xl">{{ cat.icon || '📦' }}</span>
                        <p class="text-xs font-bold text-slate-700">{{ cat.name }}</p>
                        <p class="text-2xl font-black text-slate-900">{{ cat.total }}</p>
                        <p class="text-[11px] text-slate-500">{{ cat.active }} aktif</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast -->
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0 translate-y-2" leave-active-class="transition ease-in duration-150" leave-to-class="opacity-0 translate-y-2">
            <div v-if="toast.show" class="fixed bottom-5 right-5 z-50 flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold shadow-xl"
                 :class="toast.type === 'success' ? 'border-emerald-200 bg-emerald-50 text-emerald-800' : 'border-rose-200 bg-rose-50 text-rose-800'">
                {{ toast.type === 'success' ? '✓' : '✕' }} {{ toast.message }}
            </div>
        </Transition>
    </LawangsewuLayout>
</template>
