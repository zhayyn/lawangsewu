<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    status: {
        type: String,
        default: null,
    },
});

const showCreateForm = ref(false);
const newUserForm = useForm({
    name: '',
    email: '',
    password: '',
    role: 'viewer',
});

const submitNewUser = () => {
    newUserForm.post(route('admin.users.store'), {
        onSuccess: () => {
            newUserForm.reset();
            showCreateForm.value = false;
        },
    });
};

const formState = reactive(
    props.users.reduce((acc, user) => {
        acc[user.id] = {
            role: user.role ?? 'viewer',
            is_active: Boolean(user.is_active),
        };

        return acc;
    }, {}),
);

const pendingGoogleOnly = ref(false);

const filteredUsers = computed(() => {
    if (!pendingGoogleOnly.value) {
        return props.users;
    }

    return props.users.filter((user) => Boolean(user.google_id) && !Boolean(user.is_active));
});

const isRecentlyPending = (user) => {
    if (!user?.created_at || Boolean(user.is_active)) {
        return false;
    }

    const createdAt = new Date(user.created_at);

    if (Number.isNaN(createdAt.getTime())) {
        return false;
    }

    const oneDayMs = 24 * 60 * 60 * 1000;
    return Date.now() - createdAt.getTime() <= oneDayMs;
};

const saveUser = (userId) => {
    const payload = formState[userId];

    router.patch(route('admin.users.update', userId), payload, {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Kelola Akses User" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black uppercase tracking-[0.2em] text-[var(--text-1)]">
                    Kelola Akses Pengguna
                </h2>
                <button
                    @click="showCreateForm = !showCreateForm"
                    class="github-button !bg-blue-600 hover:!bg-blue-700 !px-5"
                >
                    {{ showCreateForm ? 'Batal' : 'Registrasi Manual' }}
                </button>
            </div>
        </template>

        <div class="py-10">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                
                <!-- Notifikasi Status -->
                <div
                    v-if="status"
                    class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-600 flex items-center gap-3"
                >
                    <div class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></div>
                    {{ status }}
                </div>

                <!-- Form Registrasi Manual -->
                <div v-if="showCreateForm" class="card-surface p-8 border-blue-500/30 bg-blue-500/[0.02]">
                    <h3 class="text-lg font-black uppercase tracking-[0.2em] text-blue-600 mb-6">
                        Registrasi User Manual (Email & Password)
                    </h3>
                    <form @submit.prevent="submitNewUser" class="grid gap-6 md:grid-cols-4 items-end">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Nama Lengkap</label>
                            <input v-model="newUserForm.name" type="text" class="input-surface w-full" required placeholder="Contoh: Budi Santoso">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Email Akses</label>
                            <input v-model="newUserForm.email" type="email" class="input-surface w-full" required placeholder="nama@email.com">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Password</label>
                            <input v-model="newUserForm.password" type="password" class="input-surface w-full" required placeholder="Min 8 karakter">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Role Akses</label>
                            <div class="flex gap-2">
                                <select v-model="newUserForm.role" class="input-surface flex-1">
                                    <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                                </select>
                                <button type="submit" class="github-button !bg-emerald-600 hover:!bg-emerald-700" :disabled="newUserForm.processing">
                                    Daftarkan
                                </button>
                            </div>
                        </div>
                    </form>
                    <div v-if="newUserForm.errors" class="mt-4 space-y-1">
                        <p v-for="(error, key) in newUserForm.errors" :key="key" class="text-xs text-red-500 font-bold italic">{{ error }}</p>
                    </div>
                </div>

                <!-- Filter & Stats -->
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-5 py-4">
                    <label class="inline-flex items-center gap-3 font-bold text-sm text-[var(--text-2)] cursor-pointer group">
                        <input
                            v-model="pendingGoogleOnly"
                            type="checkbox"
                            class="rounded-lg border-[var(--border)] text-blue-600 shadow-sm focus:ring-blue-500 focus:ring-offset-0 bg-[var(--surface-2)]"
                        >
                        <span class="group-hover:text-[var(--text-1)] transition-colors">Hanya user Google menunggu persetujuan</span>
                    </label>
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Total Terfilter:</span>
                        <span class="rounded-xl border border-blue-500/30 bg-blue-500/10 px-4 py-1.5 text-xs font-black text-blue-600 shadow-lg shadow-blue-500/5">
                            {{ filteredUsers.length }} USER
                        </span>
                    </div>
                </div>

                <!-- Tabel User -->
                <div class="overflow-hidden rounded-[28px] border border-[var(--border)] bg-[var(--surface-1)] shadow-2xl shadow-black/5">
                    <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                        <thead class="bg-[var(--surface-2)]">
                            <tr>
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Nama</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Kontak / Email</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Provider</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Role Hak Akses</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Status Akun</th>
                                <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Konfigurasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-[var(--surface-2)]/[0.4] transition-colors">
                                <td class="px-6 py-4 font-bold text-[var(--text-1)]">{{ user.name }}</td>
                                <td class="px-6 py-4 font-medium text-[var(--text-2)]">{{ user.email }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span
                                        class="inline-flex rounded-lg px-3 py-1 text-[10px] font-black uppercase tracking-widest"
                                        :class="user.google_id ? 'bg-blue-500/10 text-blue-500 border border-blue-500/20' : 'bg-[var(--surface-3)] text-[var(--text-3)] border border-[var(--border)]'"
                                    >
                                        {{ user.google_id ? 'Google' : 'Manual' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <select
                                        v-model="formState[user.id].role"
                                        class="input-surface !py-1.5 !text-xs !rounded-xl"
                                    >
                                        <option v-for="role in roles" :key="role" :value="role">
                                            {{ role.toUpperCase() }}
                                        </option>
                                    </select>
                                </td>
                                <td class="px-6 py-4">
                                    <label class="inline-flex items-center gap-3 cursor-pointer group">
                                        <div class="relative flex items-center">
                                            <input
                                                v-model="formState[user.id].is_active"
                                                type="checkbox"
                                                class="rounded-lg border-[var(--border)] text-emerald-600 shadow-sm focus:ring-emerald-500 focus:ring-offset-0 bg-[var(--surface-2)]"
                                            >
                                        </div>
                                        <span :class="[
                                            'text-[10px] font-black uppercase tracking-widest transition-colors',
                                            formState[user.id].is_active ? 'text-emerald-500' : 'text-rose-500'
                                        ]">
                                            {{ formState[user.id].is_active ? 'Aktif' : 'Tertahan' }}
                                        </span>
                                    </label>

                                    <div v-if="!formState[user.id].is_active && user.google_id" class="mt-1.5">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.15em]"
                                            :class="isRecentlyPending(user) ? 'bg-amber-500/10 text-amber-600' : 'bg-rose-500/10 text-rose-600'"
                                        >
                                            {{ isRecentlyPending(user) ? 'Daftar Baru' : 'Antrian Lama' }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <button
                                        type="button"
                                        class="github-button !py-2 !px-4 !text-[11px] !bg-indigo-600 hover:!bg-indigo-700"
                                        @click="saveUser(user.id)"
                                    >
                                        Update
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="filteredUsers.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--text-3)] opacity-40">
                                        Tidak ada data pengguna terfilter
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
