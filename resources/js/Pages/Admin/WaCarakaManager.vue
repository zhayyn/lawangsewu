<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import AppToast from '@/Components/lawangsewu/AppToast.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

// ─── Toast ref ────────────────────────────────────────────────────────────────
const toast = ref(null);
const t = {
    ok  : (title, msg) => toast.value?.success(title, msg),
    err : (title, msg) => toast.value?.error(title, msg),
    info: (title, msg) => toast.value?.info(title, msg),
};

const props = defineProps({
    appMeta: { type: Object, default: () => ({}) },
    navGroups: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    messageStats: { type: Object, default: () => ({}) },
    recentLogs: { type: Array, default: () => [] },
    conversations: { type: Array, default: () => [] },
    config: { type: Object, default: () => ({}) },
});

const activeTab = ref('overview');
const loading = ref(false);
const deviceStatus = ref('checking');
const runtimeHealth = ref({});
const qrDataUrl = ref('');
const logs = ref(props.recentLogs);
const convos = ref(props.conversations);
const localStats = ref(props.stats);
const localMsgStats = ref(props.messageStats);
const pollRef = ref(null);
const generatingQr = ref(false);

// Send form
const sendTo = ref('');
const sendText = ref('');
const sendState = ref('idle');

// Broadcast form
const broadcastRecipients = ref('');
const broadcastText = ref('');
const broadcastState = ref('idle');
const broadcastResult = ref(null);

// Settings
const bgInput = ref(props.config.background || '');
const bgSaving = ref(false);

const isConnected = computed(() => Boolean(runtimeHealth.value?.connected || runtimeHealth.value?.status === 'connected'));

const callApi = async (action, method = 'get', body = null) => {
    const opts = {
        method,
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    };
    if (method === 'post') {
        opts.headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(body || {});
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) opts.headers['X-CSRF-TOKEN'] = csrfToken;

    const url = route('admin.wacaraka.api', { action });
    const res = await fetch(url, opts);
    const data = await res.json();
    // Jika backend mengembalikan ok:false, lempar error agar ditangkap caller
    if (data?.ok === false) {
        const err = new Error(data?.error || data?.message || 'Terjadi kesalahan.');
        err.detail = data?.detail ?? null;
        throw err;
    }
    return data;
};

const refreshHealth = async () => {
    try {
        const data = await callApi('health');
        runtimeHealth.value = data?.data ?? data ?? {};
        const raw = runtimeHealth.value;
        deviceStatus.value = raw?.connected || raw?.status === 'connected' ? 'connected' : 'disconnected';
    } catch {
        deviceStatus.value = 'error';
    }
};

const refreshStats = async () => {
    try {
        const [s, m] = await Promise.all([callApi('stats'), callApi('message-stats')]);
        localStats.value    = s?.data ?? s ?? {};
        localMsgStats.value = m?.data ?? m ?? {};
    } catch { /* silent */ }
};

const refreshLogs = async () => {
    try {
        const d = await callApi('logs');
        logs.value = d?.data ?? d ?? [];
    } catch { /* silent */ }
};

const refreshConvos = async () => {
    try {
        const d = await callApi('conversations');
        convos.value = d?.data ?? d ?? [];
    } catch { /* silent */ }
};

const refreshAll = async (silent = false) => {
    if (loading.value) return;
    loading.value = true;
    try {
        await Promise.all([refreshHealth(), refreshStats(), refreshLogs(), refreshConvos()]);
        if (!silent) t.ok('Data diperbarui', 'Status device dan statistik telah disinkronkan.');
    } catch (e) {
        if (!silent) t.err('Refresh gagal', e.message);
    } finally {
        loading.value = false;
    }
};

// Label & pesan untuk setiap aksi
const ACTION_META = {
    'restart'       : { ok: 'Device di-restart',        okMsg: 'Runtime WA Caraka sedang memulai ulang.',        fail: 'Restart gagal' },
    'reconnect'     : { ok: 'Reconnect dikirim',         okMsg: 'Device mencoba terhubung kembali ke WhatsApp.',   fail: 'Reconnect gagal' },
    'disconnect'    : { ok: 'Device diputus',            okMsg: 'Sesi WhatsApp telah diakhiri.',                   fail: 'Disconnect gagal' },
    'history/clear' : { ok: 'History dihapus',           okMsg: 'Riwayat pesan runtime telah dibersihkan.',        fail: 'Gagal menghapus history' },
    'inbox/clear'   : { ok: 'Inbox dihapus',             okMsg: 'Seluruh pesan inbox telah dihapus dari database.', fail: 'Gagal menghapus inbox' },
};

