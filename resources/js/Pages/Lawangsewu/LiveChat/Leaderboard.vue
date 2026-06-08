<script setup>
import { Head } from '@inertiajs/vue3'
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue'

const props = defineProps({
    appMeta: Object,
    navGroups: Array,
    mostConversations: Array,
    mostResponsive: Array
})
</script>

<template>
    <Head title="Leaderboard - Live Chat PTSP" />
    <LawangsewuLayout current-route="livechat.leaderboard" :appMeta="appMeta" :navGroups="navGroups">
        <div class="px-4 sm:px-6 lg:px-8 py-6 max-w-screen-xl mx-auto">
            <h1 class="text-2xl font-bold text-slate-100 mb-6 flex items-center gap-3">
                <span class="text-3xl">🏆</span> Leaderboard Petugas PTSP
            </h1>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Most Active -->
                <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6">
                    <h2 class="text-lg font-bold text-amber-400 mb-4 border-b border-slate-700 pb-2">🥇 Paling Aktif Menjawab</h2>
                    <div class="space-y-4">
                        <div v-if="mostConversations.length === 0" class="text-slate-500 text-sm">Belum ada data percakapan.</div>
                        <div v-for="(user, index) in mostConversations" :key="index" class="flex justify-between items-center bg-slate-800 p-3 rounded-xl border border-slate-700">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold" :class="index === 0 ? 'text-amber-400' : (index === 1 ? 'text-slate-300' : (index === 2 ? 'text-amber-700' : 'text-slate-500'))">#{{ index + 1 }}</span>
                                <span class="font-semibold text-slate-200">{{ user.name }}</span>
                            </div>
                            <span class="bg-emerald-500/20 text-emerald-400 px-3 py-1 rounded-full text-xs font-bold">{{ user.total_handled }} Chat</span>
                        </div>
                    </div>
                </div>

                <!-- Most Responsive -->
                <div class="bg-slate-900 border border-slate-700 rounded-2xl p-6">
                    <h2 class="text-lg font-bold text-emerald-400 mb-4 border-b border-slate-700 pb-2">⚡ Paling Responsif (Waktu Balas)</h2>
                    <div class="space-y-4">
                        <div v-if="mostResponsive.length === 0" class="text-slate-500 text-sm">Belum ada data waktu respons.</div>
                        <div v-for="(user, index) in mostResponsive" :key="index" class="flex justify-between items-center bg-slate-800 p-3 rounded-xl border border-slate-700">
                            <div class="flex items-center gap-3">
                                <span class="text-xl font-bold" :class="index === 0 ? 'text-emerald-400' : 'text-slate-500'">#{{ index + 1 }}</span>
                                <span class="font-semibold text-slate-200">{{ user.name }}</span>
                            </div>
                            <span class="bg-blue-500/20 text-blue-400 px-3 py-1 rounded-full text-xs font-bold">{{ (user.avg_response_time_ms / 1000).toFixed(1) }} Detik</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </LawangsewuLayout>
</template>
