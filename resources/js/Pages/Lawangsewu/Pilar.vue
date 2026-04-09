<script setup>
import SectionHeader from '@/Components/lawangsewu/SectionHeader.vue';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    overview: { type: Object, default: () => ({}) },
    stats: { type: Array, default: () => [] },
    pillars: { type: Array, default: () => [] },
    legacySources: { type: Array, default: () => [] },
    launchers: { type: Array, default: () => [] },
    phases: { type: Array, default: () => [] },
});
</script>

<template>
    <Head title="Pilar Antrian PASMG" />

    <LawangsewuLayout
        current-route="pilar"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">
            <section class="card-surface overflow-hidden p-6 lg:p-8">
                <div class="grid gap-6 xl:grid-cols-[minmax(0,1.45fr),360px]">
                    <div class="space-y-5">
                        <div class="inline-flex items-center gap-2 rounded-full border border-sky-500/20 bg-sky-500/10 px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.22em] text-sky-400">
                            Modul Sprint 3
                        </div>

                        <div class="space-y-3">
                            <h1 class="max-w-4xl text-3xl font-semibold tracking-tight text-[var(--text-1)] sm:text-4xl xl:text-5xl">
                                {{ overview.title }}
                            </h1>
                            <p class="max-w-3xl text-sm leading-7 text-[var(--text-2)] md:text-base">
                                {{ overview.description }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Link
                                v-for="launcher in launchers"
                                :key="launcher.label"
                                :href="launcher.href"
                                class="secondary-button"
                            >
                                {{ launcher.label }}
                            </Link>
                        </div>
                    </div>

                    <div class="card-muted space-y-4 p-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-2)]">
                                Status integrasi
                            </p>
                            <h2 class="mt-2 text-xl font-semibold tracking-tight text-[var(--text-1)]">
                                {{ overview.status }}
                            </h2>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="launcher in launchers"
                                :key="`${launcher.label}-caption`"
                                class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4"
                            >
                                <p class="text-sm font-semibold text-[var(--text-1)]">
                                    {{ launcher.label }}
                                </p>
                                <p class="mt-1 text-sm leading-6 text-[var(--text-2)]">
                                    {{ launcher.caption }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div
                    v-for="stat in stats"
                    :key="stat.label"
                    class="card-surface p-5"
                >
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[var(--text-2)]">
                        {{ stat.label }}
                    </p>
                    <p class="mt-3 text-3xl font-semibold tracking-tight text-[var(--text-1)]">
                        {{ stat.value }}
                    </p>
                    <p class="mt-2 text-sm leading-6 text-[var(--text-2)]">
                        {{ stat.detail }}
                    </p>
                </div>
            </section>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr),440px]">
                <section class="card-surface p-6">
                    <SectionHeader
                        eyebrow="Blueprint Pilar Antrian PASMG"
                        title="Pilar domain modul"
                        description="Enam pilar ini diambil dari arsitektur pilar antrian PASMG dan dipetakan ke fondasi Lawangsewu yang sudah ada."
                    />

                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        <article
                            v-for="pillar in pillars"
                            :key="pillar.title"
                            class="card-muted p-5"
                        >
                            <h3 class="text-base font-semibold text-[var(--text-1)]">
                                {{ pillar.title }}
                            </h3>
                            <p class="mt-3 text-sm leading-6 text-[var(--text-2)]">
                                {{ pillar.description }}
                            </p>
                        </article>
                    </div>
                </section>

                <section class="card-surface p-6">
                    <SectionHeader
                        eyebrow="Sumber Legacy"
                        title="Aset yang akan diserap"
                        description="Pilar Antrian PASMG tidak ditarik mentah ke Lawangsewu. Legacy dijadikan sumber migrasi bertahap."
                    />

                    <div class="mt-5 space-y-4">
                        <article
                            v-for="source in legacySources"
                            :key="source.path"
                            class="rounded-3xl border border-[var(--border)] bg-[var(--surface-1)] p-5"
                        >
                            <h3 class="text-base font-semibold text-[var(--text-1)]">
                                {{ source.name }}
                            </h3>
                            <p class="mt-2 break-all text-xs font-semibold uppercase tracking-[0.18em] text-[var(--text-3)]">
                                {{ source.path }}
                            </p>
                            <p class="mt-3 text-sm leading-6 text-[var(--text-2)]">
                                {{ source.summary }}
                            </p>
                        </article>
                    </div>
                </section>
            </div>

            <section class="card-surface p-6">
                <SectionHeader
                    eyebrow="Roadmap Migrasi"
                    title="Urutan masuk Pilar Antrian PASMG ke Lawangsewu"
                    description="Mulai dari hub modul dan launcher, lalu bergerak ke penyatuan queue authority, display publik, dan integrasi SIPP."
                />

                <div class="mt-5 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="phase in phases"
                        :key="phase.step"
                        class="card-muted p-5"
                    >
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sky-400">
                            {{ phase.step }}
                        </p>
                        <h3 class="mt-2 text-lg font-semibold text-[var(--text-1)]">
                            {{ phase.title }}
                        </h3>
                        <p class="mt-3 text-sm leading-6 text-[var(--text-2)]">
                            {{ phase.description }}
                        </p>
                    </article>
                </div>
            </section>
        </div>
    </LawangsewuLayout>
</template>