const deviceAction = async (action, _label) => {
    if (loading.value) return;
    loading.value = true;
    const meta = ACTION_META[action] ?? { ok: 'Berhasil', okMsg: '', fail: 'Aksi gagal' };
    try {
        await callApi(action, 'post');
        t.ok(meta.ok, meta.okMsg);
        setTimeout(() => refreshAll(true), 1200);
    } catch (e) {
        t.err(meta.fail, e.message);
    } finally {
        loading.value = false;
    }
};

const confirmDeleteInbox = async () => {
    if (!window.confirm('Yakin ingin menghapus SELURUH pesan inbox dari database? Aksi ini tidak bisa di-undo!')) return;
    await deviceAction('inbox/clear', 'Delete Inbox');
};

const sendMessage = async () => {
    const to = sendTo.value.trim();
    const text = sendText.value.trim();
    if (!to || !text) { t.err('Form tidak lengkap', 'Nomor tujuan dan isi pesan wajib diisi.'); return; }
    sendState.value = 'sending';
    try {
        await callApi('send-text', 'post', { to, text });
        sendState.value = 'sent';
        sendText.value = '';
        t.ok('Pesan terkirim', `Pesan berhasil dikirim ke ${to}.`);
        await refreshStats();
        setTimeout(() => { sendState.value = 'idle'; }, 3000);
    } catch (e) {
        sendState.value = 'error';
        t.err('Pesan gagal terkirim', e.message);
        setTimeout(() => { sendState.value = 'idle'; }, 4000);
    }
};

const sendBroadcast = async () => {
    const raw = broadcastRecipients.value.trim();
    const text = broadcastText.value.trim();
    if (!raw || !text) { t.err('Form tidak lengkap', 'Daftar nomor dan isi pesan wajib diisi.'); return; }
    const recipients = raw.split(/[\n,;]+/).map(s => s.trim()).filter(Boolean);
    broadcastState.value = 'sending';
    broadcastResult.value = null;
    try {
        const data = await callApi('broadcast', 'post', { recipients, text });
        broadcastState.value = 'sent';
        broadcastResult.value = data?.data ?? data;
        const res = broadcastResult.value;
        t.ok(
            `Broadcast selesai — ${res?.succeeded ?? 0}/${res?.total ?? recipients.length} berhasil`,
            res?.failed ? `${res.failed} nomor gagal dikirim.` : 'Semua pesan berhasil terkirim.'
        );
        await refreshStats();
        setTimeout(() => { broadcastState.value = 'idle'; }, 4000);
    } catch (e) {
        broadcastState.value = 'error';
        broadcastResult.value = { error: e.message };
        t.err('Broadcast gagal', e.message);
        setTimeout(() => { broadcastState.value = 'idle'; }, 5000);
    }
};

const bgSaved = ref(false);
const saveBackground = async () => {
    bgSaving.value = true;
    bgSaved.value = false;
    try {
        await callApi('save-background', 'post', { background: bgInput.value });
        bgSaved.value = true;
        t.ok('Background tersimpan', bgInput.value ? 'Tampilan chat telah diperbarui.' : 'Background dikembalikan ke default.');
        setTimeout(() => { bgSaved.value = false; }, 3000);
    } catch (e) {
        t.err('Gagal menyimpan background', e.message);
    } finally {
        bgSaving.value = false;
    }
};

let qrPoll = null;

const refreshQr = async () => {
    try {
        const data = await callApi('qr');
        qrDataUrl.value = data?.qrDataUrl || data?.qr || (typeof data === 'string' && data.startsWith('data:image') ? data : '');
    } catch { /* silent */ }
};

const startQrPoll = async () => {
    if (generatingQr.value || qrPoll) return;
    generatingQr.value = true;
    t.info('Menyiapkan QR', 'Memulai ulang runtime dan menunggu kode QR...');
    try {
        await callApi('restart', 'post');
        await new Promise(resolve => setTimeout(resolve, 2000));
        await refreshQr();

        if (!qrPoll) {
            qrPoll = setInterval(async () => {
                if (isConnected.value) {
                    stopQrPoll();
                    generatingQr.value = false;
                    t.ok('Device terhubung!', 'WhatsApp berhasil dipasangkan via QR Code.');
                } else {
                    await refreshQr();
                }
            }, 3000);
        }
    } catch (e) {
        generatingQr.value = false;
        t.err('Gagal generate QR', e.message);
    }
};

