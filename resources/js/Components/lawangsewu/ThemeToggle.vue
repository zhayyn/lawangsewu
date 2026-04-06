<script setup>
defineProps({
    dark: {
        type: Boolean,
        default: true,
    },
});

defineEmits(['toggle']);
</script>

<template>
    <button
        type="button"
        class="theme-toggle"
        @click="$emit('toggle')"
    >
        <span
            class="theme-toggle__item"
            :class="{ 'is-active': !dark }"
        >
            Light
        </span>
        <span
            class="theme-toggle__item"
            :class="{ 'is-active': dark }"
        >
            Dark
        </span>
    </button>
</template>
