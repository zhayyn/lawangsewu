<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { useReverb } from '@/composables/useReverb';

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
    hearing_number: '',
    courtroom: 'Ruang Sidang 1',
    parties: '',
    hearing_time: '',
});

const submitting = ref(false);
const actionLoading = ref(null);
const todayTickets = ref([...props.todayTickets]);
const summary = ref({ ...props.summary });
const activeCall = ref(props.activeCall ? { ...props.activeCall } : null);

const { connected: reverbConnected, reconnecting: reverbReconnecting, error: reverbError, subscribeQueue } = useReverb();
let unsubscribeQueue = null;

function submitTicket() {
    submitting.value = true;
    router.post('/antrian-sidang-v2', form.value, {
        preserveScroll: true,
        onFinish: () => { submitting.value = false; },
        onSuccess: () => {
            form.value.hearing_number = '';
            form.value.parties = '';
            form.value.hearing_time = '';
        },
    });
}

function callTicket(ticket) {
    actionLoading.value = ticket.id;
    router.post(`/antrian-sidang-v2/${ticket.id}/call`, {}, {
        preserveScroll: true,
        onFinish: () => { actionLoading.value = null; },
    });
}

function completeTicket(ticket) {
    actionLoading.value = ticket.id;
    router.post(`/antrian-sidang-v2/${ticket.id}/complete`, {}, {
        preserveScroll: true,
        onFinish: () => { actionLoading.value = null; },
    });
}

function postponeTicket(ticket) {
    actionLoading.value = ticket.id;
    router.post(`/antrian-sidang-v2/${ticket.id}/postpone`, {}, {
        preserveScroll: true,
        onFinish: () => { actionLoading.value = null; },
    });
}

