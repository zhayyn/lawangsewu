<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    appMeta:   { type: Object, default: () => ({}) },
    navGroups: { type: Array,  default: () => [] },
    authUser:  { type: Object, default: () => ({}) },
    config:    { type: Object, default: () => ({}) },
    stats:     { type: Object, default: () => ({ total: 0, sent: 0, failed: 0, today: 0, lastSent: 'Belum ada' }) },
    messageStats: { type: Object, default: () => ({ totalMessages: 0, inbound: 0, outbound: 0, unreplied: 0, todayInbound: 0, todayOutbound: 0, conversations: 0 }) },
    convoStats:   { type: Object, default: () => ({ total: 0, open: 0, pending: 0, closed: 0, pendingHandovers: 0 }) },
});

// ─── State ────────────────────────────────────────────
const isLoading      = ref(false);
const isBusy        = ref(false);
const autoRefresh   = ref(true);
const pollRef       = ref(null);

const runtimeHealth     = ref({});
const qrDataUrl         = ref('');
const historyItems      = ref([]);
const requestLog        = ref([]);
const inboxSyncText     = ref('Belum disinkronkan.');
const runtimeInboxSupported = ref(true);

const latestMsgStats    = ref({ ...props.messageStats });
const latestConvoStats  = ref({ ...props.convoStats });

// Inbox
const conversations         = ref([]);
const activeConvoId         = ref('');
const conversationMessages  = ref([]);
const activeConvo           = computed(() => conversations.value.find(c => c.conversationId === activeConvoId.value) || null);
const threadEl              = ref(null);

// Reply / Send
const replyText  = ref('');
const replyState = ref('idle');
const sendTo     = ref('');
const sendText   = ref('');
const sendState  = ref('idle');

// Handover
const handoverReason  = ref('');
const handoverLoading = ref(false);
const showHandoverModal = ref(false);

// Status
const statusText = ref('Memeriksa koneksi...');
const statusTone = ref('warn');

const isAdmin    = computed(() => Boolean(props.authUser?.isAdmin));
const isConnected = computed(() => Boolean(runtimeHealth.value?.connected || runtimeHealth.value?.status === 'connected'));
const hasRealtime = computed(() => typeof window !== 'undefined' && Boolean(window.Echo));
const myId = computed(() => props.authUser?.id);

// Ownership
const iMineConvo = computed(() => activeConvo.value?.owner?.id === myId.value);
const isUnclaimedConvo = computed(() => !activeConvo.value?.owner);
const canReply = computed(() => iMineConvo.value || isUnclaimedConvo.value || isAdmin.value);
const hasPendingHandover = computed(() => Boolean(activeConvo.value?.pendingHandover));
const iRequestedHandover = computed(() =>
    activeConvo.value?.pendingHandover?.requestor?.id === myId.value
);

const statusClass = computed(() => ({
    ok:     'bg-emerald-500/20 border-emerald-500/40 text-emerald-300',
    warn:   'bg-amber-500/20 border-amber-500/40 text-amber-300',
    danger: 'bg-rose-500/20 border-rose-500/40 text-rose-300',
}[statusTone.value] || 'bg-slate-500/20 border-slate-500/40 text-slate-200'));

// ─── API Helper ───────────────────────────────────────
const callApi = async (action, options = {}) => {
    const method = options.method || 'get';
    const url = route('lawangsewu.wacaraka.api', { action });
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

    const fetchOpts = { method: method.toUpperCase(), headers };
    if (options.data) {
        headers['Content-Type'] = 'application/json';
        fetchOpts.body = JSON.stringify(options.data);
    }

    const query = options.params ? '?' + new URLSearchParams(options.params).toString() : '';
    const res = await fetch(url + query, fetchOpts);
    const json = await res.json();

    if (!res.ok) throw { status: res.status, error: json?.error || 'Terjadi kesalahan.' };
    return json;
};

const appendLog = (title, payload = null) => {
    const stamp = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    requestLog.value.unshift({ stamp, title, payload });
    if (requestLog.value.length > 30) requestLog.value = requestLog.value.slice(0, 30);
};

