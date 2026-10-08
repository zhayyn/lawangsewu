<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    users: {
        type: Array,
        required: true,
    },
    roles: {
        type: Array,
        required: true,
    },
    featureCatalog: {
        type: Array,
        default: () => [],
    },
    roleFeaturePermissions: {
        type: Object,
        default: () => ({}),
    },
    userFeaturePermissions: {
        type: Array,
        default: () => [],
    },
    allowlist: {
        type: Array,
        required: true,
    },
    superAdminEmail: {
        type: String,
        default: '',
    },
    loginHistories: {
        type: Array,
        default: () => [],
    },
    permissionAuditLogs: {
        type: Array,
        default: () => [],
    },
    status: {
        type: String,
        default: null,
    },
});

const page = usePage();
const isSuperAdmin = computed(() => Boolean(page.props.auth?.isSuperAdmin));
const inlineError = ref('');

const showCreateForm = ref(false);
const selectedPermissionUserId = ref(null);
const deleteModalState = reactive({ isOpen: false, userId: null });
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

const allowlistForm = useForm({
    email: '',
    note: '',
    auto_activate: false,
});

const submitAllowlist = () => {
    allowlistForm.post(route('admin.users.allowlist.store'), {
        onSuccess: () => {
            allowlistForm.reset();
            inlineError.value = '';
        },
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal menyimpan allowlist. Pastikan akun Anda superadmin.';
        },
    });
};

const toggleAllowlist = (entry) => {
    router.post(route('admin.users.allowlist.update', entry.id), {
        _method: 'patch',
        auto_activate: !entry.auto_activate,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal update allowlist. Hanya superadmin yang dapat mengubah allowlist.';
        },
    });
};

const removeAllowlist = (entryId) => {
    router.post(route('admin.users.allowlist.destroy', entryId), {
        _method: 'delete',
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal menghapus allowlist. Hanya superadmin yang dapat menghapus allowlist.';
        },
    });
};

const formState = reactive({});

const syncFormState = (users) => {
    users.forEach((user) => {
        formState[user.id] = {
            role: user.role ?? 'viewer',
            is_active: Boolean(user.is_active),
            name: user.name ?? '',
            alias: user.alias ?? '',
        };
    });

    Object.keys(formState).forEach((userId) => {
        if (!users.some((user) => String(user.id) === String(userId))) {
            delete formState[userId];
        }
    });
};

watch(
    () => props.users,
    (users) => {
        syncFormState(users);
    },
    { immediate: true },
);

const pendingGoogleOnly = ref(false);

const filteredUsers = computed(() => {
    if (!pendingGoogleOnly.value) {
        return props.users;
    }

    return props.users.filter((user) => Boolean(user.google_id) && !Boolean(user.is_active));
});

const roleOrder = ['admin', 'useradmin', 'operator', 'viewer'];
const manageablePermissionUsers = computed(() => props.users.filter((user) => Boolean(user.can_manage)));

const roleLabel = (role) => ({
    admin: 'Superadmin',
    useradmin: 'Admin',
    operator: 'Operator',
    viewer: 'Viewer',
}[role] || role);

watch(
    manageablePermissionUsers,
    (users) => {
        if (!users.length) {
            selectedPermissionUserId.value = null;
            return;
        }

        const existing = users.some((item) => item.id === selectedPermissionUserId.value);
        if (!existing) {
            selectedPermissionUserId.value = users[0].id;
        }
    },
    { immediate: true },
);

const selectedPermissionUser = computed(() =>
    manageablePermissionUsers.value.find((user) => user.id === selectedPermissionUserId.value) || null,
);

const userPermissionMap = computed(() => {
    const map = {};

    props.userFeaturePermissions.forEach((entry) => {
        map[`${entry.user_id}:${entry.feature_key}`] = Boolean(entry.enabled);
    });

    return map;
});

const rolePermissionEnabled = (featureKey, role) =>
    Boolean(props.roleFeaturePermissions?.[featureKey]?.[role]);

