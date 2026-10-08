<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    syncStatus: { type: Object, required: true },
    syncLog: { type: Array, default: () => [] },
});

const statusToneClass = (status) => {
    if (status === 'SUCCESS') return 'text-emerald-300 border-emerald-500/20 bg-emerald-500/10';
    if (status === 'FAILED') return 'text-rose-300 border-rose-500/20 bg-rose-500/10';
    return 'text-amber-300 border-amber-500/20 bg-amber-500/10';
};
</script>

<template>
    <Head title="Pasemarang Sync Monitor" />

    <LawangsewuLayout
        current-route="admin-pasemarang-sync"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-2xl shadow-black/10">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex rounded-full border border-sky-500/20 bg-sky-500/10 px-4 py-2 text-[11px] font-black uppercase tracking-[0.22em] text-sky-300">
                            Backup & Sync Monitor
                        </span>
                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-[var(--text-1)]">Pasemarang Sync Status</h1>
                            <p class="mt-2 max-w-3xl text-sm text-[var(--text-3)]">
                                Memantau sinkronisasi database dan file WordPress dari Rumahweb ke Server 9.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-right">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Last Run</p>
                        <p class="mt-1 text-sm font-bold text-[var(--text-1)]">{{ syncStatus.last_run }}</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-[1.5rem] border p-5" :class="statusToneClass(syncStatus.status)">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em]">Sync Status</p>
                    <p class="mt-3 text-2xl font-black uppercase tracking-[0.1em]">{{ syncStatus.status }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-indigo-500/20 bg-indigo-500/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-300">Duration</p>
                    <p class="mt-3 text-2xl font-black text-indigo-200">{{ syncStatus.duration }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-cyan-500/20 bg-cyan-500/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-300">Rumahweb Disk</p>
                    <p class="mt-3 text-xl font-black text-cyan-200">{{ syncStatus.rw_disk }}</p>
                </div>
                <div class="rounded-[1.5rem] border border-fuchsia-500/20 bg-fuchsia-500/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-fuchsia-300">Server 9 Disk</p>
                    <p class="mt-3 text-xl font-black text-fuchsia-200">{{ syncStatus.s9_disk }}</p>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Sync Log Output</h2>
                <div class="mt-5 rounded-2xl border border-[var(--border)] bg-[#0d1117] p-4 overflow-x-auto">
                    <pre class="text-[11px] font-mono leading-relaxed text-gray-300 whitespace-pre-wrap"><template v-if="syncLog.length"><span v-for="(line, index) in syncLog" :key="index" class="block">{{ line }}</span></template><template v-else><span class="italic text-gray-500">Belum ada log yang tersimpan.</span></template></pre>
                </div>
            </section>
        </div>
    </LawangsewuLayout>
</template>
