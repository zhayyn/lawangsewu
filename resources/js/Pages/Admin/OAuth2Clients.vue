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

const ssoSteps = [
    {
        icon: '🔀',
        title: 'Redirect ke Authorize',
        desc: 'Arahkan user ke /oauth/authorize dengan client_id dan redirect_uri.',
        badgeClass: 'bg-violet-500/20 text-violet-600',
    },
    {
        icon: '📨',
        title: 'Terima Authorization Code',
        desc: 'Setelah user setuju, Lawangsewu redirect ke callback dengan query ?code=...',
        badgeClass: 'bg-blue-500/20 text-blue-600',
    },
    {
        icon: '🔑',
        title: 'Tukar Code → Token',
        desc: 'POST /oauth/token dengan client_secret dari server Anda untuk mendapat access_token.',
        badgeClass: 'bg-emerald-500/20 text-emerald-600',
    },
    {
        icon: '👤',
        title: 'Ambil Data User',
        desc: 'GET /api/user dengan Bearer token untuk mendapat id, name, email, dan role.',
        badgeClass: 'bg-amber-500/20 text-amber-600',
    },
];

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

                <!-- SSO Implementation Guide — Panduan Lengkap -->
                <div class="card-surface p-8 border-violet-500/30 bg-violet-500/[0.02] space-y-8">

                    <!-- Header Guide -->
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-500/20 flex items-center justify-center flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-violet-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-[0.1em] text-violet-600">Panduan Integrasi SSO OAuth2</h3>
                            <p class="text-xs text-[var(--text-3)] mt-0.5">Langkah demi langkah menghubungkan aplikasi klien ke Lawangsewu sebagai Identity Provider</p>
                        </div>
                    </div>

                    <!-- Endpoint Reference -->
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)] mb-3">🔗 Endpoint URLs</p>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-1)] divide-y divide-[var(--border)] text-xs">
                                <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                    <span class="font-bold text-[var(--text-2)]">Authorization URL</span>
                                    <code class="font-mono text-violet-600 bg-violet-500/10 px-2 py-0.5 rounded">/oauth/authorize</code>
                                </div>
                                <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                    <span class="font-bold text-[var(--text-2)]">Token URL</span>
                                    <code class="font-mono text-blue-600 bg-blue-500/10 px-2 py-0.5 rounded">/oauth/token</code>
                                </div>
                                <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                    <span class="font-bold text-[var(--text-2)]">Token Refresh</span>
                                    <code class="font-mono text-blue-600 bg-blue-500/10 px-2 py-0.5 rounded">/oauth/token/refresh</code>
                                </div>
                                <div class="px-4 py-2.5 flex items-center justify-between gap-3">
                                    <span class="font-bold text-[var(--text-2)]">User Info</span>
                                    <code class="font-mono text-emerald-600 bg-emerald-500/10 px-2 py-0.5 rounded">/api/user</code>
                                </div>
                            </div>
                            <div class="rounded-xl border border-[var(--border)] bg-[var(--surface-1)] divide-y divide-[var(--border)] text-xs">
                                <div class="px-4 py-2.5">
                                    <p class="font-black text-[var(--text-3)] uppercase tracking-wider mb-1">Base URL Production</p>
                                    <code class="font-mono text-[var(--text-1)]">https://lawangsewu.pa-semarang.go.id</code>
                                </div>
                                <div class="px-4 py-2.5">
                                    <p class="font-black text-[var(--text-3)] uppercase tracking-wider mb-1">Grant Type</p>
                                    <code class="font-mono text-amber-600">authorization_code</code>
                                </div>
                                <div class="px-4 py-2.5">
                                    <p class="font-black text-[var(--text-3)] uppercase tracking-wider mb-1">Scopes yang Tersedia</p>
                                    <code class="font-mono text-[var(--text-2)]">openid  profile  email</code>
                                </div>
                                <div class="px-4 py-2.5">
                                    <p class="font-black text-[var(--text-3)] uppercase tracking-wider mb-1">Response Type</p>
                                    <code class="font-mono text-[var(--text-2)]">code</code>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alur 4 Langkah -->
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)] mb-4">⚙️ Alur Integrasi (4 Langkah)</p>
                        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4">
                            <div v-for="(step, i) in ssoSteps" :key="i"
                                class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4 relative overflow-hidden">
                                <div class="absolute top-3 right-3 w-7 h-7 rounded-full flex items-center justify-center text-xs font-black"
                                    :class="step.badgeClass">{{ i + 1 }}</div>
                                <div class="text-2xl mb-2">{{ step.icon }}</div>
                                <p class="font-black text-sm text-[var(--text-1)] mb-1">{{ step.title }}</p>
                                <p class="text-xs text-[var(--text-3)] leading-relaxed">{{ step.desc }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contoh Request -->
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)] mb-3">📋 Contoh Kode Integrasi (PHP / Laravel)</p>
                        <div class="space-y-3">

                            <!-- Step 1: Redirect to authorize -->
                            <div class="rounded-xl border border-[var(--border)] overflow-hidden">
                                <div class="px-4 py-2 bg-[var(--surface-2)] flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-violet-500/20 text-violet-600 text-[10px] font-black flex items-center justify-center">1</span>
                                    <span class="text-[11px] font-black uppercase tracking-wider text-[var(--text-3)]">Arahkan User ke Halaman Otorisasi</span>
                                </div>
                                <pre class="p-4 text-[11px] font-mono text-[var(--text-2)] overflow-x-auto leading-relaxed bg-black/[0.02]"><code>$query = http_build_query([
    'client_id'     => 'CLIENT_ID_DARI_DASHBOARD',
    'redirect_uri'  => 'http://192.168.88.44:8089/auth/callback',
    'response_type' => 'code',
    'scope'         => '',   // Kosong = akses dasar
]);
return redirect('https://lawangsewu.pa-semarang.go.id/oauth/authorize?' . $query);</code></pre>
                            </div>

                            <!-- Step 2: Handle callback -->
                            <div class="rounded-xl border border-[var(--border)] overflow-hidden">
                                <div class="px-4 py-2 bg-[var(--surface-2)] flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-blue-500/20 text-blue-600 text-[10px] font-black flex items-center justify-center">2</span>
                                    <span class="text-[11px] font-black uppercase tracking-wider text-[var(--text-3)]">Terima Authorization Code di Callback</span>
                                </div>
                                <pre class="p-4 text-[11px] font-mono text-[var(--text-2)] overflow-x-auto leading-relaxed bg-black/[0.02]"><code>// Di route: GET /auth/callback
