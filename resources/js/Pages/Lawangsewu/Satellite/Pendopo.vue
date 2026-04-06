<script setup>
import SectionHeader from '@/Components/lawangsewu/SectionHeader.vue';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    pendopoUrl: {
        type: String,
        required: true,
    },
});

const isLoading = ref(true);

const handleLoad = () => {
    isLoading.value = false;
};
</script>

<template>
    <Head title="Buku Tamu Pendopo" />

    <LawangsewuLayout current-route="satellite.pendopo">
        <div class="space-y-6 h-[calc(100vh-140px)] flex flex-col">
            <!-- Header Section -->
            <section class="card-surface p-5 py-4 shrink-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-600/10 text-amber-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-black uppercase tracking-[0.2em] text-[var(--text-1)]">Buku Tamu Pendopo</h1>
                            <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest italic">Modul Satelit Aktif - Register Tamu Digital</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <div v-if="isLoading" class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-500 border border-amber-500/20">
                            <div class="h-2 w-2 rounded-full bg-amber-500 animate-ping"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest">Menghubungkan...</span>
                        </div>
                        <a :href="pendopoUrl" target="_blank" class="secondary-button text-[10px] uppercase font-black">
                            Buka Tab Baru
                        </a>
                    </div>
                </div>
            </section>

            <!-- Satellite Frame -->
            <div class="flex-1 relative card-surface overflow-hidden !p-0">
                <iframe 
                    :src="pendopoUrl" 
                    class="w-full h-full border-0 rounded-2xl bg-white"
                    @load="handleLoad"
                    title="Pendopo Guestbook"
                    allow="camera; microphone"
                ></iframe>
                
                <!-- Loading Overlay -->
                <div v-if="isLoading" class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-[var(--surface-1)]/80 backdrop-blur-md">
                     <div class="relative w-24 h-24 mb-6">
                        <div class="absolute inset-0 rounded-3xl bg-amber-500/10 animate-pulse"></div>
                        <div class="absolute inset-4 rounded-2xl bg-amber-600 flex items-center justify-center text-white font-black text-2xl shadow-xl shadow-amber-600/30">
                            P
                        </div>
                    </div>
                    <p class="text-xs font-black uppercase tracking-[0.5em] animate-pulse">Menyiapkan Akses Pendopo</p>
                </div>
            </div>
        </div>
    </LawangsewuLayout>
</template>

<style scoped>
/* Ensure the iframe container fills space correctly */
.card-surface {
    background: transparent !important;
}
</style>
