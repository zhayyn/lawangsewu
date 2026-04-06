<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    own: {
        type: Boolean,
        default: false,
    },
});

const wrapperClass = computed(() => (props.own ? 'items-end' : 'items-start'));
const bubbleClass = computed(() => (props.own
    ? 'bg-[var(--accent-soft)] border-[var(--accent-border)]'
    : 'bg-[var(--surface-2)] border-[var(--border)]'));
</script>

<template>
    <div class="flex" :class="wrapperClass">
        <div
            class="max-w-[85%] rounded-3xl border px-4 py-3"
            :class="bubbleClass"
        >
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="font-semibold text-[var(--text-1)]">
                    {{ message.alias }}
                </span>
                <span class="text-[var(--text-3)]">
                    {{ message.department || message.senderName }}
                </span>
                <span class="text-[var(--text-3)]">
                    {{ message.sentAt }}
                </span>
            </div>

            <p class="mt-2 text-sm leading-6 text-[var(--text-1)]">
                {{ message.body }}
            </p>
        </div>
    </div>
</template>
