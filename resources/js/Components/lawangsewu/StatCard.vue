<script setup>
import { computed } from 'vue';

const props = defineProps({
    metric: {
        type: Object,
        required: true,
    },
});

const toneClasses = computed(() => ({
    blue: 'border-sky-500/30 bg-sky-500/10 text-sky-500',
    violet: 'border-violet-500/30 bg-violet-500/10 text-violet-500',
    amber: 'border-amber-500/30 bg-amber-500/10 text-amber-500',
    emerald: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-500',
}[props.metric.tone] ?? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-500'));
</script>

<template>
    <article class="card-surface p-6 group transition-all duration-300 hover:scale-[1.02] hover:shadow-2xl active:scale-[0.98]">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--text-3)] opacity-80 mb-2">
                    {{ metric.title }}
                </p>
                <h3 class="text-3xl font-black tracking-tight text-[var(--text-1)]">
                    {{ metric.value }}
                </h3>
            </div>

            <span
                class="rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-widest shadow-sm"
                :class="toneClasses"
            >
                {{ metric.trend }}
            </span>
        </div>

        <div class="mt-4 flex items-center gap-2">
            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)]"></div>
            <p class="text-xs font-semibold text-[var(--text-2)] italic tracking-tight">
                {{ metric.detail }}
            </p>
        </div>
    </article>
</template>