// ─── Refresh Methods ──────────────────────────────────
const refreshHealth = async () => {
    try {
        const data = await callApi('health');
        runtimeHealth.value = data || {};
        if (isConnected.value) { statusText.value = 'Device terhubung. Inbox aktif.'; statusTone.value = 'ok'; }
        else if (runtimeHealth.value?.hasQr) { statusText.value = 'Device belum login. Pindai QR untuk terhubung.'; statusTone.value = 'warn'; }
        else { statusText.value = 'Runtime tidak merespons.'; statusTone.value = 'danger'; }
    } catch { statusText.value = 'Runtime tidak terjangkau.'; statusTone.value = 'danger'; }
};

const refreshQr = async () => {
    if (isConnected.value) { qrDataUrl.value = ''; return; }
    try { const d = await callApi('qr'); qrDataUrl.value = d?.qrDataUrl || ''; } catch { /* silent */ }
};

const refreshStats = async () => {
    try {
        latestMsgStats.value  = await callApi('message-stats');
        latestConvoStats.value = await callApi('convo-stats');
    } catch { /* silent */ }
};

const refreshHistory = async () => {
    if (!isAdmin.value) { historyItems.value = []; return; }
    try {
        const d = await callApi('history');
        historyItems.value = Array.isArray(d?.items) ? d.items : (Array.isArray(d) ? d : []);
    } catch { /* silent */ }
};

const refreshInboxList = async (preserveActive = true) => {
    try {
        const data = await callApi('inbox');
        conversations.value = data?.conversations || [];
        if (!preserveActive || !activeConvoId.value || !conversations.value.some(c => c.conversationId === activeConvoId.value)) {
            activeConvoId.value = conversations.value[0]?.conversationId || '';
        }
    } catch { /* silent */ }
};

const refreshConvoMessages = async (convoId = activeConvoId.value) => {
    const convo = conversations.value.find(c => c.conversationId === convoId);
    if (!convoId || !convo) { conversationMessages.value = []; return; }
    try {
        const data = await callApi('conversation', { params: { remote_number: convo.remoteNumber } });
        conversationMessages.value = data?.messages || [];
        // Mark as read
        await callApi('mark-read', { method: 'post', data: { conversation_id: convoId } });
        // Scroll to bottom
        await nextTick();
        if (threadEl.value) threadEl.value.scrollTop = threadEl.value.scrollHeight;
    } catch { /* silent */ }
};

const pullInbox = async () => {
    try {
        const d = await callApi('pull-inbox');
        runtimeInboxSupported.value = d?.supported !== false;
        inboxSyncText.value = runtimeInboxSupported.value
            ? `Sinkron: ${d?.stored ?? 0} pesan baru.`
            : 'Runtime belum mendukung GET /messages.';
    } catch { /* silent */ }
};

const refreshAll = async () => {
    if (isLoading.value) return;
    isLoading.value = true;
    try {
        await Promise.all([refreshHealth(), refreshStats(), refreshInboxList(), refreshHistory()]);
        await Promise.all([refreshQr(), refreshConvoMessages(), pullInbox()]);
    } catch (err) {
        appendLog('Error sinkronisasi', { message: err?.error || err?.message || 'Unknown' });
    } finally {
        isLoading.value = false;
    }
};

// ─── Actions ──────────────────────────────────────────
const runAction = async (action, title) => {
    if (isBusy.value) return;
    isBusy.value = true;
    try {
        const d = await callApi(action, { method: 'post' });
        appendLog(`${title} berhasil`, d);
        await refreshAll();
    } catch (err) {
        appendLog(`${title} gagal`, { error: err?.error });
    } finally {
        isBusy.value = false;
    }
};

const selectConversation = async (convoId) => {
    activeConvoId.value = convoId;
    await refreshConvoMessages(convoId);
};

const replyToConversation = async () => {
    const text = replyText.value.trim();
    if (!activeConvoId.value || !text) { replyState.value = 'error'; return; }
    replyState.value = 'sending';
    try {
        await callApi('reply', { method: 'post', data: { conversation_id: activeConvoId.value, text } });
        replyState.value = 'sent';
        replyText.value = '';
        appendLog('Balasan terkirim', { convoId: activeConvoId.value });
        await Promise.all([refreshInboxList(), refreshConvoMessages(), refreshStats()]);
        setTimeout(() => replyState.value = 'idle', 2000);
    } catch (err) {
        replyState.value = 'error';
        appendLog('Balasan gagal', { error: err?.error });
        setTimeout(() => replyState.value = 'idle', 3000);
    }
};

