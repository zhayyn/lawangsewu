<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import ThemeToggle from '@/Components/lawangsewu/ThemeToggle.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
    canGoogleAuth: {
        type: Boolean,
    },
    googleClientId: {
        type: String,
        default: '',
    },
    status: {
        type: String,
    },
    error: {
        type: String,
    },
});

const googleButtonContainer = ref(null);
const googleBusy = ref(false);
const googleReady = ref(false);
const googleBusyLabel = ref('Menyiapkan login Google...');
const googleError = ref('');
const isDark = ref(true);
const isPasswordVisible = ref(false);
const isGoogleRedirectMode = ref(false);
const passwordRevealTimer = ref(null);
let googleIdentityScriptPromise = null;

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

const passwordInputType = computed(() => (isPasswordVisible.value ? 'text' : 'password'));

const toggleTheme = () => {
    isDark.value = !isDark.value;

    window.dispatchEvent(new CustomEvent('lawangsewu-theme-change', {
        detail: {
            theme: isDark.value ? 'dark' : 'light',
        },
    }));

    localStorage.setItem('lawangsewu-theme', isDark.value ? 'dark' : 'light');
};

const loadGoogleIdentityScript = () => {
    if (window.google?.accounts?.id) {
        return Promise.resolve(window.google);
    }

    if (googleIdentityScriptPromise) {
        return googleIdentityScriptPromise;
    }

    googleIdentityScriptPromise = new Promise((resolve, reject) => {
        const existingScript = document.querySelector('script[data-google-identity]');

        if (existingScript) {
            existingScript.addEventListener('load', () => resolve(window.google), { once: true });
            existingScript.addEventListener('error', () => reject(new Error('Script Google gagal dimuat.')), { once: true });

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://accounts.google.com/gsi/client';
        script.async = true;
        script.defer = true;
        script.dataset.googleIdentity = 'true';
        script.onload = () => resolve(window.google);
        script.onerror = () => reject(new Error('Script Google gagal dimuat.'));

        document.head.appendChild(script);
    });

    return googleIdentityScriptPromise;
};

const isAppleTouchDevice = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    const userAgent = window.navigator.userAgent || '';
    const platform = window.navigator.platform || '';
    const maxTouchPoints = window.navigator.maxTouchPoints || 0;

    return /iPad|iPhone|iPod/.test(userAgent)
        || (platform === 'MacIntel' && maxTouchPoints > 1);
};

const shouldUseGoogleRedirect = () => {
    if (typeof window === 'undefined') {
        return false;
    }

    const coarsePointer = window.matchMedia?.('(pointer: coarse)')?.matches;
    return isGoogleRedirectMode.value || isAppleTouchDevice() || Boolean(coarsePointer && /Safari/i.test(window.navigator.userAgent || '') && !/Chrome|CriOS|FxiOS|EdgiOS/i.test(window.navigator.userAgent || ''));
};

const openGoogleRedirect = () => {
    window.location.assign(route('auth.google'));
};

const handleGoogleButtonPress = () => {
    if (googleBusy.value) {
        return;
    }

    if (shouldUseGoogleRedirect() || !googleReady.value) {
        googleBusy.value = true;
        googleBusyLabel.value = 'Mengalihkan ke Google...';
        openGoogleRedirect();
        return;
    }

    const renderedButton = googleButtonContainer.value?.querySelector('div[role="button"]');
    const renderedFrame = googleButtonContainer.value?.querySelector('iframe');

    if (renderedButton instanceof HTMLElement) {
        renderedButton.click();
        return;
    }

    if (renderedFrame instanceof HTMLElement) {
        renderedFrame.click();
        return;
    }

    googleError.value = 'Tombol Google belum siap. Browser akan dialihkan ke mode login yang lebih stabil.';
    isGoogleRedirectMode.value = true;
    openGoogleRedirect();
};

const revealPasswordTemporarily = () => {
    isPasswordVisible.value = true;

    if (passwordRevealTimer.value) {
        clearTimeout(passwordRevealTimer.value);
    }

    passwordRevealTimer.value = setTimeout(() => {
        isPasswordVisible.value = false;
        passwordRevealTimer.value = null;
    }, 3000);
};