const updateRolePermission = (role, featureKey, enabled) => {
    inlineError.value = '';

    router.post(route('admin.permissions.role.update'), {
        _method: 'patch',
        role,
        feature_key: featureKey,
        enabled,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal memperbarui permission role.';
        },
    });
};

const userOverrideValue = (userId, featureKey) => {
    const key = `${userId}:${featureKey}`;
    return Object.prototype.hasOwnProperty.call(userPermissionMap.value, key)
        ? userPermissionMap.value[key]
        : null;
};

const effectiveUserFeatureEnabled = (user, featureKey) => {
    if (!user) return false;

    const roleEnabled = rolePermissionEnabled(featureKey, user.role);
    if (!roleEnabled) {
        return false;
    }

    const override = userOverrideValue(user.id, featureKey);
    return override === null ? true : Boolean(override);
};

const setUserOverride = (userId, featureKey, enabled) => {
    inlineError.value = '';

    router.post(route('admin.permissions.user.update'), {
        _method: 'patch',
        user_id: userId,
        feature_key: featureKey,
        enabled,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal memperbarui override permission user.';
        },
    });
};

const resetUserOverride = (userId, featureKey) => {
    inlineError.value = '';

    router.post(route('admin.permissions.user.clear'), {
        _method: 'delete',
        user_id: userId,
        feature_key: featureKey,
    }, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal mereset override ke default role.';
        },
    });
};

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
    inlineError.value = '';

    if (!formState[userId]) {
        inlineError.value = 'Data pengguna tidak ditemukan di form.';
        return;
    }

    const { role, is_active, name, alias } = formState[userId];
    const payload = { _method: 'patch', role, is_active, name: name || null, alias: alias || null };

    router.post(route('admin.users.update', userId), payload, {
        preserveScroll: true,
        preserveState: true,
        onError: (errors) => {
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal memperbarui pengguna. Cek hak akses dan data yang dikirim.';
        },
    });
};
const requestDeleteUser = (userId) => {
    deleteModalState.userId = userId;
    deleteModalState.isOpen = true;
};

const cancelDeleteUser = () => {
    deleteModalState.isOpen = false;
    deleteModalState.userId = null;
};

