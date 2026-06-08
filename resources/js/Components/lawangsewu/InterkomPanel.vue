<script setup>
/**
 * InterkomPanel — Panel chat internal mini yang dapat di-embed di sidebar kanan.
 * ─ Self-contained: polling sendiri, tidak bergantung pada Inertia reload.
 * ─ Ringan: poll 5s aktif / 10s background, hanya teks (tanpa attachment).
 * ─ Semua role: viewer, operator, useradmin, admin, superadmin.
 * ─ Performa: scroll hanya dijalankan kalau panel visible (mounted & open).
 */
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    visible: { type: Boolean, default: true },
});

const page   = usePage();
const user   = computed(() => page.props.auth?.user);
const userId = computed(() => user.value?.id);
const myAlias = computed(() => user.value?.alias || user.value?.name || 'Saya');

// ── State ──
const messages   = ref([]);
const draftText  = ref('');
const isSending  = ref(false);
const isLoading  = ref(false);
const unread     = ref(0);
const lastSeenId = ref(0);
const inputRef   = ref(null);
const scrollRef  = ref(null);

let pollTimer    = null;
let msgSeed      = 0;

// ── Scroll ke bawah ──
const scrollToBottom = (force = false) => {
    if (!props.visible && !force) return;
    nextTick(() => {
        if (scrollRef.value) {
            scrollRef.value.scrollTop = scrollRef.value.scrollHeight;
        }
    });
};

// ── Fetch messages via REST (ringan, tanpa full-page reload) ──
const fetchMessages = async () => {
    if (isLoading.value) return;
    try {
        isLoading.value = true;
        const res = await window.axios.get(route('lawangsewu.chat.messages'), {
            params: { limit: 40, type: 'global' },
        });
        const incoming = res.data?.messages ?? res.data?.data ?? [];
        const normalized = incoming.map(normalizeMsg);
        const wasAtBottom = !scrollRef.value
            || (scrollRef.value.scrollHeight - scrollRef.value.scrollTop <= scrollRef.value.clientHeight + 80);

        // Hitung unread jika panel sedang hidden
        if (!props.visible) {
            const newCount = normalized.filter(m => m.id > lastSeenId.value && m.userId !== userId.value).length;
            unread.value += newCount;
        }

        messages.value = normalized;

        if (wasAtBottom || !messages.value.length) {
            scrollToBottom();
        }
    } catch {
        // silent — jangan ganggu UX
    } finally {
        isLoading.value = false;
    }
};

const normalizeMsg = (m) => ({
    id       : m.id,
    userId   : m.user_id ?? m.userId,
    alias    : m.user?.alias || m.user?.name || 'Operator',
    avatar   : m.user?.avatar ?? null,
    content  : m.content ?? m.body ?? '',
    time     : new Date(m.created_at).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    isOwn    : (m.user_id ?? m.userId) === userId.value,
});

// ── Polling ──
const startPoll = () => {
    stopPoll();
    const ms = document.hidden ? 12_000 : (props.visible ? 5_000 : 10_000);
    pollTimer = setInterval(fetchMessages, ms);
};

const stopPoll = () => {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
};

const restartPoll = () => { stopPoll(); startPoll(); };

// ── Echo WebSocket (opsional — auto-fallback ke polling) ──
const connectEcho = () => {
    if (!window.Echo) return;
    window.Echo.channel('lawangsewu.chat.global')
        .listen('.chat.message.sent', (payload) => {
            if (!payload?.message) return;
            const msg = normalizeMsg({ ...payload.message, user_id: payload.message.user_id });
            const idx = messages.value.findIndex(m => m.id === msg.id);
            if (idx === -1) {
                messages.value.push(msg);
                if (!props.visible && msg.userId !== userId.value) unread.value++;
            } else {
                messages.value[idx] = msg;
            }
            scrollToBottom();
        });
};

// ── Kirim pesan ──
const sendMessage = async () => {
    const text = draftText.value.trim();
    if (!text || isSending.value) return;

    isSending.value = true;
    const optimisticId = `opt-${++msgSeed}`;
    const optimistic = {
        id      : optimisticId,
        userId  : userId.value,
        alias   : myAlias.value,
        avatar  : user.value?.avatar ?? null,
        content : text,
        time    : new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
        isOwn   : true,
    };
    messages.value.push(optimistic);
    draftText.value = '';
    scrollToBottom(true);

    try {
        await window.axios.post(route('lawangsewu.chat.store'), { content: text }, {
            headers: { Accept: 'application/json' },
        });
        // Sync setelah kirim supaya ID optimistic terganti ID real
        await fetchMessages();
    } catch {
        // Hapus optimistic kalau gagal
        messages.value = messages.value.filter(m => m.id !== optimisticId);
    } finally {
        isSending.value = false;
        inputRef.value?.focus();
    }
};

const handleEnter = (e) => {
    if (e.shiftKey) return;
    e.preventDefault();
    sendMessage();
};

// ── Visibility change handler ──
const onVisibilityChange = () => restartPoll();

// ── Saat panel dibuka: reset unread, scroll ke bawah, restart poll lebih cepat ──
watch(() => props.visible, (open) => {
    if (open) {
        unread.value = 0;
        if (messages.value.length) {
            lastSeenId.value = Math.max(...messages.value.map(m => typeof m.id === 'number' ? m.id : 0), 0);
        }
        scrollToBottom(true);
        restartPoll();
        nextTick(() => inputRef.value?.focus());
    } else {
        restartPoll(); // poll lebih lambat saat disembunyikan
    }
});