$code  = $request->query('code');   // ← Authorization Code
$error = $request->query('error');  // ← Jika user klik Tolak

if ($error) return redirect('/login')->with('error', 'Akses ditolak.');</code></pre>
                            </div>

                            <!-- Step 3: Exchange code for token -->
                            <div class="rounded-xl border border-[var(--border)] overflow-hidden">
                                <div class="px-4 py-2 bg-[var(--surface-2)] flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-emerald-500/20 text-emerald-600 text-[10px] font-black flex items-center justify-center">3</span>
                                    <span class="text-[11px] font-black uppercase tracking-wider text-[var(--text-3)]">Tukar Code → Access Token (Server-to-Server)</span>
                                </div>
                                <pre class="p-4 text-[11px] font-mono text-[var(--text-2)] overflow-x-auto leading-relaxed bg-black/[0.02]"><code>$response = Http::post('https://lawangsewu.pa-semarang.go.id/oauth/token', [
    'grant_type'    => 'authorization_code',
    'client_id'     => 'CLIENT_ID_DARI_DASHBOARD',
    'client_secret' => 'CLIENT_SECRET_DARI_DASHBOARD',
    'redirect_uri'  => 'http://192.168.88.44:8089/auth/callback',
    'code'          => $code,
]);
$token = $response->json('access_token');</code></pre>
                            </div>

                            <!-- Step 4: Get user info -->
                            <div class="rounded-xl border border-[var(--border)] overflow-hidden">
                                <div class="px-4 py-2 bg-[var(--surface-2)] flex items-center gap-2">
                                    <span class="w-5 h-5 rounded-full bg-amber-500/20 text-amber-600 text-[10px] font-black flex items-center justify-center">4</span>
                                    <span class="text-[11px] font-black uppercase tracking-wider text-[var(--text-3)]">Ambil Data User</span>
                                </div>
                                <pre class="p-4 text-[11px] font-mono text-[var(--text-2)] overflow-x-auto leading-relaxed bg-black/[0.02]"><code>$user = Http::withToken($token)
    ->get('https://lawangsewu.pa-semarang.go.id/api/user')
    ->json();

// Response: { "id": 1, "name": "...", "email": "...", "role": "admin" }
Auth::login(User::updateOrCreate(['email' => $user['email']], $user));</code></pre>
                            </div>

                        </div>
                    </div>

                    <!-- Tips & Peringatan -->
                    <div class="grid gap-4 md:grid-cols-2">
                        <!-- Tips Redirect URI -->
                        <div class="rounded-2xl border border-amber-500/30 bg-amber-500/[0.04] p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-lg">⚠️</span>
                                <p class="text-[11px] font-black uppercase tracking-widest text-amber-600">Tips Redirect URI</p>
                            </div>
                            <ul class="space-y-2 text-xs text-[var(--text-2)]">
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5 flex-shrink-0">✓</span>
                                    <span>Redirect URI di dashboard harus <strong>sama persis</strong> dengan yang dikirim aplikasi klien</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-red-500 mt-0.5 flex-shrink-0">✗</span>
                                    <span>Trailing slash berbeda = <strong>error!</strong><br>
                                        <code class="text-[10px]">/auth/callback</code> ≠ <code class="text-[10px]">/auth/callback<strong>/</strong></code>
                                    </span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5 flex-shrink-0">✓</span>
                                    <span>Boleh pakai IP lokal (<code class="text-[10px]">192.168.x.x</code>) asalkan server bisa menjangkau Lawangsewu</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5 flex-shrink-0">✓</span>
                                    <span>Satu klien bisa punya lebih dari satu Redirect URI — daftarkan semuanya saat registrasi</span>
                                </li>
                            </ul>
                        </div>

                        <!-- Keamanan -->
                        <div class="rounded-2xl border border-rose-500/30 bg-rose-500/[0.04] p-5">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-lg">🔒</span>
                                <p class="text-[11px] font-black uppercase tracking-widest text-rose-600">Keamanan</p>
                            </div>
                            <ul class="space-y-2 text-xs text-[var(--text-2)]">
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-500 mt-0.5 flex-shrink-0">!</span>
                                    <span><strong>Client Secret</strong> hanya tampil sekali saat pembuatan. Simpan di <code class="text-[10px]">.env</code> aplikasi klien, jangan di kode</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-500 mt-0.5 flex-shrink-0">!</span>
                                    <span>Pertukaran token (Langkah 3) harus dilakukan di <strong>server-side</strong>, tidak boleh dari browser</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-rose-500 mt-0.5 flex-shrink-0">!</span>
                                    <span>Jika secret bocor, <strong>Revoke</strong> klien dari tabel di atas dan buat ulang</span>
                                </li>
                                <li class="flex items-start gap-2">
                                    <span class="text-emerald-500 mt-0.5 flex-shrink-0">✓</span>
                                    <span>Access token sudah mengandung <strong>user role</strong> — gunakan untuk otorisasi di sisi klien</span>
                                </li>
                            </ul>
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