const statusColors = {
    waiting:   { bg: 'bg-blue-500/10 text-blue-400 border-blue-500/20', label: 'Menunggu' },
    called:    { bg: 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20', label: 'Dipanggil' },
    completed: { bg: 'bg-violet-500/10 text-violet-400 border-violet-500/20', label: 'Selesai' },
    postponed: { bg: 'bg-amber-500/10 text-amber-400 border-amber-500/20', label: 'Ditunda' },
};

const courtroomOptions = computed(() =>
    props.counters.length > 0
        ? props.counters.map(c => c.name)
        : ['Ruang Sidang 1', 'Ruang Sidang 2', 'Ruang Sidang 3', 'Ruang Mediasi']
);

onMounted(() => {
    unsubscribeQueue = subscribeQueue('sidang', (event) => {
        if (event?.summary) {
            summary.value = { ...event.summary };
        }

        if (event?.ticket?.id) {
            const idx = todayTickets.value.findIndex((item) => item.id === event.ticket.id);
            if (idx >= 0) {
                todayTickets.value[idx] = {
                    ...todayTickets.value[idx],
                    ...event.ticket,
                };
            } else {
                todayTickets.value.unshift({ ...event.ticket });
            }
        }

        if (event?.ticket?.status === 'called') {
            activeCall.value = {
                id: event.ticket.id,
                ticket_number: event.ticket.ticket_number,
                courtroom: event.ticket.courtroom,
                hearing_number: event.ticket.hearing_number,
                called_at: event.ticket.called_at ?? null,
            };
        } else if (activeCall.value?.id === event?.ticket?.id) {
            const stillCalled = todayTickets.value.find((item) => item.status === 'called');
            activeCall.value = stillCalled
                ? {
                    id: stillCalled.id,
                    ticket_number: stillCalled.ticket_number,
                    courtroom: stillCalled.courtroom,
                    hearing_number: stillCalled.hearing_number,
                    called_at: stillCalled.called_at ?? null,
                }
                : null;
        }
    });
});

onUnmounted(() => {
    unsubscribeQueue?.();
});
</script>

<template>
    <Head title="Antrian Sidang" />

    <LawangsewuLayout
        current-route="sidang"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <!-- Hero -->
        <section class="card-surface overflow-hidden p-6 lg:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="space-y-2">
                    <div class="inline-flex items-center gap-2 rounded-full border border-violet-500/20 bg-violet-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-violet-400">
                        Kepaniteraan Persidangan
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight text-[var(--text-1)] sm:text-4xl">
                        Antrian Sidang
                    </h1>
                    <p class="max-w-xl text-sm leading-7 text-[var(--text-2)]">
                        Kelola antrean pemanggilan sidang per ruang sidang secara cepat dan terstruktur.
                    </p>
                    <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[10px] font-semibold uppercase tracking-[0.2em]"
                        :class="reverbConnected ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-300'">
                        <span class="h-2 w-2 rounded-full" :class="reverbConnected ? 'bg-emerald-300' : 'bg-amber-300'" />
                        {{ reverbConnected ? 'Realtime aktif' : (reverbReconnecting ? 'Realtime reconnecting' : 'Realtime fallback polling') }}
                    </div>
                    <p v-if="reverbError" class="text-xs text-amber-300">{{ reverbError }}</p>
                </div>

                <!-- Active Call Banner -->
                <div
                    v-if="activeCall"
                    class="min-w-[220px] rounded-2xl border border-violet-500/30 bg-violet-500/10 p-4 text-center"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-400">Sedang Dipanggil</p>
                    <p class="mt-1 text-4xl font-bold tracking-tight text-violet-300">{{ activeCall.ticket_number }}</p>
                    <p class="mt-1 text-xs text-[var(--text-2)]">{{ activeCall.courtroom }}</p>
                    <p class="mt-0.5 text-xs text-[var(--text-3)]">{{ activeCall.hearing_number }}</p>
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
                <p class="mt-2 text-4xl font-bold text-violet-400">{{ summary.completed ?? 0 }}</p>
            </div>
            <div class="card-surface p-5 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-3)]">Ditunda</p>
                <p class="mt-2 text-4xl font-bold text-amber-400">{{ summary.postponed ?? 0 }}</p>
            </div>
        </section>

        <!-- Main Content -->
        <div class="grid gap-6 lg:grid-cols-[360px,minmax(0,1fr)]">

            <!-- Form Panel -->
            <section v-if="canOperate" class="card-surface space-y-5 p-6">
                <h2 class="text-base font-semibold text-[var(--text-1)]">Tambah Antrian Sidang</h2>

                <form class="space-y-4" @submit.prevent="submitTicket">
                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Nomor Perkara</label>
                        <input
                            v-model="form.hearing_number"
                            required
                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-violet-500/60 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                            maxlength="80"
                            placeholder="Contoh: 112/Pdt.G/2026/PA.Smg"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Ruang Sidang</label>
                        <select
                            v-model="form.courtroom"
                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-violet-500/60 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                        >
                            <option v-for="room in courtroomOptions" :key="room" :value="room">{{ room }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Pihak <span class="normal-case font-normal">(opsional)</span></label>
                        <input
                            v-model="form.parties"
                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] placeholder:text-[var(--text-3)] focus:border-violet-500/60 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                            maxlength="255"
                            placeholder="Penggugat vs Tergugat"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">Jam Sidang <span class="normal-case font-normal">(opsional)</span></label>
                        <input
                            v-model="form.hearing_time"
                            type="time"
                            class="w-full rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-sm text-[var(--text-1)] focus:border-violet-500/60 focus:outline-none focus:ring-2 focus:ring-violet-500/20"
                        >
                    </div>

                    <button
                        type="submit"
                        :disabled="submitting"
                        class="w-full rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-violet-500 disabled:opacity-60"
                    >
                        {{ submitting ? 'Memproses…' : '+ Tambah Antrian' }}
                    </button>
                </form>
            </section>

            <!-- Viewer info panel -->
            <section v-else class="card-surface space-y-4 p-6">
                <h2 class="text-base font-semibold text-[var(--text-1)]">Status Persidangan</h2>
                <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] p-4 text-sm text-[var(--text-2)]">
                    Anda dapat memantau antrian sidang hari ini. Hubungi kepaniteraan untuk penambahan antrian.
                </div>
                <div v-if="activeCall" class="rounded-2xl border border-violet-500/20 bg-violet-500/8 p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-400">Sedang Dipanggil Sekarang</p>
                    <p class="mt-2 text-5xl font-bold tracking-tight text-violet-300">{{ activeCall.ticket_number }}</p>
                    <p class="mt-2 text-sm text-[var(--text-2)]">{{ activeCall.courtroom }}</p>
                    <p class="mt-0.5 text-xs text-[var(--text-3)]">{{ activeCall.hearing_number }}</p>
                </div>
            </section>

            <!-- Ticket Table -->
            <section class="card-surface p-6">
                <h2 class="mb-5 text-base font-semibold text-[var(--text-1)]">Daftar Antrian Sidang Hari Ini</h2>

                <div v-if="todayTickets.length === 0" class="py-10 text-center text-sm text-[var(--text-3)]">
                    Belum ada antrian sidang hari ini.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-[var(--border)] text-xs font-semibold uppercase tracking-[0.16em] text-[var(--text-3)]">
                                <th class="pb-3 text-left">No</th>
                                <th class="pb-3 text-left">Perkara</th>
                                <th class="pb-3 text-left">Ruang / Jam</th>
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
                                <td class="py-3.5 pr-4">
                                    <div class="font-medium text-[var(--text-1)]">{{ ticket.hearing_number }}</div>
                                    <div class="text-xs text-[var(--text-3)]">{{ ticket.parties || '' }}</div>
                                </td>
                                <td class="py-3.5 pr-4">
                                    <div class="text-[var(--text-2)]">{{ ticket.courtroom }}</div>
                                    <div class="text-xs text-[var(--text-3)]">{{ ticket.hearing_time_label || '—' }} WIB</div>
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
                                            v-if="['waiting', 'postponed'].includes(ticket.status)"
                                            :disabled="actionLoading === ticket.id"
                                            class="rounded-lg border border-blue-500/30 bg-blue-500/10 px-3 py-1.5 text-xs font-semibold text-blue-400 transition hover:bg-blue-500/20 disabled:opacity-50"
                                            @click="callTicket(ticket)"
                                        >
                                            Panggil
                                        </button>
                                        <button
                                            v-if="ticket.status === 'called'"
                                            :disabled="actionLoading === ticket.id"
                                            class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-400 transition hover:bg-emerald-500/20 disabled:opacity-50"
                                            @click="completeTicket(ticket)"
                                        >
                                            Selesai
                                        </button>
                                        <button
                                            v-if="ticket.status === 'called'"
                                            :disabled="actionLoading === ticket.id"
                                            class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-xs font-semibold text-amber-400 transition hover:bg-amber-500/20 disabled:opacity-50"
                                            @click="postponeTicket(ticket)"
                                        >
                                            Tunda
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
