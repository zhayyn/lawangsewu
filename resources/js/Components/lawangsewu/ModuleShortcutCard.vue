<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    module: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <component
        :is="module.href ? Link : 'div'"
        :href="module.href || undefined"
        class="card-muted flex h-full flex-col justify-between p-5 transition duration-200 hover:-translate-y-0.5 hover:border-[var(--border-strong)]"
    >
        <div>
            <div class="flex items-center justify-between gap-3">
                <h3 class="text-base font-semibold text-[var(--text-1)]">
                    {{ module.title }}
                </h3>
                <span class="rounded-full border border-[var(--border)] px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-[var(--text-2)]">
                    {{ module.badge }}
                </span>
            </div>

            <p class="mt-3 text-sm leading-6 text-[var(--text-2)]">
                {{ module.description }}
            </p>
        </div>

        <div class="mt-6 flex items-center justify-between text-sm">
            <span class="text-[var(--text-2)]">
                {{ module.owner }}
            </span>
            <span class="font-medium text-[var(--text-1)]">
                {{ module.href ? 'Open' : 'Planned' }}
            </span>
        </div>
    </component>
</template>