const handleGoogleCredential = async (response) => {
    if (!response?.credential) {
        googleError.value = 'Google tidak mengirimkan token login yang dibutuhkan.';

        return;
    }

    googleBusy.value = true;
    googleBusyLabel.value = 'Memproses login Google...';
    googleError.value = '';

    try {
        const { data } = await window.axios.post(
            route('auth.google.credential'),
            {
                credential: response.credential,
            },
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        window.location.href = data.redirect;
    } catch (requestError) {
        googleError.value = requestError?.response?.data?.message
            || 'Login Google gagal diproses. Silakan coba lagi.';
    } finally {
        googleBusy.value = false;
    }
};

const renderGoogleButton = async () => {
    if (!props.canGoogleAuth || !props.googleClientId || !googleButtonContainer.value) {
        return;
    }

    googleBusy.value = true;
    googleReady.value = false;
    googleBusyLabel.value = 'Menyiapkan login Google...';
    googleError.value = '';

    try {
        await loadGoogleIdentityScript();

        if (!window.google?.accounts?.id) {
            throw new Error('Google Identity Services tidak tersedia.');
        }

        googleButtonContainer.value.innerHTML = '';

        window.google.accounts.id.initialize({
            client_id: props.googleClientId,
            callback: handleGoogleCredential,
            context: 'signin',
            ux_mode: shouldUseGoogleRedirect() ? 'redirect' : 'popup',
            login_uri: route('auth.google'),
            auto_select: false,
            cancel_on_tap_outside: true,
        });

        window.google.accounts.id.renderButton(googleButtonContainer.value, {
            type: 'standard',
            theme: 'outline',
            size: 'large',
            text: 'continue_with',
            shape: 'pill',
            width: Math.max(240, Math.min(360, googleButtonContainer.value.clientWidth || 360)),
            logo_alignment: 'left',
        });

        const buttonNode = googleButtonContainer.value.querySelector('div[role="button"], iframe');

        if (buttonNode) {
            buttonNode.style.width = '100%';
            buttonNode.style.height = '100%';
        }

        googleReady.value = true;

        if (shouldUseGoogleRedirect()) {
            googleBusy.value = false;
            googleBusyLabel.value = 'Lanjutkan dengan Google';
        }
    } catch (scriptError) {
        googleError.value = scriptError?.message
            || 'Tombol Google tidak berhasil dimuat di browser ini.';
    } finally {
        googleBusy.value = false;
    }
};

onMounted(() => {
    const storedTheme = localStorage.getItem('lawangsewu-theme');

    if (storedTheme) {
        isDark.value = storedTheme === 'dark';
    }

    window.dispatchEvent(new CustomEvent('lawangsewu-theme-change', {
        detail: {
            theme: isDark.value ? 'dark' : 'light',
        },
    }));

    renderGoogleButton();
});

onBeforeUnmount(() => {
    if (passwordRevealTimer.value) {
        clearTimeout(passwordRevealTimer.value);
    }
});
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

        <div v-if="canGoogleAuth" class="mb-6 space-y-3">
            <div class="google-glow-shell relative">
                <button
                    type="button"
                    @click="handleGoogleButtonPress"
                    data-testid="google-signin-button"
                    class="google-glow-button flex h-14 w-full items-center justify-center gap-3 rounded-2xl bg-white px-4 text-sm text-gray-900 ring-1 ring-gray-200 transition-all duration-300 sm:px-5 sm:text-base"
                    :class="googleBusy ? 'cursor-wait opacity-80' : ''"
                >
                    <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" aria-hidden="true">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.35-.35-2.1s.13-1.44.35-2.1V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.83z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.83c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>

                    <span class="font-bold">
                        {{ googleBusy ? googleBusyLabel : 'Lanjutkan dengan Google' }}
                    </span>
                </button>

                <div
                    ref="googleButtonContainer"
                    class="google-button-hitbox absolute inset-0 overflow-hidden rounded-2xl opacity-0"
                    :class="googleReady && !googleBusy && !shouldUseGoogleRedirect() ? 'pointer-events-auto' : 'pointer-events-none'"
                    aria-hidden="true"
                />
            </div>

            <div v-if="googleError" class="rounded-2xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 text-sm text-amber-700">
                {{ googleError }}
            </div>

            <p v-if="shouldUseGoogleRedirect()" class="text-center text-xs font-semibold text-sky-600">
                Browser ini memakai alur Google yang paling stabil untuk perangkat sentuh.
            </p>
        </div>

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

                <div class="relative mt-1">
                    <TextInput
                        id="password"
                        data-testid="password-input"
                        :type="passwordInputType"
                        class="block w-full pr-11"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                    />

                    <button
                        type="button"
                        data-testid="password-visibility-toggle"
                        class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center text-gray-400 transition hover:text-blue-500"
                        @click="revealPasswordTemporarily"
                        aria-label="Tampilkan password selama 3 detik"
                    >
                        <svg v-if="!isPasswordVisible" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 3l18 18" />
                            <path d="M10.6 10.7a3 3 0 0 0 4 4" />
                            <path d="M9.9 5.1A10.4 10.4 0 0 1 12 5c6.5 0 10 7 10 7a19.6 19.6 0 0 1-4 5.2" />
                            <path d="M6.6 6.7C4 8.5 2 12 2 12a19.1 19.1 0 0 0 7.5 6" />
                        </svg>
                    </button>
                </div>

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

            <div class="pt-2 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center">
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
            <ThemeToggle
                :dark="isDark"
                @toggle="toggleTheme"
            />

            <footer class="text-center">
                <p class="text-[10px] text-gray-400 font-medium uppercase tracking-[0.2em]">
                    developed with <span class="text-red-500 animate-pulse inline-block mx-0.5">❤</span> by dubes prakom pa semarang
                </p>
            </footer>
        </div>
    </GuestLayout>
</template>

<style scoped>
.google-button-hitbox :deep(div),
.google-button-hitbox :deep(iframe) {
    width: 100% !important;
    height: 100% !important;
}

.google-glow-shell:hover .google-glow-button {
    transform: translateY(-2px);
    box-shadow: 0 0 25px rgba(66, 133, 244, 0.4);
    border-color: rgba(66, 133, 244, 0.4);
}

.google-glow-shell:active .google-glow-button {
    transform: translateY(0);
}

@media (max-width: 640px) {
    .google-glow-button {
        min-height: 52px;
    }

    .google-glow-button span {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
}
</style>