onMounted(() => {
    fetchMessages();
    startPoll();
    connectEcho();
    document.addEventListener('visibilitychange', onVisibilityChange);
});

onUnmounted(() => {
    stopPoll();
    document.removeEventListener('visibilitychange', onVisibilityChange);
    if (window.Echo) window.Echo.leave('lawangsewu.chat.global');
});

defineExpose({ unread });
</script>

<template>
    <!-- Panel Wrapper — full height, flex column -->
    <div class="interkom-panel flex flex-col h-full bg-[var(--surface-1)] overflow-hidden">

        <!-- ── Header ── -->
        <div class="interkom-header flex items-center gap-2 px-3 py-2.5 border-b border-[var(--border)] bg-[var(--surface-2)]/60 flex-shrink-0">
            <div class="h-7 w-7 rounded-lg bg-violet-500/15 flex items-center justify-center flex-shrink-0">
                <svg class="h-3.5 w-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-widest text-[var(--text-1)] leading-none">Interkom</p>
                <p class="text-[9px] text-[var(--text-3)] leading-none mt-0.5">Chat Internal Operator</p>
            </div>
            <div v-if="isLoading" class="h-1.5 w-1.5 rounded-full bg-sky-400 animate-pulse flex-shrink-0" title="Memuat..." />
        </div>

        <!-- ── Messages ── -->
        <div
            ref="scrollRef"
            class="interkom-messages flex-1 overflow-y-auto px-3 py-3 space-y-2.5 min-h-0"
        >
            <!-- Empty state -->
            <div v-if="!messages.length" class="h-full flex flex-col items-center justify-center opacity-30 gap-2 py-8">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                </svg>
                <p class="text-[10px] font-black uppercase tracking-[0.2em]">Belum ada pesan</p>
            </div>

            <!-- Message bubbles -->
            <div
                v-for="msg in messages"
                :key="msg.id"
                class="flex gap-2"
                :class="msg.isOwn ? 'flex-row-reverse' : 'flex-row'"
            >
                <!-- Avatar -->
                <div class="flex-shrink-0 mt-auto">
                    <img
                        v-if="msg.avatar"
                        :src="msg.avatar"
                        :alt="msg.alias"
                        class="h-6 w-6 rounded-full object-cover border border-[var(--border)]"
                    />
                    <div
                        v-else
                        class="h-6 w-6 rounded-full flex items-center justify-center text-[9px] font-black border border-[var(--border)]"
                        :class="msg.isOwn
                            ? 'bg-violet-500/20 text-violet-600 border-violet-200'
                            : 'bg-sky-500/15 text-sky-600 border-sky-200'"
                    >
                        {{ (msg.alias?.[0] || '?').toUpperCase() }}
                    </div>
                </div>

                <!-- Bubble -->
                <div class="min-w-0 max-w-[78%]" :class="msg.isOwn ? 'items-end' : 'items-start'" style="display:flex;flex-direction:column">
                    <p
                        v-if="!msg.isOwn"
                        class="text-[9px] font-bold text-[var(--text-3)] mb-0.5 px-1"
                    >{{ msg.alias }}</p>
                    <div
                        class="rounded-2xl px-3 py-2 text-xs leading-relaxed break-words"
                        :class="msg.isOwn
                            ? 'rounded-tr-sm bg-violet-600 text-white'
                            : 'rounded-tl-sm bg-[var(--surface-2)] text-[var(--text-1)] border border-[var(--border)]'"
                    >
                        {{ msg.content }}
                    </div>
                    <p class="text-[8px] text-[var(--text-3)] mt-0.5 px-1">{{ msg.time }}</p>
                </div>
            </div>
        </div>

        <!-- ── Composer ── -->
        <div class="interkom-composer flex-shrink-0 border-t border-[var(--border)] bg-[var(--surface-2)]/50 px-3 py-2.5">
            <form @submit.prevent="sendMessage" class="flex items-end gap-2">
                <textarea
                    ref="inputRef"
                    v-model="draftText"
                    class="flex-1 min-w-0 resize-none rounded-xl border border-[var(--border)] bg-[var(--surface-1)] px-3 py-2 text-xs text-[var(--text-1)] placeholder:text-[var(--text-3)] outline-none transition focus:border-violet-400/60 focus:ring-2 focus:ring-violet-500/15"
                    :class="{ 'opacity-60': isSending }"
                    placeholder="Tulis koordinasi…"
                    rows="2"
                    :disabled="isSending"
                    @keydown.enter.exact="handleEnter"
                />
                <button
                    type="submit"
                    :disabled="!draftText.trim() || isSending"
                    class="flex-shrink-0 flex h-9 w-9 items-center justify-center rounded-xl bg-violet-600 text-white transition hover:bg-violet-700 disabled:opacity-40 disabled:cursor-not-allowed"
                    title="Kirim (Enter)"
                >
                    <svg v-if="!isSending" class="h-4 w-4 rotate-45" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <svg v-else class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </button>
            </form>
            <p class="text-[9px] text-[var(--text-3)] mt-1.5 text-center">Enter kirim · Shift+Enter baris baru</p>
        </div>
    </div>
</template>

<style scoped>
.interkom-messages {
    scrollbar-width: thin;
    scrollbar-color: var(--border) transparent;
}
.interkom-messages::-webkit-scrollbar { width: 4px; }
.interkom-messages::-webkit-scrollbar-track { background: transparent; }
.interkom-messages::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }
</style>
