<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import SimpleParticles from '@/Components/SimpleParticles.vue';
import { Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const theme = ref('dark');
const shellTheme = computed(() => (theme.value === 'dark' ? 'dark theme-dark' : 'theme-light'));

function handleThemeChange(event) {
    const nextTheme = event?.detail?.theme;
    if (nextTheme === 'dark' || nextTheme === 'light') {
        theme.value = nextTheme;
    }
}

watch(theme, (value) => {
    localStorage.setItem('lawangsewu-theme', value);
});

onMounted(() => {
    const storedTheme = localStorage.getItem('lawangsewu-theme');

    if (storedTheme === 'dark' || storedTheme === 'light') {
        theme.value = storedTheme;
    }

    window.addEventListener('lawangsewu-theme-change', handleThemeChange);
});

onBeforeUnmount(() => {
    window.removeEventListener('lawangsewu-theme-change', handleThemeChange);
});
</script>

<template>
    <div :class="shellTheme">
        <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-[var(--surface-0)] px-4 py-8">
            <div
                class="pointer-events-none absolute inset-0"
                style="background-image: var(--app-gradient)"
            />
            <SimpleParticles />

            <div class="pointer-events-none absolute -left-16 top-8 h-72 w-72 rounded-full bg-sky-500/20 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-16 right-0 h-72 w-72 rounded-full bg-blue-500/15 blur-3xl" />

            <div class="relative w-full max-w-md">
                <div class="mb-6 flex flex-col items-center">
                    <Link href="/" class="inline-flex items-center justify-center">
                        <ApplicationLogo class="h-20 w-auto text-[var(--text-2)]" />
                    </Link>
                </div>

                <div class="card-surface w-full px-6 py-5 sm:px-7 sm:py-6">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
