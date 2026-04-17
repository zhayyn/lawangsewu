<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const user = usePage().props.auth.user;

const form = useForm({
    alias: user.alias,
    email: user.email,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-black uppercase tracking-[0.2em] text-[var(--text-1)]">
                Informasi Identitas
            </h2>

            <p class="mt-1 text-sm text-[var(--text-2)]">
                Anda dapat mengubah alias dan email. Nama resmi hanya dapat diubah oleh Superadmin.
            </p>
        </header>

        <form
            @submit.prevent="form.post(route('profile.save'))"
            class="mt-6 space-y-6"
        >
            <!-- Nama Resmi: read-only -->
            <div>
                <InputLabel for="name" value="Nama Lengkap Resmi" />
                <div class="mt-1 flex items-center gap-3 rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 opacity-70 cursor-not-allowed">
                    <svg class="h-4 w-4 shrink-0 text-[var(--text-3)]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span class="text-sm font-semibold text-[var(--text-2)]">{{ user.name }}</span>
                    <span class="ml-auto text-[9px] font-black uppercase tracking-widest text-[var(--text-3)]">Hanya Superadmin</span>
                </div>
            </div>

            <div>
                <InputLabel for="alias" value="Alias / Nama Panggilan (Internal)" />

                <TextInput
                    id="alias"
                    type="text"
                    class="mt-1 block w-full !bg-[var(--surface-1)] !border-[var(--border)]"
                    v-model="form.alias"
                    placeholder="Contoh: Sang Juara, Delta One, dll"
                />
                
                <p class="mt-1 text-[10px] font-bold text-[var(--text-3)] uppercase tracking-wider">
                    Nama ini akan muncul di chat dan header sistem.
                </p>

                <InputError class="mt-2" :message="form.errors.alias" />
            </div>

            <div>
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    Your email address is unverified.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Save</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Saved.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
