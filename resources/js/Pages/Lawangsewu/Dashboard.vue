<script setup>
import ChatBubble from '@/Components/lawangsewu/ChatBubble.vue';
import ModuleShortcutCard from '@/Components/lawangsewu/ModuleShortcutCard.vue';
import SectionHeader from '@/Components/lawangsewu/SectionHeader.vue';
import StatCard from '@/Components/lawangsewu/StatCard.vue';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const page = usePage();
const isViewer = computed(() => page.props.auth?.user?.role === 'viewer');
const isOperator = computed(() => page.props.auth?.user?.role === 'operator');
const waCarakaHref = computed(() => props.modules.find((module) => module.title === 'WA Caraka')?.href || null);
const ptspQueueValue = computed(() => props.metrics.find((metric) => metric.title === 'Antrian PTSP')?.value ?? '-');
const chatChannelCount = computed(() => props.channels.find((channel) => channel.key === 'interkom-umum')?.count ?? 0);
const waStatus = computed(() => props.systemHealth.find((item) => item.label === 'WA Caraka')?.value ?? 'Siap terhubung');

</script>

<template>
    <Head title="Dashboard Utama" />

    <LawangsewuLayout
        current-route="dashboard"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div v-if="isViewer" class="space-y-6">
            <section class="card-surface overflow-hidden p-6 lg:p-8">
                <div class="max-w-3xl space-y-4">
                    <div class="inline-flex items-center gap-2 rounded-full border border-[var(--accent-border)] bg-[var(--accent-soft)] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-1)]">
                        Viewer workspace
                    </div>

                    <div class="space-y-3">
                        <h1 class="max-w-3xl text-3xl font-semibold tracking-tight text-[var(--text-1)] sm:text-4xl">
                            Akses ringkas untuk pemantauan CCTV dan chat internal.
                        </h1>
                        <p class="max-w-2xl text-sm leading-7 text-[var(--text-2)] md:text-base">
                            Tampilan viewer disederhanakan supaya fokus pada dua kebutuhan utama: melihat monitoring kamera dan membuka komunikasi internal.
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-5 lg:grid-cols-2">
                <Link
                    :href="route('lawangsewu.cctv')"
                    class="group relative overflow-hidden rounded-[2rem] border border-cyan-500/20 bg-[radial-gradient(circle_at_top_right,rgba(34,211,238,0.16),transparent_28%),linear-gradient(145deg,rgba(8,47,73,0.92),rgba(8,15,26,0.98))] p-6 text-white transition duration-300 hover:-translate-y-1 hover:border-cyan-400/40"
                >
                    <div class="space-y-4">
                        <div class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-cyan-200">
                            Monitoring
                        </div>
                        <h2 class="text-2xl font-black tracking-tight">Monitoring CCTV</h2>
                        <p class="max-w-md text-sm leading-7 text-slate-200/85">
                            Buka wall monitoring untuk memantau seluruh kamera aktif dengan tampilan responsif dan fokus layar penuh.
                        </p>
                        <span class="inline-flex items-center gap-2 text-sm font-bold text-cyan-200">
                            Buka monitoring
                            <span aria-hidden="true">→</span>
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('lawangsewu.chat')"
                    class="group relative overflow-hidden rounded-[2rem] border border-blue-500/20 bg-[radial-gradient(circle_at_top_right,rgba(59,130,246,0.18),transparent_26%),linear-gradient(145deg,rgba(15,23,42,0.94),rgba(10,15,28,0.98))] p-6 text-white transition duration-300 hover:-translate-y-1 hover:border-blue-400/40"
                >
                    <div class="space-y-4">
                        <div class="inline-flex rounded-full border border-blue-300/20 bg-blue-300/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-blue-200">
                            Komunikasi
                        </div>
                        <h2 class="text-2xl font-black tracking-tight">Chat Internal</h2>
                        <p class="max-w-md text-sm leading-7 text-slate-200/85">
                            Masuk ke kanal komunikasi internal untuk koordinasi cepat dengan operator dan unit kerja terkait.
                        </p>
                        <span class="inline-flex items-center gap-2 text-sm font-bold text-blue-200">
                            Buka chat
                            <span aria-hidden="true">→</span>
                        </span>
                    </div>
                </Link>

                <Link
                    :href="route('lawangsewu.satellite.pendopo')"
                    class="group relative overflow-hidden rounded-[2rem] border border-amber-500/20 bg-[radial-gradient(circle_at_top_right,rgba(251,191,36,0.16),transparent_28%),linear-gradient(145deg,rgba(69,26,3,0.92),rgba(24,24,27,0.98))] p-6 text-white transition duration-300 hover:-translate-y-1 hover:border-amber-400/40 lg:col-span-2"
                >
                    <div class="space-y-4">
                        <div class="inline-flex rounded-full border border-amber-300/20 bg-amber-300/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-amber-200">
                            Pendopo
                        </div>
                        <h2 class="text-2xl font-black tracking-tight">Pusat Entri Data & Operasional Pengunjung</h2>
                        <p class="max-w-2xl text-sm leading-7 text-slate-200/85">
                            Buka pusat entri data dan operasional pengunjung di Pengadilan Agama Semarang melalui modul Pendopo yang disiapkan khusus untuk alur viewer.
                        </p>
                        <span class="inline-flex items-center gap-2 text-sm font-bold text-amber-200">
                            Buka Pendopo
                            <span aria-hidden="true">→</span>
                        </span>
                    </div>
                </Link>
            </section>
        </div>

        <div v-else class="space-y-6">
            <template v-if="isOperator">
                <section class="card-surface overflow-hidden p-6 lg:p-8">
                    <div class="space-y-5">
                        <div class="inline-flex items-center gap-2 rounded-full border border-[var(--accent-border)] bg-[var(--accent-soft)] px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-1)]">
                            Operator workspace
                        </div>

                        <div class="space-y-2">
                            <h1 class="text-2xl font-semibold tracking-tight text-[var(--text-1)] sm:text-3xl">
                                Fokus kerja operator: PTSP, WA Caraka, dan Chat Internal.
                            </h1>
                            <p class="max-w-3xl text-sm leading-7 text-[var(--text-2)] md:text-base">
                                Dashboard operator disederhanakan agar akses cepat ke modul inti tanpa distraksi menu atau widget yang tidak relevan.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div class="card-muted p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-2)]">Antrian PTSP</p>
                                <p class="mt-2 text-2xl font-black text-[var(--text-1)]">{{ ptspQueueValue }}</p>
                            </div>
                            <div class="card-muted p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-2)]">Interkom Umum</p>
                                <p class="mt-2 text-2xl font-black text-[var(--text-1)]">{{ chatChannelCount }}</p>
                            </div>
                            <div class="card-muted p-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-[var(--text-2)]">WA Caraka</p>
                                <p class="mt-2 text-sm font-semibold text-[var(--text-1)]">{{ waStatus }}</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="grid gap-5 lg:grid-cols-3">
                    <Link
                        :href="route('lawangsewu.ptsp.index')"
                        class="group relative overflow-hidden rounded-[1.7rem] border border-blue-500/20 bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.16),transparent_28%),linear-gradient(145deg,rgba(12,74,110,0.92),rgba(15,23,42,0.98))] p-6 text-white transition duration-300 hover:-translate-y-1 hover:border-cyan-400/40"
                    >
                        <div class="space-y-3">
                            <div class="inline-flex rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-cyan-200">Utama</div>
                            <h2 class="text-2xl font-black tracking-tight">Antrian PTSP</h2>
                            <p class="text-sm leading-7 text-slate-200/85">Kelola tiket, panggilan loket, dan alur antrean layanan PTSP harian.</p>
                            <span class="inline-flex items-center gap-2 text-sm font-bold text-cyan-200">Buka PTSP <span aria-hidden="true">→</span></span>
                        </div>
                    </Link>

                    <component
                        :is="waCarakaHref ? Link : 'div'"
                        :href="waCarakaHref || undefined"
                        class="group relative overflow-hidden rounded-[1.7rem] border border-emerald-500/20 bg-[radial-gradient(circle_at_top_right,rgba(52,211,153,0.16),transparent_28%),linear-gradient(145deg,rgba(6,78,59,0.92),rgba(15,23,42,0.98))] p-6 text-white transition duration-300"
                        :class="waCarakaHref ? 'hover:-translate-y-1 hover:border-emerald-400/40' : 'opacity-70 cursor-not-allowed'"
                    >
                        <div class="space-y-3">
                            <div class="inline-flex rounded-full border border-emerald-300/20 bg-emerald-300/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-emerald-200">Komunikasi</div>
                            <h2 class="text-2xl font-black tracking-tight">WA Caraka Inbox</h2>
                            <p class="text-sm leading-7 text-slate-200/85">Pantau pesan masuk dan distribusikan respons operasional dari dashboard internal.</p>
                            <span class="inline-flex items-center gap-2 text-sm font-bold text-emerald-200">
                                {{ waCarakaHref ? 'Buka WA Caraka' : 'Belum tersedia' }}
                                <span aria-hidden="true">→</span>
                            </span>
                        </div>
                    </component>

                    <Link
                        :href="route('lawangsewu.chat')"
                        class="group relative overflow-hidden rounded-[1.7rem] border border-indigo-500/20 bg-[radial-gradient(circle_at_top_right,rgba(129,140,248,0.16),transparent_28%),linear-gradient(145deg,rgba(55,48,163,0.9),rgba(15,23,42,0.98))] p-6 text-white transition duration-300 hover:-translate-y-1 hover:border-indigo-400/40"
                    >
                        <div class="space-y-3">
                            <div class="inline-flex rounded-full border border-indigo-300/20 bg-indigo-300/10 px-3 py-1 text-[10px] font-black uppercase tracking-[0.2em] text-indigo-200">Realtime</div>
                            <h2 class="text-2xl font-black tracking-tight">Chat Internal</h2>
                            <p class="text-sm leading-7 text-slate-200/85">Koordinasi lintas unit secara cepat dengan kanal interkom internal.</p>
                            <span class="inline-flex items-center gap-2 text-sm font-bold text-indigo-200">Buka Chat <span aria-hidden="true">→</span></span>
                        </div>
                    </Link>
                </section>
            </template>

            <template v-else>
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
                                :hide-badge="isOperator"
                            />
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
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
            </template>
        </div>

        <!-- Dashboard Footer -->
        <div class="mt-12 py-10 border-t border-[var(--border)] flex flex-col items-center gap-6">
            <div class="flex items-center gap-8 opacity-40">
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Sprint Complete</span>
                <div class="w-1.5 h-1.5 rounded-full bg-[var(--border-strong)]"></div>
                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Internal System</span>
            </div>
            
            <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-[var(--text-3)] text-shadow-sm">
                developed with <span class="text-red-500 animate-pulse mx-1 inline-block">❤</span> by dubes prakom pa semarang
            </p>
        </div>
    </LawangsewuLayout>
</template>
