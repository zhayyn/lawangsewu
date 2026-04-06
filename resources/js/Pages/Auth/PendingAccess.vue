<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const props = defineProps({
    reason: {
        type: String,
        default: 'pending',
    },
});

const page = usePage();
const flash = page.props.flash;
const error = page.props.error;
</script>

<template>
    <GuestLayout>
        <Head title="Akses Menunggu Persetujuan" />

        <div class="space-y-6 text-center">
            <!-- Icon -->
            <div v-if="reason !== 'error'" class="mx-auto h-24 w-24 rounded-full bg-[var(--accent-soft)] flex items-center justify-center animated-pulse">
                <svg class="w-12 h-12 text-[var(--accent-default)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m-8-3h8m3-3V6m-4 14H8a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v10a2 2 0 01-2 2z" />
                </svg>
            </div>
            <div v-else class="mx-auto h-24 w-24 rounded-full bg-red-500/10 flex items-center justify-center">
                <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 0H9m3 0h3m-3-4H9m3 0h3" />
                </svg>
            </div>

            <!-- Title & Messages -->
            <div class="space-y-3">
                <h1 class="text-2xl font-bold text-[var(--text-1)]">
                    <template v-if="reason === 'unregistered'">
                        Akun Google Anda Terdaftar
                    </template>
                    <template v-else-if="reason === 'pending'">
                        Menunggu Persetujuan Admin
                    </template>
                    <template v-else-if="reason === 'error'">
                        Terjadi Kesalahan
                    </template>
                    <template v-else>
                        Akses Ditunda
                    </template>
                </h1>

                <!-- Detailed messages -->
                <div class="space-y-2">
                    <p v-if="reason === 'error'" class="text-sm text-red-600 leading-relaxed font-bold bg-red-500/5 p-4 rounded-xl border border-red-500/10">
                        {{ error || flash?.message || 'Login Google gagal diproses. Silakan coba lagi atau hubungi administrator.' }}
                    </p>

                    <p v-else class="text-sm text-[var(--text-2)] leading-relaxed px-4">
                        {{ flash?.message || (reason === 'unregistered' ? 'Akun Google Anda telah berhasil terdaftar. Silakan menghubungi admin untuk mengaktifkan akses Anda.' : 'Akun Anda sudah tercatat, namun masih menunggu persetujuan administrator.') }}
                    </p>
                </div>

                <!-- Contact info card -->
                <div class="mt-6 rounded-2xl border border-blue-500/30 bg-blue-500/[0.02] p-6 space-y-3 transition-all hover:bg-blue-500/[0.05]">
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-600 opacity-60">
                        Layanan Aktivasi
                    </p>
                    <p class="text-sm font-black text-[var(--text-1)]">
                        Dubes Prakom PA Semarang
                    </p>
                    <div class="flex justify-center">
                        <a
                            :href="`mailto:${flash?.email || 'dbprakom@gmail.com'}`"
                            class="inline-flex items-center gap-3 px-5 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 transition-all shadow-lg shadow-blue-600/20 font-bold text-xs"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                            </svg>
                            {{ flash?.email || 'dbprakom@gmail.com' }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="pt-4 flex flex-col gap-3">
                <Link
                    :href="route('login')"
                    class="secondary-button"
                >
                    Kembali ke Login
                </Link>
                <p class="text-[10px] text-[var(--text-3)] font-medium">
                    Estimasi aktivasi: <span class="text-[var(--text-2)]">1-2 jam kerja</span>
                </p>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
.animated-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.5;
    }
}
</style>