const deleteUser = () => {
    if (!deleteModalState.userId) return;

    inlineError.value = '';

    router.post(`/admin/users/${deleteModalState.userId}`, {
        _method: 'delete'
    }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            cancelDeleteUser();
        },
        onError: (errors) => {
            cancelDeleteUser();
            const firstError = Object.values(errors || {}).find(Boolean);
            inlineError.value = firstError || 'Gagal menghapus pengguna.';
        },
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleString('id-ID', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
};

const permissionActionLabel = (action) => ({
    role_permission_update: 'Update Role',
    user_permission_override_set: 'Set Override User',
    user_permission_override_cleared: 'Reset Override User',
}[action] || action || '-');

const permissionStateLabel = (value) => {
    if (value === null || value === undefined) return 'default';
    return value ? 'allow' : 'deny';
};

const permissionStateClass = (value) => {
    if (value === null || value === undefined) {
        return 'bg-slate-500/10 text-slate-500 border border-[var(--border)]';
    }

    return value
        ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20'
        : 'bg-rose-500/10 text-rose-600 border border-rose-500/20';
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

                <div
                    v-if="inlineError"
                    class="rounded-2xl border border-rose-500/20 bg-rose-500/10 px-4 py-3 text-sm font-bold text-rose-600"
                >
                    {{ inlineError }}
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
                                    <option v-for="role in roles" :key="role" :value="role">{{ roleLabel(role) }}</option>
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

                <!-- Allowlist Google -->
                <div class="card-surface p-8 border-amber-500/30 bg-amber-500/[0.02]" v-if="isSuperAdmin">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-[0.2em] text-amber-600">
                                Daftar Akses Login Google
                            </h3>
                            <p class="mt-2 text-xs text-[var(--text-3)] font-semibold">
                                Hanya email akun Google dalam daftar ini yang boleh mendaftar via Google. Superadmin tetap diizinkan.
                                Email yang sudah <span class="text-emerald-600 font-bold">Aktif</span> berarti sudah berhasil login — entry allowlist-nya masih bisa dihapus karena tidak lagi diperlukan.
                            </p>
                        </div>
                        <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-2 text-[11px] font-black uppercase tracking-[0.2em] text-amber-600">
                            Superadmin: {{ superAdminEmail || 'Belum diset' }}
                        </div>
                    </div>

                    <form @submit.prevent="submitAllowlist" class="grid gap-6 md:grid-cols-3 items-end">
                        <div class="space-y-2 md:col-span-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Email Google</label>
                            <input v-model="allowlistForm.email" type="email" class="input-surface w-full" required placeholder="nama@pa-semarang.go.id">
                        </div>
                        <div class="space-y-2 md:col-span-1">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Catatan (Opsional)</label>
                            <input v-model="allowlistForm.note" type="text" class="input-surface w-full" placeholder="Unit / jabatan">
                        </div>
                        <div class="space-y-2 md:col-span-1">
                            <label class="inline-flex items-center gap-3 cursor-pointer mb-3 text-xs font-semibold text-[var(--text-2)]">
                                <input
                                    v-model="allowlistForm.auto_activate"
                                    type="checkbox"
                                    class="rounded-lg border-[var(--border)] text-emerald-600 shadow-sm focus:ring-emerald-500 focus:ring-offset-0 bg-[var(--surface-2)]"
                                >
                                Auto-aktif setelah login
                            </label>
                            <button type="submit" class="github-button !bg-amber-600 hover:!bg-amber-700 !w-full" :disabled="allowlistForm.processing">
                                Tambahkan ke Allowlist
                            </button>
                        </div>
                    </form>

                    <div v-if="allowlistForm.errors" class="mt-4 space-y-1">
                        <p v-for="(error, key) in allowlistForm.errors" :key="key" class="text-xs text-red-500 font-bold italic">{{ error }}</p>
                    </div>

                    <div class="mt-6 overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-1)]">
                        <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                            <thead class="bg-[var(--surface-2)]">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Email</th>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Catatan</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Status Akun</th>
                                    <th class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Auto Aktif</th>
                                    <th class="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                <tr v-for="entry in allowlist" :key="entry.id" :class="entry.is_registered ? 'opacity-60' : ''">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-[var(--text-1)] text-sm">{{ entry.email }}</p>
                                        <p v-if="entry.user_name" class="text-[11px] text-[var(--text-3)] mt-0.5">{{ entry.user_name }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-[var(--text-2)]">{{ entry.note || '-' }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <!-- Badge status akun: sudah login atau belum -->
                                        <span v-if="entry.is_registered && entry.is_active_user"
                                              class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest bg-emerald-500/10 text-emerald-600 border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Aktif
                                        </span>
                                        <span v-else-if="entry.is_registered && !entry.is_active_user"
                                              class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest bg-amber-500/10 text-amber-600 border border-amber-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                                            Menunggu Aktivasi
                                        </span>
                                        <span v-else
                                              class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-widest bg-slate-500/10 text-slate-500 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                            Belum Login
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="github-button !py-2 !px-4 !text-[11px]"
                                            :class="entry.auto_activate ? '!bg-emerald-600 hover:!bg-emerald-700' : '!bg-slate-600 hover:!bg-slate-700'"
                                            @click="toggleAllowlist(entry)"
                                        >
                                            {{ entry.auto_activate ? 'ON' : 'OFF' }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <button
                                            type="button"
                                            class="github-button !py-2 !px-4 !text-[11px] !bg-rose-600 hover:!bg-rose-700"
                                            @click="removeAllowlist(entry.id)"
                                        >
                                            Hapus
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="allowlist.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-xs font-bold uppercase tracking-[0.2em] text-[var(--text-3)] opacity-40">
                                        Belum ada email di allowlist
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="isSuperAdmin" class="card-surface p-8 border-violet-500/30 bg-violet-500/[0.03] space-y-6">
                    <div>
                        <h3 class="text-lg font-black uppercase tracking-[0.2em] text-violet-600">
                            Permission Fitur (Role & User)
                        </h3>
                        <p class="mt-2 text-xs text-[var(--text-3)] font-semibold">
                            Role menentukan batas maksimal akses fitur. Tiap user hanya bisa dibatasi lebih lanjut di bawah role tersebut.
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-1)]">
                        <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                            <thead class="bg-[var(--surface-2)]">
                                <tr>
                                    <th class="px-4 py-3 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Fitur</th>
                                    <th
                                        v-for="role in roleOrder"
                                        :key="`head-${role}`"
                                        class="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]"
                                    >
                                        {{ roleLabel(role) }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                <tr v-for="feature in featureCatalog" :key="feature.key">
                                    <td class="px-4 py-3 align-top">
                                        <p class="font-bold text-[var(--text-1)]">{{ feature.name }}</p>
                                        <p class="text-[11px] text-[var(--text-3)]">{{ feature.key }}</p>
                                    </td>
                                    <td v-for="role in roleOrder" :key="`${feature.key}-${role}`" class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="github-button !py-2 !px-3 !text-[10px]"
                                            :class="rolePermissionEnabled(feature.key, role) ? '!bg-emerald-600 hover:!bg-emerald-700' : '!bg-slate-600 hover:!bg-slate-700'"
                                            @click="updateRolePermission(role, feature.key, !rolePermissionEnabled(feature.key, role))"
                                        >
                                            {{ rolePermissionEnabled(feature.key, role) ? 'ON' : 'OFF' }}
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="featureCatalog.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-xs font-bold uppercase tracking-[0.2em] text-[var(--text-3)] opacity-40">
                                        Tidak ada feature catalog terdeteksi
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5">
                        <div class="flex flex-wrap items-end gap-4">
                            <div class="space-y-2 min-w-[17rem]">
                                <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Pilih User Override</label>
                                <select v-model="selectedPermissionUserId" class="input-surface w-full">
                                    <option v-for="user in manageablePermissionUsers" :key="user.id" :value="user.id">
                                        {{ user.name || user.email }} ({{ roleLabel(user.role) }})
                                    </option>
                                </select>
                            </div>
                            <p v-if="selectedPermissionUser" class="text-xs text-[var(--text-3)] font-semibold">
                                Batas role untuk user ini: <span class="font-black text-[var(--text-1)]">{{ roleLabel(selectedPermissionUser.role) }}</span>
                            </p>
                        </div>

                        <div v-if="selectedPermissionUser" class="mt-4 space-y-3">
                            <div
                                v-for="feature in featureCatalog"
                                :key="`override-${feature.key}`"
                                class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3"
                            >
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-bold text-[var(--text-1)]">{{ feature.name }}</p>
                                        <p class="text-[11px] text-[var(--text-3)]">{{ feature.key }}</p>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-3)]">
                                            Role: {{ rolePermissionEnabled(feature.key, selectedPermissionUser.role) ? 'ON' : 'OFF' }}
                                        </span>
                                        <button
                                            type="button"
                                            class="github-button !py-2 !px-3 !text-[10px]"
                                            :class="effectiveUserFeatureEnabled(selectedPermissionUser, feature.key) ? '!bg-emerald-600 hover:!bg-emerald-700' : '!bg-slate-600 hover:!bg-slate-700'"
                                            :disabled="!rolePermissionEnabled(feature.key, selectedPermissionUser.role)"
                                            @click="setUserOverride(selectedPermissionUser.id, feature.key, !effectiveUserFeatureEnabled(selectedPermissionUser, feature.key))"
                                        >{{ effectiveUserFeatureEnabled(selectedPermissionUser, feature.key) ? 'ON' : 'OFF' }}</button>
                                        <span
                                            class="text-[10px] font-black uppercase tracking-widest"
                                            :class="rolePermissionEnabled(feature.key, selectedPermissionUser.role)
                                                ? (userOverrideValue(selectedPermissionUser.id, feature.key) === null ? 'text-slate-500' : (userOverrideValue(selectedPermissionUser.id, feature.key) ? 'text-emerald-600' : 'text-rose-600'))
                                                : 'text-rose-600'"
                                        >
                                            {{ !rolePermissionEnabled(feature.key, selectedPermissionUser.role)
                                                ? 'DIBLOK ROLE'
                                                : (userOverrideValue(selectedPermissionUser.id, feature.key) === null
                                                    ? 'MENGIKUTI ROLE'
                                                    : (userOverrideValue(selectedPermissionUser.id, feature.key) ? 'IZINKAN USER' : 'BATASI USER')) }}
                                        </span>
                                        <button
                                            v-if="rolePermissionEnabled(feature.key, selectedPermissionUser.role) && userOverrideValue(selectedPermissionUser.id, feature.key) !== null"
                                            type="button"
                                            class="github-button !py-2 !px-3 !text-[10px] !bg-slate-600 hover:!bg-slate-700"
                                            @click="resetUserOverride(selectedPermissionUser.id, feature.key)"
                                        >Reset</button>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Nama Resmi / Alias</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Kontak / Email</th>
                                <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Provider</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Role Hak Akses</th>
                                <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Status Akun</th>
                                <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Konfigurasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[var(--border)]">
                            <tr v-for="user in filteredUsers" :key="user.id" class="hover:bg-[var(--surface-2)]/[0.4] transition-colors">
                                <td class="px-6 py-4">
                                    <div class="space-y-1.5">
                                        <input
                                            v-model="formState[user.id].name"
                                            type="text"
                                            class="input-surface !py-1.5 !text-xs !rounded-xl w-full"
                                            placeholder="Nama resmi..."
                                            :disabled="!user.can_manage"
                                        />
                                        <input
                                            v-model="formState[user.id].alias"
                                            type="text"
                                            class="input-surface !py-1.5 !text-xs !rounded-xl w-full opacity-70"
                                            placeholder="Alias / emoji ✦ (opsional)..."
                                            :disabled="!user.can_manage"
                                            autocapitalize="none"
                                            autocorrect="off"
                                            spellcheck="false"
                                        />
                                    </div>
                                </td>
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
                                        :disabled="!user.can_manage"
                                    >
                                        <option v-for="role in roles" :key="role" :value="role">
                                            {{ roleLabel(role) }}
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
                                                :disabled="!user.can_manage"
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
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            class="github-button !py-2 !px-4 !text-[11px] !bg-indigo-600 hover:!bg-indigo-700"
                                            @click="saveUser(user.id)"
                                            :disabled="!user.can_manage"
                                        >
                                            Update
                                        </button>
                                        <button
                                            v-if="user.can_manage"
                                            type="button"
                                            class="github-button !py-2 !px-4 !text-[11px] !bg-rose-600 hover:!bg-rose-700"
                                            @click="requestDeleteUser(user.id)"
                                        >
                                            Hapus
                                        </button>
                                    </div>
                                    <p v-if="!user.can_manage" class="mt-1 text-[10px] font-bold uppercase tracking-widest text-amber-600">
                                        Hanya superadmin utama
                                    </p>
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
                <!-- Tabel Login History -->
                <div class="mt-8">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-xl font-black uppercase tracking-[0.2em] text-[var(--text-1)]">
                            Riwayat Aktivitas Login
                        </h3>
                        <span class="text-xs font-semibold text-[var(--text-3)] bg-[var(--surface-2)] px-3 py-1 rounded-full">
                            100 Login Terakhir
                        </span>
                    </div>
                    
                    <div class="overflow-x-auto overflow-y-auto max-h-[500px] rounded-[28px] border border-[var(--border)] bg-[var(--surface-1)] shadow-2xl shadow-black/5 custom-scrollbar">
                        <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                            <thead class="bg-[var(--surface-2)] sticky top-0 z-10">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Waktu Login</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Pengguna</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Device</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Sistem & Browser</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Alamat IP</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Metode</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                <tr v-for="history in loginHistories" :key="history.id" class="hover:bg-[var(--surface-2)]/[0.4] transition-colors">
                                    <td class="px-6 py-4 font-semibold text-[var(--text-2)] whitespace-nowrap">
                                        {{ formatDate(history.logged_in_at) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <div v-if="history.user">
                                            <p class="font-bold text-[var(--text-1)]">{{ history.user.name }}</p>
                                            <p class="text-xs text-[var(--text-3)]">{{ history.user.email }}</p>
                                        </div>
                                        <span v-else class="text-[var(--text-3)] italic">User Dihapus</span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex rounded-lg px-3 py-1 text-[10px] font-black uppercase tracking-widest border"
                                            :class="{
                                                'bg-indigo-500/10 text-indigo-500 border-indigo-500/20': history.device_type === 'desktop',
                                                'bg-emerald-500/10 text-emerald-500 border-emerald-500/20': history.device_type === 'mobile',
                                                'bg-amber-500/10 text-amber-500 border-amber-500/20': history.device_type === 'tablet',
                                                'bg-[var(--surface-3)] text-[var(--text-3)] border-[var(--border)]': !['desktop', 'mobile', 'tablet'].includes(history.device_type)
                                            }">
                                            {{ history.device_type }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center text-[var(--text-2)] whitespace-nowrap">
                                        <span class="font-bold text-[var(--text-1)]">{{ history.platform }}</span> 
                                        <span class="mx-1 text-[var(--text-3)]">/</span> 
                                        {{ history.browser }}
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs text-[var(--text-2)] whitespace-nowrap">
                                        {{ history.ip_address || 'Tidak Terdeteksi' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.15em]"
                                            :class="history.login_method === 'google' ? 'bg-blue-500/10 text-blue-500 border border-blue-500/20' : 'bg-slate-500/10 text-slate-500 border border-[var(--border)]'"
                                        >
                                            {{ history.login_method === 'google' ? 'Google' : 'User/Pass' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="loginHistories.length === 0">
                                    <td colspan="6" class="px-6 py-12 text-center">
                                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--text-3)] opacity-40">
                                            Belum ada riwayat login
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="isSuperAdmin" class="mt-8">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-xl font-black uppercase tracking-[0.2em] text-[var(--text-1)]">
                            Riwayat Audit Permission
                        </h3>
                        <span class="text-xs font-semibold text-[var(--text-3)] bg-[var(--surface-2)] px-3 py-1 rounded-full">
                            120 Perubahan Terakhir
                        </span>
                    </div>

                    <div class="overflow-x-auto overflow-y-auto max-h-[500px] rounded-[28px] border border-[var(--border)] bg-[var(--surface-1)] shadow-2xl shadow-black/5 custom-scrollbar">
                        <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                            <thead class="bg-[var(--surface-2)] sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Waktu</th>
                                    <th class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Aktor</th>
                                    <th class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Target</th>
                                    <th class="px-4 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Aksi</th>
                                    <th class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Feature</th>
                                    <th class="px-4 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">State</th>
                                    <th class="px-4 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                <tr v-for="log in permissionAuditLogs" :key="log.id" class="hover:bg-[var(--surface-2)]/[0.4] transition-colors">
                                    <td class="px-4 py-3 font-semibold text-[var(--text-2)] whitespace-nowrap">{{ formatDate(log.created_at) }}</td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-[var(--text-1)]">{{ log.actor?.name || 'System' }}</p>
                                        <p class="text-xs text-[var(--text-3)]">{{ log.actor?.email || '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-bold text-[var(--text-1)]">{{ log.target_user?.name || (log.role ? `Role: ${log.role}` : '-') }}</p>
                                        <p class="text-xs text-[var(--text-3)]">{{ log.target_user?.email || '-' }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-[9px] font-black uppercase tracking-widest bg-violet-500/10 text-violet-600 border border-violet-500/20">
                                            {{ permissionActionLabel(log.action) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <p class="font-mono text-xs text-[var(--text-2)]">{{ log.feature_key }}</p>
                                        <p class="text-[10px] text-[var(--text-3)]">{{ log.scope.toUpperCase() }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="inline-flex items-center gap-1 text-[10px] font-black uppercase tracking-wider">
                                            <span class="rounded-full px-2 py-0.5" :class="permissionStateClass(log.old_enabled)">
                                                {{ permissionStateLabel(log.old_enabled) }}
                                            </span>
                                            <span class="text-[var(--text-3)]">→</span>
                                            <span class="rounded-full px-2 py-0.5" :class="permissionStateClass(log.new_enabled)">
                                                {{ permissionStateLabel(log.new_enabled) }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 font-mono text-xs text-[var(--text-2)] whitespace-nowrap">{{ log.ip_address || '-' }}</td>
                                </tr>
                                <tr v-if="permissionAuditLogs.length === 0">
                                    <td colspan="7" class="px-6 py-12 text-center">
                                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--text-3)] opacity-40">
                                            Belum ada riwayat audit permission
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Futuristic Delete Confirmation Modal -->
        <div v-if="deleteModalState.isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <!-- Backdrop blur -->
            <div 
                class="absolute inset-0 bg-slate-900/40 backdrop-blur-md transition-opacity" 
                @click="cancelDeleteUser"
            ></div>
            
            <!-- Modal Content -->
            <div 
                class="relative w-full max-w-md overflow-hidden rounded-3xl border border-rose-500/30 bg-slate-900/80 p-8 shadow-[0_0_40px_-10px_rgba(225,29,72,0.3)] backdrop-blur-xl transform transition-all scale-100 opacity-100"
            >
                <div class="absolute -top-24 -right-24 h-48 w-48 rounded-full bg-rose-600/20 blur-3xl"></div>
                <div class="absolute -bottom-24 -left-24 h-48 w-48 rounded-full bg-orange-600/20 blur-3xl"></div>
                
                <div class="relative z-10 flex flex-col items-center text-center">
                    <div class="mb-5 flex h-16 w-16 items-center justify-center rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-500 shadow-[0_0_15px_rgba(225,29,72,0.5)]">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    
                    <h3 class="mb-2 text-xl font-black tracking-wider text-white">Konfirmasi Hapus</h3>
                    <p class="mb-8 text-sm font-medium leading-relaxed text-slate-300">
                        Yakin ingin menghapus pengguna ini? <br>
                        <span class="text-rose-400">Semua data terkait (kecuali riwayat login/audit) mungkin akan ikut terhapus atau menjadi yatim piatu. Tindakan ini tidak bisa dibatalkan.</span>
                    </p>
                    
                    <div class="flex w-full gap-3">
                        <button 
                            @click="cancelDeleteUser"
                            class="flex-1 rounded-xl border border-slate-600 bg-slate-800/50 px-4 py-3 text-xs font-bold uppercase tracking-widest text-slate-300 transition-all hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500"
                        >
                            Batal
                        </button>
                        <button 
                            @click="deleteUser"
                            class="flex-1 rounded-xl border border-rose-500 bg-rose-600 px-4 py-3 text-xs font-bold uppercase tracking-widest text-white shadow-[0_0_15px_rgba(225,29,72,0.4)] transition-all hover:bg-rose-500 hover:shadow-[0_0_25px_rgba(225,29,72,0.6)] focus:outline-none focus:ring-2 focus:ring-rose-500"
                        >
                            Eksekusi
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: var(--surface-1);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: var(--surface-3);
    border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: var(--border);
}
</style>
