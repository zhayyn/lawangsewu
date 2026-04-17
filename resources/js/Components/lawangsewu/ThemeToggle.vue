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
        :aria-pressed="dark"
        :aria-label="dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap'"
        @click="$emit('toggle')"
    >
        <span class="theme-toggle__backdrop" />
        <span class="theme-toggle__track">
            <span class="theme-toggle__sun" :class="{ 'is-hidden': dark }">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2.5v2.5" />
                    <path d="M12 19v2.5" />
                    <path d="M4.9 4.9l1.8 1.8" />
                    <path d="M17.3 17.3l1.8 1.8" />
                    <path d="M2.5 12H5" />
                    <path d="M19 12h2.5" />
                    <path d="M4.9 19.1l1.8-1.8" />
                    <path d="M17.3 6.7l1.8-1.8" />
                </svg>
            </span>
            <span class="theme-toggle__moon" :class="{ 'is-hidden': !dark }">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M14.7 3.2a1 1 0 0 1 .91 1.49A8.2 8.2 0 1 0 19.3 15.6a1 1 0 0 1 1.5.93A10.2 10.2 0 1 1 13.8 2.7a1 1 0 0 1 .9.5Z" />
                </svg>
            </span>
        </span>
        <span class="theme-toggle__orb" :class="{ 'is-dark': dark }">
            <svg v-if="dark" viewBox="0 0 24 24" fill="currentColor">
                <path d="M14.7 3.2a1 1 0 0 1 .91 1.49A8.2 8.2 0 1 0 19.3 15.6a1 1 0 0 1 1.5.93A10.2 10.2 0 1 1 13.8 2.7a1 1 0 0 1 .9.5Z" />
            </svg>
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round">
                <circle cx="12" cy="12" r="4" />
                <path d="M12 2.5v2.5" />
                <path d="M12 19v2.5" />
                <path d="M4.9 4.9l1.8 1.8" />
                <path d="M17.3 17.3l1.8 1.8" />
                <path d="M2.5 12H5" />
                <path d="M19 12h2.5" />
                <path d="M4.9 19.1l1.8-1.8" />
                <path d="M17.3 6.7l1.8-1.8" />
            </svg>
        </span>
    </button>
</template>

<style scoped>
.theme-toggle {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 4.5rem;
    height: 2.5rem;
    border-radius: 999px;
    border: 1px solid rgba(148, 163, 184, 0.22);
    background: rgba(15, 23, 42, 0.72);
    overflow: hidden;
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
}

.theme-toggle:hover {
    transform: translateY(-1px);
    border-color: rgba(125, 211, 252, 0.45);
    box-shadow: 0 14px 35px -20px rgba(56, 189, 248, 0.65);
}

.theme-toggle:active {
    transform: translateY(0);
}

.theme-toggle__backdrop {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.16), rgba(250, 204, 21, 0.12));
}

.theme-toggle__track {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 0.45rem;
    color: rgba(255, 255, 255, 0.82);
}

.theme-toggle__sun,
.theme-toggle__moon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.theme-toggle__sun {
    color: #fbbf24;
}

.theme-toggle__moon {
    color: #dbeafe;
}

.theme-toggle__sun.is-hidden,
.theme-toggle__moon.is-hidden {
    opacity: 0.35;
    transform: scale(0.8);
}

.theme-toggle__orb {
    position: absolute;
    left: 0.22rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.95rem;
    height: 1.95rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #fde68a, #f59e0b);
    color: #7c2d12;
    box-shadow: 0 8px 20px -12px rgba(251, 191, 36, 0.9);
    transition: transform 0.3s ease, background 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
}

.theme-toggle__orb.is-dark {
    transform: translateX(1.95rem);
    background: linear-gradient(135deg, #dbeafe, #93c5fd 45%, #1d4ed8);
    color: #0f172a;
    box-shadow: 0 8px 20px -12px rgba(59, 130, 246, 0.95);
}

.theme-toggle__orb svg {
    width: 1rem;
    height: 1rem;
}
</style>
