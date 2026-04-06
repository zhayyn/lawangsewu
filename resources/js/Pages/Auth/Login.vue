<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import ThemeToggleElectric from '@/Components/lawangsewu/ThemeToggleElectric.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    canGoogleAuth: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    error: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Log in" />

        <div v-if="status" class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-600 rounded-2xl text-sm font-semibold flex items-center gap-3">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
            {{ status }}
        </div>

        <div v-if="error" class="mb-6 p-4 bg-red-500/10 border border-red-500/20 text-red-600 rounded-2xl text-sm font-semibold flex items-center gap-3 shadow-lg shadow-red-500/5">
            <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            <span class="leading-relaxed">{{ error }}</span>
        </div>

        <a
            v-if="canGoogleAuth"
            :href="route('auth.google')"
            class="google-glow-button mb-6 flex w-full items-center justify-center gap-3 py-4 bg-white text-gray-900 rounded-2xl font-bold transition-all duration-300 relative overflow-hidden ring-1 ring-gray-200"
        >
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.35-.35-2.1s.13-1.44.35-2.1V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.83z"/>
                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.83c.87-2.6 3.3-4.53 6.16-4.53z"/>
            </svg>
            <span>Lanjutkan dengan Google</span>
        </a>

        <form @submit.prevent="submit" class="space-y-4">
            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <InputLabel for="password" value="Password" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                />

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center cursor-pointer group">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-xs text-gray-500 group-hover:text-gray-700 transition-colors">Remember me</span>
                </label>

                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-xs text-gray-400 hover:text-blue-500 transition-colors"
                >
                    Lupa password?
                </Link>
            </div>

            <div class="pt-2 flex items-center gap-3">
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="flex-1 py-3 text-center rounded-xl border border-gray-200 text-sm font-semibold text-gray-600 hover:bg-gray-50 transition-all active:scale-95"
                >
                    Buat akun
                </Link>

                <PrimaryButton
                    class="flex-1 py-3 justify-center text-sm font-bold shadow-lg shadow-blue-600/20 active:scale-95"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Masuk
                </PrimaryButton>
            </div>
        </form>

        <div class="mt-8 flex flex-col items-center gap-6">
            <ThemeToggleElectric />

            <footer class="text-center">
                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-[0.2em]">
                    developed with <span class="text-red-500 animate-pulse inline-block mx-0.5">❤</span> by dubes prakom pa semarang
                </p>
            </footer>
        </div>
    </GuestLayout>
</template>

<style scoped>
.google-glow-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 0 25px rgba(66, 133, 244, 0.4);
    border-color: rgba(66, 133, 244, 0.4);
}
.google-glow-button:active {
    transform: translateY(0);
}
</style>
