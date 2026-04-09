<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    appMeta:          { type: Object, required: true },
    navGroups:        { type: Array,  default: () => [] },
    pendopoUrl:       { type: String, required: true },
    guestbookListUrl: { type: String, required: true },
    stats: {
        type: Object,
        default: () => ({ day: 0, week: 0, month: 0, year: 0, all: 0 }),
    },
});

const isLoading = ref(true);

const handleLoad = () => {
    isLoading.value = false;
};
</script>

<template>
    <Head title="Buku Tamu Pendopo" />

    <LawangsewuLayout current-route="satellite.pendopo" :nav-groups="navGroups" :app-meta="appMeta">
        <div class="flex h-[calc(100vh-88px)] flex-col gap-4">

            <!-- Stats + Action Bar -->
            <section class="shrink-0 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-5 py-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                    <!-- Title -->
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-sm font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Buku Tamu Pendopo</h1>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-3)]">Modul Satelit Aktif</p>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="flex flex-wrap gap-2">
                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Hari Ini</p>
                            <p class="text-lg font-black text-[var(--text-1)]">{{ stats.day }}</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Minggu Ini</p>
                            <p class="text-lg font-black text-[var(--text-1)]">{{ stats.week }}</p>
                        </div>
                        <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Bulan Ini</p>
                            <p class="text-lg font-black text-[var(--text-1)]">{{ stats.month }}</p>
                        </div>
                        <div class="rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-2 text-center">
                            <p class="text-[9px] font-black uppercase tracking-[0.2em] text-amber-500">Total Tamu</p>
                            <p class="text-lg font-black text-amber-400">{{ stats.all }}</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center gap-2">
                        <div v-if="isLoading" class="flex items-center gap-2 rounded-lg border border-amber-500/20 bg-amber-500/10 px-3 py-1.5">
                            <div class="h-2 w-2 animate-ping rounded-full bg-amber-500"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-amber-500">Menghubungkan...</span>
                        </div>
                        <a :href="guestbookListUrl" class="secondary-button text-[10px] font-black uppercase">
                            Riwayat Tamu
                        </a>
                        <a :href="pendopoUrl" target="_blank" class="secondary-button text-[10px] font-black uppercase">
                            Buka Tab Baru
                        </a>
                    </div>
                </div>
            </section>

            <!-- Satellite Frame -->
            <div class="relative min-h-0 flex-1 overflow-hidden rounded-2xl border border-[var(--border)]">
                <iframe
                    :src="pendopoUrl"
                    class="h-full w-full border-0 bg-white"
                    @load="handleLoad"
                    title="Pendopo Guestbook"
                    allow="camera; microphone"
                ></iframe>

                <!-- Loading Overlay -->
                <div v-if="isLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-[var(--surface-1)]/80 backdrop-blur-md">
                    <div class="relative mb-6 h-24 w-24">
                        <div class="absolute inset-0 animate-pulse rounded-3xl bg-amber-500/10"></div>
                        <div class="absolute inset-4 flex items-center justify-center rounded-2xl bg-amber-600 text-2xl font-black text-white shadow-xl shadow-amber-600/30">
                            P
                        </div>
                    </div>
                    <p class="animate-pulse text-xs font-black uppercase tracking-[0.5em]">Menyiapkan Akses Pendopo</p>
                </div>
            </div>

        </div>
    </LawangsewuLayout>
</template>
