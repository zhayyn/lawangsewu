<script setup>
import { ref, computed } from 'vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    todayTickets: { type: Array, default: () => [] },
    activeCall: { type: Object, default: null },
    summary: { type: Object, default: () => ({}) },
    counters: { type: Array, default: () => [] },
    flash: { type: Object, default: () => ({}) },
    canOperate: { type: Boolean, default: false },
});

const form = ref({
    service_desk: 'PTSP-1',
    visitor_name: '',
    purpose: '',
});

const submitting = ref(false);
const callLoading = ref(null);

function submitTicket() {
    submitting.value = true;
    router.post('/antrian-ptsp', form.value, {
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
        onSuccess: () => {
            form.value.visitor_name = '';
            form.value.purpose = '';
        },
    });
}

function callTicket(ticket) {
    callLoading.value = ticket.id;
    router.post(`/antrian-ptsp/${ticket.id}/call`, {}, {
        preserveScroll: true,
        onFinish: () => { callLoading.value = null; },
    });
}

function serveTicket(ticket) {
    callLoading.value = ticket.id;
    router.post(`/antrian-ptsp/${ticket.id}/serve`, {}, {
        preserveScroll: true,
        onFinish: () => { callLoading.value = null; },
    });
}

function skipTicket(ticket) {
    callLoading.value = ticket.id;
    router.post(`/antrian-ptsp/${ticket.id}/skip`, {}, {
        preserveScroll: true,
        onFinish: () => { callLoading.value = null; },
    });
}

