<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    clients: {
        type: Array,
        required: true,
    },
    status: {
        type: String,
        default: null,
    },
    last_client: {
        type: Object,
        default: null,
    }
});

const showCreateForm = ref(false);
const newClientForm = useForm({
    name: '',
    redirect: '',
    confidential: true,
});

const submitNewClient = () => {
    newClientForm.post(route('admin.oauth2.store'), {
        onSuccess: () => {
            newClientForm.reset();
            showCreateForm.value = false;
        },
    });
};

const deleteClient = (clientId) => {
    if (confirm('Apakah Anda yakin ingin menghapus OAuth2 Client ini? Semua token yang terkait akan tidak valid.')) {
        router.delete(route('admin.oauth2.destroy', clientId), {
            preserveScroll: true,
        });
    }
};

const copyToClipboard = (text) => {
    navigator.clipboard.writeText(text);
    alert('Tersalin ke clipboard!');
};
</script>

<template>
    <Head title="OAuth2 SSO Clients" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-black uppercase tracking-[0.2em] text-[var(--text-1)]">
                    OAuth2 SSO Applications
                </h2>
                <button
                    @click="showCreateForm = !showCreateForm"
                    class="github-button !bg-blue-600 hover:!bg-blue-700 !px-5"
                >
                    {{ showCreateForm ? 'Batal' : 'Register Aplikasi Baru' }}
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

                <!-- ALERT: Client Secret (Hanya Muncul Sekali Setelah Create) -->
                <div v-if="last_client" class="card-surface p-8 border-rose-500/50 bg-rose-500/[0.05] animate-bounce-subtle">
                    <div class="flex items-start gap-4">
                        <div class="p-3 rounded-xl bg-rose-500/20 text-rose-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1 space-y-4">
                            <div>
                                <h3 class="text-lg font-black uppercase tracking-[0.1em] text-rose-600">Simpan Client Secret Sekarang!</h3>
                                <p class="text-sm text-rose-500 font-semibold italic mt-1">
                                    Demi keamanan, secret ini hanya akan ditampilkan SEKALI INI SAJA. Jangan tutup halaman ini sebelum Anda menyimpannya.
                                </p>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-rose-400">Client ID</label>
                                    <div class="flex items-center gap-2 bg-white/50 p-2 rounded-lg border border-rose-200">
                                        <code class="text-sm font-mono text-rose-700 flex-1">{{ last_client.id }}</code>
                                        <button @click="copyToClipboard(last_client.id)" class="text-rose-400 hover:text-rose-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <label class="text-[10px] font-black uppercase tracking-widest text-rose-400">Client Secret</label>
                                    <div class="flex items-center gap-2 bg-white/50 p-2 rounded-lg border border-rose-200">
                                        <code class="text-sm font-mono text-rose-700 flex-1">{{ last_client.secret }}</code>
                                        <button @click="copyToClipboard(last_client.secret)" class="text-rose-400 hover:text-rose-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Registrasi Client Baru -->
                <div v-if="showCreateForm" class="card-surface p-8 border-blue-500/30 bg-blue-500/[0.02]">
                    <h3 class="text-lg font-black uppercase tracking-[0.2em] text-blue-600 mb-6">
                        Register OAuth2 Client Application
                    </h3>
                    <form @submit.prevent="submitNewClient" class="grid gap-6 md:grid-cols-3 items-end">
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Application Name</label>
                            <input v-model="newClientForm.name" type="text" class="input-surface w-full" required placeholder="Contoh: Pandanaran Dashboard">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Redirect URI (Callback)</label>
                            <input v-model="newClientForm.redirect" type="url" class="input-surface w-full" required placeholder="http://192.168.88.44/auth/callback">
                        </div>
                        <div class="space-y-2">
                            <button type="submit" class="github-button !bg-emerald-600 hover:!bg-emerald-700 !w-full" :disabled="newClientForm.processing">
                                Generate Credentials
                            </button>
                        </div>
                    </form>
                    <div v-if="newClientForm.errors" class="mt-4 space-y-1">
                        <p v-for="(error, key) in newClientForm.errors" :key="key" class="text-xs text-red-500 font-bold italic">{{ error }}</p>
                    </div>
                </div>

                <!-- Tabel OAuth Clients -->
                <div class="card-surface p-8">
                    <div class="mb-6">
                        <h3 class="text-lg font-black uppercase tracking-[0.2em] text-[var(--text-1)]">
                            Registered Applications
                        </h3>
                        <p class="mt-1 text-xs text-[var(--text-3)] font-semibold italic">
                            Aplikasi di bawah ini diizinkan untuk melakukan autentikasi SSO melalui Lawangsewu.
                        </p>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-1)]">
                        <table class="min-w-full divide-y divide-[var(--border)] text-sm">
                            <thead class="bg-[var(--surface-2)]">
                                <tr>
                                    <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Application</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Client ID</th>
                                    <th class="px-6 py-4 text-left text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Redirect URI</th>
                                    <th class="px-6 py-4 text-center text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Confidential</th>
                                    <th class="px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[var(--border)]">
                                <tr v-for="client in clients" :key="client.id" class="hover:bg-[var(--surface-2)]/[0.4] transition-colors">
                                    <td class="px-6 py-4">
                                        <p class="font-bold text-[var(--text-1)]">{{ client.name }}</p>
                                        <p class="text-[10px] text-[var(--text-3)] mt-1 uppercase tracking-widest">Type: {{ client.personal_access_client ? 'Personal' : (client.password_client ? 'Password' : 'Auth Code') }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <code class="text-xs font-mono bg-[var(--surface-3)] px-2 py-1 rounded text-blue-600 border border-[var(--border)]">{{ client.id }}</code>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs text-[var(--text-2)] break-all">
                                            {{ Array.isArray(client.redirect_uris) ? client.redirect_uris.join(', ') : (client.redirect || '-') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span :class="['px-2 py-1 rounded-lg text-[10px] font-black uppercase tracking-widest border', 
                                            client.confidential ? 'bg-emerald-500/10 text-emerald-600 border-emerald-500/20' : 'bg-amber-500/10 text-amber-600 border-amber-500/20']">
                                            {{ client.confidential ? 'YES' : 'NO' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button
                                            @click="deleteClient(client.id)"
                                            class="github-button !py-2 !px-4 !text-[11px] !bg-rose-600 hover:!bg-rose-700"
                                        >
                                            Revoke
                                        </button>
                                    </td>
                                </tr>
                                <tr v-if="clients.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-[var(--text-3)] opacity-40">
                                            Belum ada aplikasi OAuth2 yang terdaftar.
                                        </p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- SSO Guide -->
                <div class="card-surface p-8 border-violet-500/30 bg-violet-500/[0.02]">
                    <h3 class="text-lg font-black uppercase tracking-[0.1em] text-violet-600 mb-4">SSO Implementation Guide</h3>
                    <div class="grid gap-6 md:grid-cols-2 text-sm text-[var(--text-2)]">
                        <div class="space-y-3">
                            <p><strong class="text-violet-600">Base URL:</strong> <code>https://lawangsewu.pa-semarang.go.id</code></p>
                            <p><strong class="text-violet-600">Authorize:</strong> <code>/oauth/authorize</code></p>
                            <p><strong class="text-violet-600">Token:</strong> <code>/oauth/token</code></p>
                            <p><strong class="text-violet-600">User Info:</strong> <code>/api/user</code> (requires <code>Authorization: Bearer ...</code>)</p>
                        </div>
                        <div class="p-4 rounded-2xl bg-black/[0.03] border border-[var(--border)] font-mono text-[11px]">
                            <p class="text-violet-500 mb-2">// OIDC Scopes</p>
                            <p>scope: "openid profile email"</p>
                            <p class="text-violet-500 mt-4 mb-2">// Response Data</p>
                            <p>{ "id": 1, "name": "...", "email": "...", "role": "..." }</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.animate-bounce-subtle {
    animation: bounce-subtle 3s infinite;
}
@keyframes bounce-subtle {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-5px); }
}
</style>