const sendDirectMessage = async () => {
    const to = sendTo.value.trim(), text = sendText.value.trim();
    if (!to || !text) { sendState.value = 'error'; return; }
    sendState.value = 'sending';
    try {
        await callApi('send-text', { method: 'post', data: { to, text } });
        sendState.value = 'sent';
        sendText.value = '';
        await Promise.all([refreshInboxList(), refreshStats()]);
        setTimeout(() => sendState.value = 'idle', 2000);
    } catch (err) {
        sendState.value = 'error';
        setTimeout(() => sendState.value = 'idle', 3000);
    }
};

// ─── Handover ─────────────────────────────────────────
const requestHandover = async () => {
    if (!activeConvoId.value) return;
    handoverLoading.value = true;
    try {
        await callApi('request-handover', {
            method: 'post',
            data: { conversation_id: activeConvoId.value, reason: handoverReason.value.trim() || null },
        });
        handoverReason.value = '';
        showHandoverModal.value = false;
        appendLog('Permintaan handover dikirim');
        await refreshInboxList();
    } catch (err) {
        appendLog('Permintaan handover gagal', { error: err?.error });
    } finally {
        handoverLoading.value = false;
    }
};

const respondHandover = async (handoverId, approve) => {
    const action = approve ? 'approve-handover' : 'reject-handover';
    try {
        await callApi(action, { method: 'post', data: { handover_id: handoverId } });
        appendLog(approve ? 'Handover disetujui' : 'Handover ditolak');
        await Promise.all([refreshInboxList(), refreshConvoMessages()]);
    } catch (err) {
        appendLog('Respons handover gagal', { error: err?.error });
    }
};

const forceHandover = async (convoId) => {
    try {
        await callApi('force-handover', { method: 'post', data: { conversation_id: convoId } });
        appendLog('Force takeover berhasil (admin)');
        await Promise.all([refreshInboxList(), refreshConvoMessages()]);
    } catch (err) {
        appendLog('Force takeover gagal', { error: err?.error });
    }
};

const closeConvo = async () => {
    if (!activeConvoId.value) return;
    try {
        await callApi('close', { method: 'post', data: { conversation_id: activeConvoId.value } });
        await refreshInboxList();
    } catch { /* silent */ }
};

// ─── Realtime ─────────────────────────────────────────
const connectRealtime = () => {
    const echo = window.Echo;
    if (!echo) { appendLog('Realtime: Reverb tidak aktif. Gunakan polling.'); return; }
    echo.private('lawangsewu.wacaraka.inbox')
        .listen('.wa-caraka.message.received', async (event) => {
            appendLog('Pesan masuk (Reverb)', event?.message?.remoteNumber && { from: event.message.remoteNumber });
            await refreshInboxList();
            if (!activeConvoId.value || event?.message?.conversationId === activeConvoId.value) {
                await refreshConvoMessages();
            }
            await refreshStats();
        });
};

const setAutoRefresh = (val) => {
    autoRefresh.value = val;
    if (pollRef.value) { clearInterval(pollRef.value); pollRef.value = null; }
    if (val) pollRef.value = setInterval(refreshAll, 10000);
};

// Watch active convo — refresh messages on change
watch(activeConvoId, (id) => { if (id) refreshConvoMessages(id); });

onMounted(async () => {
    await refreshAll();
    setAutoRefresh(true);
    connectRealtime();
});

onUnmounted(() => {
    if (pollRef.value) clearInterval(pollRef.value);
    window.Echo?.leave('lawangsewu.wacaraka.inbox');
});
</script>

