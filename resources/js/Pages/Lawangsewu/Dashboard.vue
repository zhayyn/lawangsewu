<script setup>
import CameraTile from '@/Components/lawangsewu/CameraTile.vue';
import ChatBubble from '@/Components/lawangsewu/ChatBubble.vue';
import ModuleShortcutCard from '@/Components/lawangsewu/ModuleShortcutCard.vue';
import SectionHeader from '@/Components/lawangsewu/SectionHeader.vue';
import StatCard from '@/Components/lawangsewu/StatCard.vue';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    quickActions: { type: Array, default: () => [] },
    metrics: { type: Array, default: () => [] },
    hearings: { type: Array, default: () => [] },
    modules: { type: Array, default: () => [] },
    alerts: { type: Array, default: () => [] },
    systemHealth: { type: Array, default: () => [] },
    cameras: { type: Array, default: () => [] },
    messages: { type: Array, default: () => [] },
    channels: { type: Array, default: () => [] },
});

</script>

<template>
    <Head title="Dashboard Utama" />

    <LawangsewuLayout
        current-route="dashboard"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">
            <section class="card-surface overflow-hidden p-6 lg:p-8">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.55fr),360px]">
                    <div class="space-y-5">
                        <div class="inline-flex items-center gap-2 rounded-full border border-[var(--accent-border)] bg-[var(--accent-soft)] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-1)]">
                            Portal internal premium
                        </div>

                        <div class="space-y-3">
                            <h1 class="max-w-4xl text-3xl font-semibold tracking-tight text-[var(--text-1)] sm:text-4xl xl:text-5xl">
                                Satu komando untuk pelayanan, pemantauan CCTV, chat internal, dan kesiapan integrasi SIPP.
                            </h1>
                            <p class="max-w-3xl text-sm leading-7 text-[var(--text-2)] md:text-base">
                                Landing dashboard ini mengikuti blueprint Lawangsewu Sprint 1: shell internal yang bisa langsung
                                dipakai sebagai fondasi frontend lanjutan, dengan fokus nyata pada monitoring live stream kamera
                                dan koordinasi antarbagian.
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Link
                                :href="route('lawangsewu.cctv')"
                                class="github-button"
                            >
                                Buka Monitoring CCTV
                            </Link>
                            <Link
                                :href="route('lawangsewu.chat')"
                                class="secondary-button"
                            >
                                Buka Chat Internal
                            </Link>
                            <button
                                type="button"
                                class="secondary-button"
                            >
                                Sinkronisasi SIPP
                            </button>
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <div
                                v-for="health in systemHealth"
                                :key="health.label"
                                class="card-muted p-4"
                            >
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-2)]">
                                    {{ health.label }}
                                </p>
                                <p class="mt-2 text-base font-semibold text-[var(--text-1)]">
                                    {{ health.value }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="card-muted space-y-4 p-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-2)]">
                                Status operasional
                            </p>
                            <h2 class="mt-2 text-xl font-semibold tracking-tight text-[var(--text-1)]">
                                {{ appMeta.status }}
                            </h2>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="alert in alerts"
                                :key="alert.title"
                                class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <span
                                        class="mt-1 h-2.5 w-2.5 rounded-full"
                                        :class="{
                                            'bg-emerald-500': alert.tone === 'emerald',
                                            'bg-amber-500': alert.tone === 'amber',
                                            'bg-sky-500': alert.tone === 'blue',
                                        }"
                                    />
                                    <div>
                                        <p class="text-sm font-semibold text-[var(--text-1)]">
                                            {{ alert.title }}
                                        </p>
                                        <p class="mt-1 text-sm leading-6 text-[var(--text-2)]">
                                            {{ alert.detail }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-surface p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-2)]">
                                Kanal aktif
                            </p>
                            <div class="mt-3 space-y-3">
                                <div
                                    v-for="channel in channels"
                                    :key="channel.key"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="text-[var(--text-2)]">
                                        {{ channel.label }}
                                    </span>
                                    <span class="font-semibold text-[var(--text-1)]">
                                        {{ channel.count }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    v-for="metric in metrics"
                    :key="metric.title"
                    :metric="metric"
                />
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr),520px]">
                <div class="space-y-6">
                    <section class="card-surface p-6">
                        <SectionHeader
                            eyebrow="Pelayanan & Persidangan"
                            title="Jadwal sidang hari ini"
                            description="Tabel ini meniru ruang kontrol internal untuk agenda persidangan. Nantinya bisa dihubungkan ke SIPP Hub begitu sinkronisasi backend siap."
                        />

                        <div class="mt-5 overflow-hidden rounded-[24px] border border-[var(--border)]">
                            <div class="hidden grid-cols-[100px,1.2fr,1.5fr,1fr,1fr] gap-4 bg-[var(--surface-2)] px-5 py-3 text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-2)] md:grid">
                                <span>Waktu</span>
                                <span>Ruang</span>
                                <span>Perkara</span>
                                <span>Majelis</span>
                                <span>Status</span>
                            </div>

                            <div class="divide-y divide-[var(--border)]">
                                <div
                                    v-for="hearing in hearings"
                                    :key="`${hearing.time}-${hearing.case}`"
                                    class="grid gap-3 px-5 py-4 md:grid-cols-[100px,1.2fr,1.5fr,1fr,1fr]"
                                >
                                    <div class="text-sm font-semibold text-[var(--text-1)]">
                                        {{ hearing.time }}
                                    </div>
                                    <div class="text-sm text-[var(--text-2)]">
                                        {{ hearing.room }}
                                    </div>
                                    <div class="text-sm text-[var(--text-1)]">
                                        {{ hearing.case }}
                                    </div>
                                    <div class="text-sm text-[var(--text-2)]">
                                        {{ hearing.judge }}
                                    </div>
                                    <div class="text-sm font-medium text-emerald-500">
                                        {{ hearing.status }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="card-surface p-6">
                        <SectionHeader
                            eyebrow="Launcher"
                            title="Shortcut modul satelit"
                            description="Modul di luar Sprint 1 tetap ditampilkan sebagai pintu masuk yang konsisten dengan blueprint Lawangsewu."
                        />

                        <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <ModuleShortcutCard
                                v-for="module in modules"
                                :key="module.title"
                                :module="module"
                            />
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="card-surface p-6">
                        <SectionHeader
                            eyebrow="Live preview"
                            title="CCTV prioritas"
                            description="Empat stream utama dipasang langsung untuk memastikan dashboard tetap terasa operasional."
                            action-label="Full screen CCTV"
                            :action-href="route('lawangsewu.cctv')"
                        />

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <CameraTile
                                v-for="camera in cameras"
                                :key="camera.key"
                                :camera="camera"
                                preview-height="h-40"
                            />
                        </div>
                    </section>

                    <section class="card-surface p-6">
                        <SectionHeader
                            eyebrow="Interkom"
                            title="Chat internal"
                            description="Contoh bubble chat yang siap di-upgrade ke Reverb atau provider real-time lain."
                            action-label="Buka chat"
                            :action-href="route('lawangsewu.chat')"
                        />

                        <div class="mt-5 space-y-4">
                            <ChatBubble
                                v-for="message in messages"
                                :key="message.id"
                                :message="message"
                                :own="message.alias === 'Arjuna_Hukum'"
                            />
                        </div>

                        <div class="mt-5 flex items-center gap-3 rounded-[24px] border border-[var(--border)] bg-[var(--surface-2)] p-3">
                            <input
                                type="text"
                                class="input-surface flex-1"
                                placeholder="Ketik pesan di sini..."
                                disabled
                            >
                            <button
                                type="button"
                                class="github-button"
                            >
                                Kirim
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>

        <!-- Dashboard Footer -->
        <div class="mt-12 py-10 border-t border-[var(--border)] flex flex-col items-center gap-6">
            <div class="flex items-center gap-8 opacity-40">
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Sprint v1.2</span>
                <div class="w-1.5 h-1.5 rounded-full bg-[var(--border-strong)]"></div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Internal System</span>
            </div>
            
            <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-[var(--text-3)] text-shadow-sm">
                developed with <span class="text-red-500 animate-pulse mx-1 inline-block">❤</span> by dubes prakom pa semarang
            </p>
        </div>
    </LawangsewuLayout>
</template>