const stopQrPoll = () => {
    if (qrPoll) {
        clearInterval(qrPoll);
        qrPoll = null;
    }
    generatingQr.value = false;
};

onMounted(() => {
    refreshAll();
    pollRef.value = setInterval(() => {
        refreshHealth();
        if (isConnected.value && qrPoll) stopQrPoll();
    }, 8000);
});

onUnmounted(() => {
    if (pollRef.value) clearInterval(pollRef.value);
    stopQrPoll();
});
</script>

<template>
    <Head title="Admin · WA Caraka Manager" />
    <AppToast ref="toast" />

    <LawangsewuLayout current-route="admin.wacaraka.index" :nav-groups="navGroups" :app-meta="appMeta">
        <template #header>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 shadow-[0_4px_12px_rgba(16,185,129,0.4),inset_0__2px_4px_rgba(255,255,255,0.4)] flex items-center justify-center text-white font-extrabold text-lg transform hover:scale-105 hover:rotate-3 transition-transform cursor-pointer">WA</div>
                    <div>
                        <h2 class="text-xl font-semibold leading-tight text-gray-800">WA Caraka Manager</h2>
                        <span class="text-xs text-slate-500">Superadmin Console</span>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="flex items-center gap-2 px-3 py-1.5 rounded-full border text-xs font-semibold bg-white"
                         :class="deviceStatus === 'connected' ? 'border-emerald-200 text-emerald-700' : 'border-amber-200 text-amber-700'">
                        <span class="w-2 h-2 rounded-full" :class="deviceStatus === 'connected' ? 'bg-emerald-500' : 'bg-amber-500'"></span>
                        {{ deviceStatus === 'connected' ? 'Device Online' : 'Device Offline' }}
                    </div>
                </div>
            </div>
        </template>

        <div class="py-8 font-sans text-slate-800 antialiased">
            <div class="max-w-7xl mx-auto px-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-4 mb-8">
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Total Pesan</p>
                    <p class="text-2xl font-black text-slate-800 mt-1">{{ localMsgStats.totalMessages ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-500">Masuk</p>
                    <p class="text-2xl font-black text-emerald-600 mt-1">{{ localMsgStats.inbound ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-blue-500">Keluar</p>
                    <p class="text-2xl font-black text-blue-600 mt-1">{{ localMsgStats.outbound ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-rose-500">Belum Dibalas</p>
                    <p class="text-2xl font-black text-rose-600 mt-1">{{ localMsgStats.unreplied ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Hari Ini (In)</p>
                    <p class="text-2xl font-black text-slate-800 mt-1">{{ localMsgStats.todayInbound ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Hari Ini (Out)</p>
                    <p class="text-2xl font-black text-slate-800 mt-1">{{ localMsgStats.todayOutbound ?? 0 }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-violet-500">Percakapan</p>
                    <p class="text-2xl font-black text-violet-600 mt-1">{{ localMsgStats.conversations ?? 0 }}</p>
                </div>
            </div>

            <!-- Tab Nav -->
            <div class="flex gap-1 mb-6 bg-white rounded-xl border border-slate-200 p-1 shadow-sm w-fit">
                <button v-for="tab in ['overview', 'device', 'send', 'broadcast', 'logs']" :key="tab"
                        @click="activeTab = tab"
                        class="px-4 py-2 rounded-lg text-xs font-bold capitalize transition-all"
                        :class="activeTab === tab ? 'bg-slate-800 text-white shadow' : 'text-slate-500 hover:bg-slate-100'">
                    {{ tab === 'overview' ? 'Ringkasan' : tab === 'device' ? 'Device' : tab === 'send' ? 'Kirim Pesan' : tab === 'broadcast' ? 'Broadcast' : 'Log Pesan' }}
                </button>
            </div>

            <!-- Tab: Overview -->
            <section v-if="activeTab === 'overview'" class="space-y-6">
                <div class="grid lg:grid-cols-2 gap-6">
                    <!-- Config -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-700 mb-4">Konfigurasi Runtime</h3>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between"><span class="text-slate-400">Base URL</span><code class="text-xs bg-slate-100 px-2 py-0.5 rounded">{{ config.baseUrl }}</code></div>
                            <div class="flex justify-between"><span class="text-slate-400">Broadcast Limit</span><span class="font-semibold">{{ config.broadcastLimit }} penerima</span></div>
                            <div class="flex justify-between"><span class="text-slate-400">Timeout</span><span class="font-semibold">{{ config.timeout }}s</span></div>
                            <div class="flex justify-between"><span class="text-slate-400">Logging</span><span :class="config.loggingEnabled ? 'text-emerald-600' : 'text-rose-500'" class="font-semibold">{{ config.loggingEnabled ? 'Aktif' : 'Nonaktif' }}</span></div>
                        </div>
                        
                        <div class="mt-6 border-t border-slate-100 pt-4">
                            <h4 class="text-xs font-bold text-slate-600 mb-2">Tampilan Chat (Background)</h4>
                            <div class="flex gap-2">
                                <input v-model="bgInput" type="text" placeholder="URL Gambar atau Warna (ex: #efeae2)" class="flex-1 border border-slate-200 rounded-xl px-3 py-2 text-sm outline-none focus:border-blue-400">
                                <button @click="saveBackground" :disabled="bgSaving" class="rounded-xl px-4 py-2 text-xs font-bold disabled:opacity-50 transition-colors" :class="bgSaved ? 'bg-emerald-600 text-white hover:bg-emerald-700' : 'bg-slate-800 text-white hover:bg-slate-700'">{{ bgSaving ? 'Menyimpan...' : bgSaved ? '✓ Tersimpan' : 'Simpan' }}</button>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Isi URL gambar (harus diawali http/https) atau warna. Kosongkan untuk menggunakan background default.</p>
                        </div>
                    </div>

                    <!-- Conversations -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-700">Percakapan Terbaru</h3>
                            <button @click="refreshConvos" class="text-xs text-slate-400 hover:text-slate-600">↻ Refresh</button>
                        </div>
                        <div v-if="convos.length === 0" class="text-sm text-slate-400 py-8 text-center">Belum ada percakapan.</div>
                        <div v-else class="space-y-2 max-h-64 overflow-auto">
                            <div v-for="c in convos" :key="c.conversationId" class="flex items-center justify-between p-3 rounded-xl border border-slate-100 hover:bg-slate-50 transition">
                                <div>
                                    <p class="text-sm font-semibold text-slate-700">{{ c.remoteNumber }}</p>
                                    <p class="text-[10px] text-slate-400">{{ c.messageCount }} pesan</p>
                                </div>
                                <span v-if="c.unrepliedCount > 0" class="px-2 py-0.5 bg-rose-100 text-rose-600 rounded-full text-[10px] font-bold">{{ c.unrepliedCount }} belum dibalas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tab: Device -->
            <section v-if="activeTab === 'device'" class="space-y-6">
                <div class="grid lg:grid-cols-[1fr,360px] gap-6">
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-700 mb-4">Device Control</h3>
                        <div class="flex flex-wrap gap-2 mb-6">
                            <button @click="() => refreshAll(false)" :disabled="loading" class="px-4 py-2 bg-blue-50 text-blue-700 border border-blue-200 rounded-xl text-xs font-bold hover:bg-blue-100 transition disabled:opacity-50">{{ loading ? '↻ Memuat...' : 'Refresh' }}</button>
                            <button @click="deviceAction('restart', 'Restart')" :disabled="loading" class="px-4 py-2 bg-amber-50 text-amber-700 border border-amber-200 rounded-xl text-xs font-bold hover:bg-amber-100 transition disabled:opacity-50">Restart</button>
                            <button @click="deviceAction('reconnect', 'Reconnect')" :disabled="loading" class="px-4 py-2 bg-emerald-50 text-emerald-700 border border-emerald-200 rounded-xl text-xs font-bold hover:bg-emerald-100 transition disabled:opacity-50">Reconnect</button>
                            <button @click="deviceAction('disconnect', 'Disconnect')" :disabled="loading" class="px-4 py-2 bg-rose-50 text-rose-700 border border-rose-200 rounded-xl text-xs font-bold hover:bg-rose-100 transition disabled:opacity-50">Disconnect</button>
                            <button @click="deviceAction('history/clear', 'Clear History')" :disabled="loading" class="px-4 py-2 bg-slate-50 text-slate-600 border border-slate-200 rounded-xl text-xs font-bold hover:bg-slate-100 transition disabled:opacity-50">Clear History</button>
                            <button @click="confirmDeleteInbox" :disabled="loading" class="px-4 py-2 bg-rose-600 text-white border border-rose-700 rounded-xl text-xs font-bold hover:bg-rose-700 transition disabled:opacity-50">Delete Inbox</button>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-slate-400 uppercase mb-2">Runtime Health Payload</p>
                            <pre class="bg-slate-900 text-emerald-300 rounded-xl p-4 text-xs overflow-auto max-h-64">{{ JSON.stringify(runtimeHealth, null, 2) }}</pre>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                        <h3 class="text-sm font-bold text-slate-700 mb-4">QR Pairing</h3>
                            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4 min-h-[280px] flex items-center justify-center">
                                <img v-if="qrDataUrl && !isConnected" :src="qrDataUrl" alt="QR Code" class="w-full max-w-[240px] rounded-lg bg-white p-3 shadow-sm" />
                                <div v-else class="text-center">
                                    <p class="text-sm text-slate-400 mb-3 font-medium">{{ isConnected ? 'Device sudah terhubung 😊' : 'Klik untuk memunculkan QR secara live.' }}</p>
                                    <button v-if="!isConnected" @click="startQrPoll" :disabled="generatingQr" class="px-5 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-bold hover:bg-slate-700 hover:shadow-lg hover:-translate-y-0.5 transition-all disabled:opacity-50 disabled:hover:translate-y-0 disabled:hover:shadow-none">
                                        {{ generatingQr ? 'Menyiapkan QR...' : 'Generate QR Baru' }}
                                    </button>
                                </div>
                            </div>
                    </div>
                </div>
            </section>

            <!-- Tab: Send -->
            <section v-if="activeTab === 'send'" class="max-w-xl">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-700 mb-4">Kirim Pesan Langsung</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Nomor Tujuan</label>
                            <input v-model="sendTo" type="text" placeholder="628..." class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition" />
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Pesan</label>
                            <textarea v-model="sendText" rows="4" placeholder="Isi pesan..." class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition resize-none"></textarea>
                        </div>
                        <button @click="sendMessage" :disabled="sendState === 'sending'" class="w-full py-3 bg-slate-800 text-white rounded-xl text-sm font-bold hover:bg-slate-700 transition disabled:opacity-50">
                            {{ sendState === 'sending' ? 'Mengirim...' : sendState === 'sent' ? '✓ Terkirim' : 'Kirim Pesan' }}
                        </button>
                    </div>
                </div>
            </section>

            <!-- Tab: Broadcast -->
            <section v-if="activeTab === 'broadcast'" class="max-w-xl">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-700 mb-1">Broadcast Pesan</h3>
                    <p class="text-xs text-slate-400 mb-4">Kirim pesan yang sama ke beberapa nomor sekaligus (maks {{ config.broadcastLimit }}).</p>
                    <div class="space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Daftar Nomor (satu per baris)</label>
                            <textarea v-model="broadcastRecipients" rows="4" placeholder="628111222333&#10;628444555666&#10;628777888999" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition resize-none"></textarea>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Pesan Broadcast</label>
                            <textarea v-model="broadcastText" rows="4" placeholder="Isi pesan broadcast..." class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition resize-none"></textarea>
                        </div>
                        <button @click="sendBroadcast" :disabled="broadcastState === 'sending'" class="w-full py-3 bg-violet-600 text-white rounded-xl text-sm font-bold hover:bg-violet-500 transition disabled:opacity-50">
                            {{ broadcastState === 'sending' ? 'Mengirim...' : 'Kirim Broadcast' }}
                        </button>
                        <div v-if="broadcastResult" class="mt-4 bg-slate-50 rounded-xl border border-slate-200 p-4">
                            <pre class="text-xs text-slate-600 overflow-auto">{{ JSON.stringify(broadcastResult, null, 2) }}</pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Tab: Logs -->
            <section v-if="activeTab === 'logs'">
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-700">Log Pesan Terkirim (Legacy)</h3>
                        <button @click="refreshLogs" class="text-xs text-slate-400 hover:text-slate-600">↻ Refresh</button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                <tr>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Pengirim</th>
                                    <th class="px-6 py-3">Penerima</th>
                                    <th class="px-6 py-3">Pesan</th>
                                    <th class="px-6 py-3 text-right">Waktu</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                              :class="log.status === 'sent' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                                            {{ log.status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-slate-600">{{ log.sender || '-' }}</td>
                                    <td class="px-6 py-3 text-sm font-mono text-slate-700">{{ log.receiver }}</td>
                                    <td class="px-6 py-3 text-sm text-slate-500 max-w-xs truncate">{{ log.message }}</td>
                                    <td class="px-6 py-3 text-xs text-slate-400 text-right whitespace-nowrap">{{ log.sentAt }}</td>
                                </tr>
                                <tr v-if="logs.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-400">Belum ada log pesan.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
            </div>
        </div>
    </LawangsewuLayout>
</template>

<style scoped>
.font-sans {
    font-family: 'Inter', system-ui, -apple-system, sans-serif;
}
</style>