<template>
    <Head title="WA Caraka — Inbox Operator" />

    <LawangsewuLayout current-route="wacaraka" :nav-groups="navGroups" :app-meta="appMeta">

        <!-- ░░ Header Hero ░░ -->
        <section class="relative overflow-hidden rounded-[2rem] border border-[var(--accent-border)] bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.15),transparent_36%),linear-gradient(145deg,rgba(5,10,23,0.96),rgba(15,23,42,0.95))] p-6 text-white shadow-[0_30px_80px_rgba(2,6,23,0.45)] lg:p-8">
            <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-sky-500/10 blur-3xl pointer-events-none" />
            <div class="absolute -bottom-12 left-1/3 h-44 w-44 rounded-full bg-cyan-400/8 blur-3xl pointer-events-none" />

            <div class="relative z-10 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.25em] text-sky-300/90">WA Caraka • Inbox Operator</p>
                    <h1 class="mt-1 text-3xl font-black tracking-tight sm:text-4xl">Pusat Pesan WhatsApp</h1>
                    <p class="mt-2 max-w-2xl text-sm text-slate-300">Dashboard terpadu untuk membaca pesan masuk, membalas percakapan, dan mengelola kepemilikan sesi obrolan antar petugas.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-bold transition" :class="statusClass">
                        <span class="h-2 w-2 rounded-full bg-current animate-pulse"></span>
                        {{ statusText }}
                    </span>
                    <button @click="refreshAll" :disabled="isLoading" class="rounded-full border border-sky-400/40 bg-sky-400/10 px-3 py-1.5 text-xs font-bold text-sky-200 hover:bg-sky-400/20 transition disabled:opacity-40">
                        {{ isLoading ? 'Memuat...' : '↻ Refresh' }}
                    </button>
                </div>
            </div>

            <!-- Mini stats -->
            <div class="relative z-10 mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-7">
                <article v-for="card in [
                    { label: 'Percakapan', value: latestConvoStats.total, color: 'text-sky-300' },
                    { label: 'Aktif', value: latestConvoStats.open, color: 'text-emerald-300' },
                    { label: 'Pending', value: latestConvoStats.pending, color: 'text-amber-300' },
                    { label: 'Selesai', value: latestConvoStats.closed, color: 'text-slate-300' },
                    { label: 'Pesan Masuk', value: latestMsgStats.todayInbound, color: 'text-cyan-300' },
                    { label: 'Belum Dibalas', value: latestMsgStats.unreplied, color: latestMsgStats.unreplied > 0 ? 'text-rose-300' : 'text-emerald-300' },
                    { label: 'Pending Handover', value: latestConvoStats.pendingHandovers, color: latestConvoStats.pendingHandovers > 0 ? 'text-orange-300' : 'text-slate-400' },
                ]" :key="card.label" class="rounded-2xl border border-white/10 bg-white/5 p-3 backdrop-blur">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-slate-400">{{ card.label }}</p>
                    <p class="mt-1.5 text-xl font-black" :class="card.color">{{ card.value ?? 0 }}</p>
                </article>
            </div>
        </section>

        <!-- ░░ Main: Inbox + Thread ░░ -->
        <section class="mt-6 grid gap-6 xl:grid-cols-[clamp(280px,28%,360px),minmax(0,1fr)]">

            <!-- Sidebar: Conversation List -->
            <aside class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] shadow-[var(--shadow)]">
                <div class="flex items-center justify-between gap-2 border-b border-[var(--border)] px-5 py-4">
                    <h2 class="font-black text-[var(--text-1)]">Inbox</h2>
                    <div class="flex items-center gap-2">
                        <button @click="pullInbox().then(refreshInboxList)" :disabled="isLoading"
                                class="rounded-xl border border-[var(--border)] px-2.5 py-1 text-[10px] font-bold text-[var(--text-2)] hover:border-sky-400/50 hover:text-sky-400 transition">
                            Pull
                        </button>
                        <span class="rounded-full bg-[var(--surface-2)] px-2.5 py-0.5 text-[10px] font-bold text-[var(--text-2)]">{{ conversations.length }}</span>
                    </div>
                </div>

                <p class="px-5 py-2 text-[10px] text-[var(--text-2)]">{{ inboxSyncText }}</p>

                <div class="max-h-[calc(100vh-18rem)] overflow-y-auto divide-y divide-[var(--border)]">
                    <button v-for="c in conversations" :key="c.conversationId"
                            @click="selectConversation(c.conversationId)"
                            class="w-full px-4 py-3.5 text-left transition group"
                            :class="activeConvoId === c.conversationId ? 'bg-sky-500/10 border-l-2 border-sky-400' : 'hover:bg-[var(--surface-2)] border-l-2 border-transparent'">

                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-bold text-[var(--text-1)] truncate">
                                {{ c.remoteName || c.remoteNumber }}
                            </p>
                            <!-- Unread badge -->
                            <span v-if="c.unreadCount > 0" class="flex-shrink-0 rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-black text-white">
                                {{ c.unreadCount }}
                            </span>
                        </div>

                        <p v-if="c.remoteName" class="text-[10px] text-[var(--text-2)] mt-0.5 font-mono">{{ c.remoteNumber }}</p>

                        <!-- Owner badge -->
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                  :class="{
                                      'bg-emerald-500/15 text-emerald-600': c.status === 'open',
                                      'bg-amber-500/15 text-amber-600': c.status === 'pending',
                                      'bg-slate-500/15 text-slate-500': c.status === 'closed',
                                  }">
                                {{ c.status }}
                            </span>
                            <span v-if="c.owner" class="rounded-full bg-blue-500/12 px-2 py-0.5 text-[10px] font-semibold text-blue-600">
                                {{ c.owner.alias || c.owner.name }}
                            </span>
                            <!-- Handover pending indicator -->
                            <span v-if="c.pendingHandover" class="rounded-full bg-orange-500/15 px-2 py-0.5 text-[10px] font-bold text-orange-600">
                                handover ⏳
                            </span>
                        </div>

                        <p class="mt-1.5 text-[10px] text-[var(--text-2)]">{{ c.lastActivityAt || '—' }}</p>
                    </button>

                    <div v-if="conversations.length === 0" class="px-5 py-10 text-center">
                        <p class="text-sm text-[var(--text-2)]">Belum ada percakapan.</p>
                        <p class="mt-1 text-xs text-[var(--text-2)]">Pesan akan muncul saat runtime mengirim webhook atau pull inbox berhasil.</p>
                    </div>
                </div>
            </aside>

            <!-- Main: Thread + Reply -->
            <div class="flex flex-col gap-4">

                <!-- Thread Header -->
                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[var(--shadow)]">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 class="text-xl font-black text-[var(--text-1)]">
                                {{ activeConvo?.remoteName || activeConvo?.remoteNumber || 'Pilih Percakapan' }}
                            </h2>
                            <p v-if="activeConvo?.remoteNumber && activeConvo?.remoteName" class="text-xs font-mono text-[var(--text-2)]">{{ activeConvo.remoteNumber }}</p>
                            <div v-if="activeConvo" class="mt-2 flex flex-wrap items-center gap-2">
                                <!-- Status -->
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold"
                                      :class="{
                                          'bg-emerald-100 text-emerald-700': activeConvo.status === 'open',
                                          'bg-amber-100 text-amber-700': activeConvo.status === 'pending',
                                          'bg-slate-100 text-slate-500': activeConvo.status === 'closed',
                                      }">
                                    {{ activeConvo.status }}
                                </span>
                                <!-- Owner -->
                                <span v-if="activeConvo.owner" class="rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700">
                                    Ditangani: {{ activeConvo.owner.alias || activeConvo.owner.name }}
                                    <span v-if="iMineConvo" class="ml-1 text-blue-400">(kamu)</span>
                                </span>
                                <span v-else class="rounded-full border border-dashed border-slate-300 px-2.5 py-1 text-xs text-slate-500">Belum ada petugas</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="activeConvo" class="flex flex-wrap gap-2">
                            <button @click="refreshConvoMessages()" class="text-xs text-[var(--text-2)] border border-[var(--border)] rounded-xl px-3 py-1.5 hover:border-sky-400/50 transition">↻ Muat ulang</button>

                            <!-- Close (owner or admin) -->
                            <button v-if="(iMineConvo || isAdmin) && activeConvo.status !== 'closed'"
                                    @click="closeConvo"
                                    class="text-xs border border-slate-200 rounded-xl px-3 py-1.5 text-slate-600 hover:bg-slate-100 transition">
                                ✓ Selesaikan
                            </button>

                            <!-- Request Handover (others) -->
                            <button v-if="!iMineConvo && !isUnclaimedConvo && !iRequestedHandover"
                                    @click="showHandoverModal = true"
                                    class="text-xs border border-orange-200 bg-orange-50 rounded-xl px-3 py-1.5 text-orange-700 hover:bg-orange-100 transition">
                                ⇄ Minta Alih Chat
                            </button>
                            <span v-if="iRequestedHandover" class="text-xs px-3 py-1.5 rounded-xl bg-orange-50 text-orange-600 border border-orange-200">
                                ⏳ Menunggu persetujuan...
                            </span>

                            <!-- Force takeover (admin) -->
                            <button v-if="isAdmin && !iMineConvo && activeConvo.owner"
                                    @click="forceHandover(activeConvo.conversationId)"
                                    class="text-xs border border-rose-200 bg-rose-50 rounded-xl px-3 py-1.5 text-rose-700 hover:bg-rose-100 transition">
                                ⚡ Ambil Alih (Admin)
                            </button>
                        </div>
                    </div>

                    <!-- Incoming Handover Request (owner must respond) -->
                    <div v-if="iMineConvo && hasPendingHandover && activeConvo.pendingHandover"
                         class="mt-4 rounded-2xl border border-orange-200 bg-orange-50 p-4">
                        <p class="text-sm font-semibold text-orange-800">
                            <strong>{{ activeConvo.pendingHandover.requestor?.name }}</strong> meminta mengambil alih percakapan ini.
                        </p>
                        <div class="mt-3 flex gap-2">
                            <button @click="respondHandover(activeConvo.pendingHandover.id, true)"
                                    class="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 transition">
                                ✓ Setujui
                            </button>
                            <button @click="respondHandover(activeConvo.pendingHandover.id, false)"
                                    class="rounded-xl border border-rose-300 bg-white px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition">
                                ✕ Tolak
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Thread Messages -->
                <div ref="threadEl"
                     class="flex-1 min-h-[320px] max-h-[480px] overflow-y-auto rounded-[2rem] border border-[var(--border)] bg-[var(--surface-2)] p-5 shadow-[var(--shadow)] scroll-smooth">

                    <div v-if="!activeConvo" class="grid min-h-[280px] place-items-center text-center text-sm text-[var(--text-2)]">
                        <div>
                            <p class="text-4xl mb-3">💬</p>
                            <p>Pilih percakapan di sebelah kiri untuk memulai.</p>
                        </div>
                    </div>

                    <div v-else-if="conversationMessages.length === 0" class="grid min-h-[280px] place-items-center text-center text-sm text-[var(--text-2)]">
                        <div>
                            <p class="text-4xl mb-3">📭</p>
                            <p>Belum ada pesan dalam thread ini.</p>
                        </div>
                    </div>

                    <div v-else class="space-y-3">
                        <div v-for="msg in conversationMessages" :key="msg.id"
                             class="flex"
                             :class="msg.direction === 'outbound' ? 'justify-end' : 'justify-start'">

                            <article class="max-w-[78%] rounded-2xl px-4 py-3 shadow-sm"
                                     :class="msg.direction === 'outbound'
                                         ? 'bg-gradient-to-br from-sky-500 to-sky-600 text-white rounded-tr-sm'
                                         : 'bg-white border border-slate-200 text-slate-900 rounded-tl-sm'">

                                <div class="flex items-center justify-between gap-4 text-[10px] font-semibold opacity-75 mb-1.5">
                                    <span>{{ msg.direction === 'outbound' ? (msg.operator || 'Operator') : '📱 WA Masuk' }}</span>
                                    <span class="whitespace-nowrap">{{ msg.sentAt }}</span>
                                </div>

                                <p class="text-sm leading-relaxed whitespace-pre-wrap">{{ msg.text || '[tanpa teks]' }}</p>

                                <div class="mt-1.5 flex items-center justify-between gap-2 text-[10px] opacity-60">
                                    <span>{{ msg.type }}</span>
                                    <span :class="msg.status === 'sent' ? 'text-emerald-200' : msg.status === 'failed' ? 'text-rose-300' : ''">
                                        {{ msg.status }}
                                        <span v-if="msg.repliedAt" class="ml-1">· dibalas {{ msg.repliedAt }}</span>
                                    </span>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <!-- Reply Box -->
                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-5 shadow-[var(--shadow)]">
                    <!-- Ownership warning -->
                    <div v-if="activeConvo && !canReply" class="mb-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        <strong>Percakapan ini sedang ditangani oleh {{ activeConvo.owner?.alias || activeConvo.owner?.name }}.</strong>
                        Kamu perlu mengajukan permintaan alih chat terlebih dahulu.
                    </div>

                    <div class="flex gap-3">
                        <textarea v-model="replyText"
                                  rows="3"
                                  :disabled="!activeConvo || !canReply"
                                  :placeholder="!activeConvo ? 'Pilih percakapan' : !canReply ? 'Tidak diizinkan membalas' : `Balas ke ${activeConvo?.remoteNumber || ''}...`"
                                  class="flex-1 resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-sm text-[var(--text-1)] outline-none transition placeholder:text-[var(--text-2)] focus:border-sky-400/60 disabled:opacity-50 disabled:cursor-not-allowed"
                                  @keydown.ctrl.enter="replyToConversation" />

                        <div class="flex flex-col gap-2">
                            <button @click="replyToConversation"
                                    :disabled="replyState === 'sending' || !activeConvo || !canReply"
                                    class="flex-1 min-w-[100px] rounded-2xl bg-gradient-to-br from-sky-500 to-cyan-500 px-5 py-3 text-sm font-black text-white shadow-md hover:brightness-110 transition disabled:opacity-40 disabled:cursor-not-allowed">
                                {{ replyState === 'sending' ? '⏳' : replyState === 'sent' ? '✓ Terkirim' : replyState === 'error' ? '✕ Gagal' : '↑ Kirim' }}
                            </button>
                            <p class="text-center text-[9px] text-[var(--text-2)]">Ctrl+Enter</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ░░ Send Direct + Device (Admin/Operator) ░░ -->
        <section class="mt-6 grid gap-6 lg:grid-cols-2">
            <!-- Quick Send -->
            <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
                <h2 class="mb-4 text-lg font-black text-[var(--text-1)]">Kirim Pesan Langsung</h2>
                <div class="space-y-3">
                    <input v-model="sendTo" type="text" placeholder="Nomor tujuan: 628112345678"
                           class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-sm text-[var(--text-1)] outline-none focus:border-sky-400/60 transition placeholder:text-[var(--text-2)]" />
                    <textarea v-model="sendText" rows="3" placeholder="Isi pesan..."
                              class="w-full resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-sm text-[var(--text-1)] outline-none focus:border-sky-400/60 transition placeholder:text-[var(--text-2)]" />
                    <button @click="sendDirectMessage" :disabled="sendState === 'sending'"
                            class="w-full rounded-2xl bg-slate-800 py-3 text-sm font-black text-white hover:bg-slate-700 transition disabled:opacity-50">
                        {{ sendState === 'sending' ? 'Mengirim...' : sendState === 'sent' ? '✓ Terkirim' : 'Kirim Pesan' }}
                    </button>
                </div>
            </article>

            <!-- Device Status + QR -->
            <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-black text-[var(--text-1)]">Status Device</h2>
                    <div class="flex gap-2">
                        <button v-if="isAdmin" @click="runAction('restart', 'Restart')" :disabled="isBusy"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 transition disabled:opacity-40">Restart</button>
                        <button v-if="isAdmin" @click="runAction('reconnect', 'Reconnect')" :disabled="isBusy"
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition disabled:opacity-40">Reconnect</button>
                        <button v-if="isAdmin" @click="runAction('disconnect', 'Disconnect')" :disabled="isBusy"
                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition disabled:opacity-40">Disconnect</button>
                    </div>
                </div>
                <div class="grid min-h-[220px] place-items-center rounded-2xl border border-dashed border-[var(--border)] bg-[var(--surface-2)] p-4">
                    <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR WA Caraka" class="w-full max-w-[200px] rounded-xl bg-white p-2" />
                    <div v-else class="text-center">
                        <div class="mb-3 h-12 w-12 mx-auto rounded-2xl flex items-center justify-center text-2xl"
                             :class="isConnected ? 'bg-emerald-100' : 'bg-amber-100'">
                            {{ isConnected ? '✓' : '📲' }}
                        </div>
                        <p class="text-sm font-semibold" :class="isConnected ? 'text-emerald-600' : 'text-amber-600'">
                            {{ isConnected ? 'Device Terhubung' : 'Belum Terhubung' }}
                        </p>
                        <p v-if="!isConnected" class="mt-2 text-xs text-[var(--text-2)]">QR akan tersedia setelah runtime aktif.</p>
                    </div>
                </div>
            </article>
        </section>

        <!-- ░░ Activity Log ░░ -->
        <section class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-black text-[var(--text-1)]">Activity Log</h2>
                <button @click="requestLog = []" class="text-xs text-[var(--text-2)] hover:text-rose-500 transition">Bersihkan</button>
            </div>
            <div class="max-h-60 overflow-y-auto space-y-2">
                <div v-for="(entry, idx) in requestLog" :key="idx"
                     class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-3">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold text-[var(--text-1)]">{{ entry.title }}</p>
                        <span class="text-[10px] text-[var(--text-2)]">{{ entry.stamp }}</span>
                    </div>
                    <pre v-if="entry.payload" class="mt-1.5 text-[10px] text-[var(--text-2)] overflow-auto">{{ JSON.stringify(entry.payload, null, 2) }}</pre>
                </div>
                <p v-if="requestLog.length === 0" class="text-sm text-[var(--text-2)] text-center py-4">Belum ada aktivitas.</p>
            </div>
        </section>

        <!-- ░░ Admin: History Monitor ░░ -->
        <section v-if="isAdmin" class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-black text-[var(--text-1)]">History Monitor <span class="text-sm font-normal text-[var(--text-2)]">(admin only)</span></h2>
                <button @click="refreshHistory" class="text-xs text-[var(--text-2)] border border-[var(--border)] rounded-xl px-3 py-1.5 hover:border-sky-400/50 transition">Muat ulang</button>
            </div>
            <div class="overflow-hidden rounded-2xl border border-[var(--border)]">
                <div class="max-h-72 overflow-y-auto divide-y divide-[var(--border)]">
                    <div v-for="(item, idx) in historyItems" :key="idx" class="grid gap-2 px-4 py-3 md:grid-cols-[160px,1fr]">
                        <p class="text-xs font-semibold text-[var(--text-1)]">{{ item?.timestamp || item?.time || '-' }}</p>
                        <pre class="text-xs text-[var(--text-2)] overflow-auto leading-5">{{ JSON.stringify(item, null, 2) }}</pre>
                    </div>
                    <div v-if="historyItems.length === 0" class="px-5 py-8 text-center text-sm text-[var(--text-2)]">Belum ada riwayat dari runtime.</div>
                </div>
            </div>
        </section>

        <!-- ░░ Handover Request Modal ░░ -->
        <Teleport to="body">
            <div v-if="showHandoverModal"
                 class="fixed inset-0 z-50 grid place-items-center bg-black/60 backdrop-blur-sm"
                 @click.self="showHandoverModal = false">
                <div class="w-full max-w-md rounded-3xl border border-[var(--border)] bg-white p-6 shadow-2xl">
                    <h3 class="text-lg font-black text-slate-800">Minta Alih Chat</h3>
                    <p class="mt-1 text-sm text-slate-500">
                        Permintaan akan dikirim ke <strong>{{ activeConvo?.owner?.alias || activeConvo?.owner?.name }}</strong>.
                        Kamu baru bisa membalas setelah disetujui.
                    </p>
                    <textarea v-model="handoverReason" rows="3" placeholder="Alasan (opsional)..."
                              class="mt-4 w-full resize-none rounded-2xl border border-slate-200 px-4 py-3 text-sm outline-none focus:border-sky-400 transition" />
                    <div class="mt-4 flex gap-3">
                        <button @click="requestHandover" :disabled="handoverLoading"
                                class="flex-1 rounded-2xl bg-orange-500 py-3 text-sm font-black text-white hover:bg-orange-600 transition disabled:opacity-50">
                            {{ handoverLoading ? 'Mengirim...' : '⇄ Kirim Permintaan' }}
                        </button>
                        <button @click="showHandoverModal = false"
                                class="rounded-2xl border border-slate-200 px-5 py-3 text-sm font-bold text-slate-600 hover:bg-slate-50 transition">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

    </LawangsewuLayout>
</template>
