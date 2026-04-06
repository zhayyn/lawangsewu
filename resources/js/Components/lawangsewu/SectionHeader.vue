<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    eyebrow: {
        type: String,
        default: '',
    },
    title: {
        type: String,
        required: true,
    },
    description: {
        type: String,
        default: '',
    },
    actionLabel: {
        type: String,
        default: '',
    },
    actionHref: {
        type: String,
        default: '',
    },
});
</script>

<template>
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div class="space-y-2">
            <p
                v-if="eyebrow"
                class="text-xs font-semibold uppercase tracking-[0.28em] text-[var(--text-2)]"
            >
                {{ eyebrow }}
            </p>
            <h2 class="text-xl font-semibold tracking-tight text-[var(--text-1)] md:text-2xl">
                {{ title }}
            </h2>
            <p
                v-if="description"
                class="max-w-2xl text-sm leading-6 text-[var(--text-2)]"
            >
                {{ description }}
            </p>
        </div>

        <Link
            v-if="actionLabel && actionHref"
            :href="actionHref"
            class="secondary-button"
        >
            {{ actionLabel }}
        </Link>
    </div>
</template>