const statusColors = {
    waiting:  { bg: 'bg-blue-500/10 text-blue-400 border-blue-500/20', label: 'Menunggu' },
    called:   { bg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', label: 'Dipanggil' },
    served:   { bg: 'bg-violet-500/10 text-violet-400 border-violet-500/20', label: 'Selesai' },
    skipped:  { bg: 'bg-amber-500/10 text-amber-400 border-amber-500/20', label: 'Dilewati' },
};

const serviceDesks = computed(() =>
    props.counters.length > 0
        ? props.counters.map(c => c.name)
        : ['PTSP-1', 'PTSP-2', 'PTSP-3', 'PTSP-4']
);
</script>

<template>
    <Head title="Antrian PTSP" />

    <LawangsewuLayout
        current-route="ptsp"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <!-- Hero -->
        <section class="card-surface overflow-hidden p-6 lg:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-blue-400">
                        Pelayanan Terpadu Satu Pintu
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-[var(--text-1)] sm:text-4xl">
                        Antrian PTSP
                    </h1>
                    <p class="max-w-xl text-sm leading-7 text-[var(--text-2)]">
                        Kelola nomor antrian, panggil tiket, dan tandai selesai layanan secara real-time.
                    </p>
                </div>

                <!-- Active Call Banner -->
                <div
                    v-if="activeCall"
                    class="min-w-[200px] rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-center"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400">Sedang Dipanggil</p>
                    <p class="mt-1 text-4xl font-bold tracking-tight text-emerald-300">{{ activeCall.ticket_number }}</p>
                    <p class="mt-1 text-xs text-[var(--text-2)]">{{ activeCall.service_desk }}</p>
                </div>
                <div
                    v-else
                    class="min-w-[200px] rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4 text-center"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Panggilan Aktif</p>
                    <p class="mt-2 text-sm text-[var(--text-3)]">Belum ada</p>
                </div>
            </div>
        </section>

        <!-- Flash message -->
        <div
            v-if="flash?.status"
            class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-5 py-3.5 text-sm font-medium text-emerald-300"
        >
            {{ flash.status }}
        </div>

        <!-- Summary Cards -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="card-surface p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Menunggu</p>
                <p class="mt-2 text-4xl font-bold text-blue-400">{{ summary.waiting ?? 0 }}</p>
            </div>
            <div class="card-surface p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Dipanggil</p>
                <p class="mt-2 text-4xl font-bold text-emerald-400">{{ summary.called ?? 0 }}</p>
            </div>
            <div class="card-surface p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Selesai</p>
                <p class="mt-2 text-4xl font-bold text-violet-400">{{ summary.served ?? 0 }}</p>
            </div>
            <div class="card-surface p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Dilewati</p>
                <p class="mt-2 text-4xl font-bold text-amber-400">{{ summary.skipped ?? 0 }}</p>
            </div>
        </section>

        <!-- Main Content -->
        <div class="grid gap-6 lg:grid-cols-[360px,minmax(0,1fr)]">

            <!-- Form Panel (hanya untuk operator+) -->
            <section v-if="canOperate" class="card-surface space-y-5 p-6">
                <h2 class="text-base font-semibold text-[var(--text-1)]">Tambah Nomor Antrian</h2>

                <form class="space-y-4" @submit.prevent="submitTicket">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Loket</label>
                        <select
                            v-model="form.service_desk"
                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-blue-500/60 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        >
                            <option v-for="desk in serviceDesks" :key="desk" :value="desk">{{ desk }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Nama Tamu <span class="normal-case font-normal">(opsional)</span></label>
                        <input
                            v-model="form.visitor_name"
                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-blue-500/60 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            maxlength="120"
                            placeholder="Nama pengunjung"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Keperluan <span class="normal-case font-normal">(opsional)</span></label>
                        <input
                            v-model="form.purpose"
                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-blue-500/60 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            maxlength="255"
                            placeholder="Contoh: Legalisir dokumen"
                        >
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="w-full rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-500 disabled:opacity-60"
                    >
                        {{ submitting ? 'Memproses…' : '+ Ambil Nomor Baru' }}
                    </button>
                </form>
            </section>

            <!-- Viewer info panel (tidak bisa operasi) -->
            <section v-else class="card-surface space-y-4 p-6">
                <h2 class="text-base font-semibold text-[var(--text-1)]">Status Antrian</h2>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] p-4 text-sm text-[var(--text-2)]">
                    Anda dapat memantau antrian hari ini. Hubungi operator untuk penambahan nomor antrian.
                </div>
                <div v-if="activeCall" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/8 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-400">Sedang Dipanggil Sekarang</p>
                    <p class="mt-2 text-5xl font-bold tracking-tight text-emerald-300">{{ activeCall.ticket_number }}</p>
                    <p class="mt-2 text-sm text-[var(--text-2)]">{{ activeCall.service_desk }}</p>
                </div>
            </section>

            <!-- Ticket Table -->
            <section class="card-surface p-6">
                <h2 class="mb-5 text-base font-semibold text-[var(--text-1)]">Daftar Antrian Hari Ini</h2>

                <div v-if="todayTickets.length === 0" class="py-10 text-center text-sm text-[var(--text-3)]">
                    Belum ada antrian hari ini.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--border)] text-xs font-semibold uppercase tracking-[0.16em] text-[var(--text-3)]">
                                <th class="pb-3 text-left">No</th>
                                <th class="pb-3 text-left">Loket</th>
                                <th class="pb-3 text-left">Nama / Keperluan</th>
                                <th class="pb-3 text-left">Status</th>
                                <th v-if="canOperate" class="pb-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]">
                            <tr
                                v-for="ticket in todayTickets"
                                :key="ticket.id"
                                class="transition hover:bg-[var(--surface-2)]"
                            >
                                <td class="py-3.5 pr-4 font-bold text-[var(--text-1)]">{{ ticket.ticket_number }}</td>
                                <td class="py-3.5 pr-4 text-[var(--text-2)]">{{ ticket.service_desk }}</td>
                                <td class="py-3.5 pr-4">
                                    <div class="font-medium text-[var(--text-1)]">{{ ticket.visitor_name || '—' }}</div>
                                    <div class="text-xs text-[var(--text-3)]">{{ ticket.purpose || '' }}</div>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <span
                                        :class="['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold', statusColors[ticket.status]?.bg ?? 'bg-[var(--surface-2)] text-[var(--text-2)]']"
                                    >
                                        {{ statusColors[ticket.status]?.label ?? ticket.status }}
                                    </span>
                                </td>
                                <td v-if="canOperate" class="py-3.5 text-right">
                                    <div class="inline-flex gap-2">
                                        <button
                                            v-if="['waiting', 'skipped'].includes(ticket.status)"
                                            :disabled="callLoading === ticket.id"
                                            class="rounded-lg border border-blue-500/30 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-400 transition hover:bg-blue-500/20 disabled:opacity-50"
                                            @click="callTicket(ticket)"
                                        >
                                            Panggil
                                        </button>
                                        <button
                                            v-if="ticket.status === 'called'"
                                            :disabled="callLoading === ticket.id"
                                            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-400 transition hover:bg-emerald-500/20 disabled:opacity-50"
                                            @click="serveTicket(ticket)"
                                        >
                                            Layani
                                        </button>
                                        <button
                                            v-if="ticket.status === 'called'"
                                            :disabled="callLoading === ticket.id"
                                            class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-400 transition hover:bg-amber-500/20 disabled:opacity-50"
                                            @click="skipTicket(ticket)"
                                        >
                                            Lewati
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </LawangsewuLayout>
</template>
