<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);
const avatarInput = ref(null);
const avatarPreview = ref(user.value?.avatar || null);
let previewObjectUrl = null;

const form = useForm({
    alias: user.value?.alias || '',
    email: user.value?.email || '',
    avatar_file: null,
    remove_avatar: false,
});

const openAvatarPicker = () => {
    avatarInput.value?.click();
};

const resetAvatarInput = () => {
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

const revokePreviewObjectUrl = () => {
    if (previewObjectUrl) {
        URL.revokeObjectURL(previewObjectUrl);
        previewObjectUrl = null;
    }
};

const handleAvatarChange = (event) => {
    const file = event.target.files?.[0] || null;
    form.avatar_file = file;
    form.remove_avatar = false;
    revokePreviewObjectUrl();

    if (file) {
        previewObjectUrl = URL.createObjectURL(file);
        avatarPreview.value = previewObjectUrl;
        return;
    }

    avatarPreview.value = user.value?.avatar || null;
};

const clearAvatar = () => {
    revokePreviewObjectUrl();
    avatarPreview.value = null;
    form.avatar_file = null;
    form.remove_avatar = true;
    resetAvatarInput();
};

const submitProfile = () => {
    form.post(route('profile.save'), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            revokePreviewObjectUrl();
            form.defaults({
                alias: page.props.auth.user?.alias || '',
                email: page.props.auth.user?.email || '',
                avatar_file: null,
                remove_avatar: false,
            });
            form.reset('avatar_file', 'remove_avatar');
            avatarPreview.value = page.props.auth.user?.avatar || null;
            resetAvatarInput();
        },
    });
};

onBeforeUnmount(() => {
    revokePreviewObjectUrl();
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-xl font-black uppercase tracking-[0.2em] text-[var(--text-1)]">
                Informasi Identitas
            </h2>

            <p class="mt-1 text-sm text-[var(--text-2)]">
                Kelola profil Anda sendiri untuk foto profil, alias operasional, dan email akun.
            </p>
        </header>

        <form
            @submit.prevent="submitProfile"
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel value="Foto Profil" />

                <div class="mt-3 flex flex-col gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4 sm:flex-row sm:items-center">
                    <div class="flex h-24 w-24 items-center justify-center overflow-hidden rounded-3xl border border-[var(--border)] bg-[var(--surface-1)]">
                        <img
                            v-if="avatarPreview"
                            :src="avatarPreview"
                            alt="Preview avatar"
                            class="h-full w-full object-cover"
                        >
                        <div v-else class="text-center text-xs font-black uppercase tracking-[0.18em] text-[var(--text-3)]">
                            {{ (user.alias || user.name || 'OP').split(' ').map((chunk) => chunk[0]).join('').slice(0, 2).toUpperCase() }}
                        </div>
                    </div>

                    <div class="min-w-0 flex-1 space-y-3">
                        <p class="text-sm font-semibold text-[var(--text-1)]">Foto ini tampil di header dan area chat internal.</p>
                        <div class="flex flex-wrap gap-2">
                            <button
                                type="button"
                                class="rounded-xl border border-[var(--border)] bg-[var(--surface-1)] px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-[var(--text-1)] transition hover:border-[var(--accent-border)]"
                                @click="openAvatarPicker"
                            >
                                Pilih Foto
                            </button>
                            <button
                                v-if="avatarPreview || user.avatar"
                                type="button"
                                class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-rose-700 transition hover:bg-rose-100"
                                @click="clearAvatar"
                            >
                                Hapus Foto
                            </button>
                        </div>
                        <input
                            ref="avatarInput"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="hidden"
                            @change="handleAvatarChange"
                        >
                        <p class="text-[11px] text-[var(--text-2)]">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                        <InputError class="mt-1" :message="form.errors.avatar_file" />
                    </div>
                </div>
            </div>

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
                <InputLabel for="alias" value="Alias / Nama Operator" />

                <TextInput
                    id="alias"
                    type="text"
                    class="mt-1 block w-full !bg-[var(--surface-1)] !border-[var(--border)]"
                    v-model="form.alias"
                    placeholder="Contoh: Sang Juara ✦, Delta One 🔵, dll"
                    autocapitalize="none"
                    autocorrect="off"
                    spellcheck="false"
                />
                
                <p class="mt-1 text-[10px] font-bold text-[var(--text-3)] tracking-wider">
                    Nama ini akan tampil di header, chat, dan statistik operator. Boleh pakai emoji atau simbol unik. ✦ 🎯
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
