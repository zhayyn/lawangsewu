<script setup>
import { computed, onMounted, ref } from 'vue';

const isDark = ref(true);

const label = computed(() => (isDark.value ? 'Dark' : 'Light'));

function applyTheme(nextIsDark) {
    isDark.value = nextIsDark;
    const nextTheme = nextIsDark ? 'dark' : 'light';

    window.dispatchEvent(new CustomEvent('lawangsewu-theme-change', {
        detail: {
            theme: nextTheme,
        },
    }));

    localStorage.setItem('lawangsewu-theme', nextTheme);
}

function toggleTheme() {
    applyTheme(!isDark.value);
}

onMounted(() => {
    const storedTheme = localStorage.getItem('lawangsewu-theme');
    applyTheme(storedTheme ? storedTheme === 'dark' : true);
});
</script>

<template>
    <button
        type="button"
        class="electric-toggle"
        :aria-label="`Switch theme, current ${label}`"
        @click="toggleTheme"
    >
        <span class="electric-toggle__text">{{ label }}</span>
    </button>
</template>

<style scoped>
.electric-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 132px;
    border-radius: 999px;
    border: 1px solid rgba(0, 210, 255, 0.35);
    background: rgba(9, 20, 35, 0.82);
    color: #dff6ff;
    padding: 0.55rem 1.1rem;
    font-size: 0.82rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.electric-toggle::before,
.electric-toggle::after {
    content: '';
    position: absolute;
    inset: -2px;
    border-radius: 999px;
    opacity: 0;
    pointer-events: none;
}

.electric-toggle::before {
    border: 1px solid rgba(0, 210, 255, 0.75);
    filter: blur(0.5px);
}

.electric-toggle::after {
    border: 1px dashed rgba(0, 210, 255, 0.95);
    filter: drop-shadow(0 0 8px #00d2ff);
}

.electric-toggle:hover {
    transform: translateY(-1px);
    box-shadow: 0 0 0 1px rgba(0, 210, 255, 0.35), 0 0 18px rgba(0, 210, 255, 0.32);
}

.electric-toggle:hover::before,
.electric-toggle:hover::after {
    opacity: 1;
    animation: electric-spark 0.58s linear infinite;
}

.electric-toggle__text {
    text-shadow: 0 0 8px rgba(0, 210, 255, 0.55);
}

@keyframes electric-spark {
    0% {
        clip-path: polygon(0% 0%, 14% 0%, 24% 9%, 35% 2%, 52% 12%, 66% 0%, 100% 0%, 100% 100%, 0% 100%);
        transform: translateX(0) translateY(0);
    }
    25% {
        clip-path: polygon(0% 0%, 18% 0%, 28% 8%, 44% 0%, 63% 15%, 78% 4%, 100% 7%, 100% 100%, 0% 100%);
        transform: translateX(0.5px) translateY(-0.5px);
    }
    50% {
        clip-path: polygon(0% 0%, 22% 3%, 36% 0%, 50% 11%, 68% 3%, 82% 0%, 100% 10%, 100% 100%, 0% 100%);
        transform: translateX(-0.5px) translateY(0.3px);
    }
    75% {
        clip-path: polygon(0% 0%, 15% 5%, 27% 0%, 43% 12%, 58% 2%, 73% 11%, 100% 3%, 100% 100%, 0% 100%);
        transform: translateX(0.6px) translateY(-0.2px);
    }
    100% {
        clip-path: polygon(0% 0%, 14% 0%, 24% 9%, 35% 2%, 52% 12%, 66% 0%, 100% 0%, 100% 100%, 0% 100%);
        transform: translateX(0) translateY(0);
    }
}
</style>
