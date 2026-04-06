<script setup>
import ChatBubble from '@/Components/lawangsewu/ChatBubble.vue';
import SectionHeader from '@/Components/lawangsewu/SectionHeader.vue';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps({
    appMeta: {
        type: Object,
        default: () => ({}),
    },
    navGroups: {
        type: Array,
        default: () => [],
    },
    initialMessages: {
        type: Array,
        default: () => [],
    },
    activeUsers: {
        type: Array,
        default: () => [],
    },
});

const BASE_POLL_MS = 3000;
const BACKGROUND_POLL_MS = 8000;
const MAX_BACKOFF_MS = 20000;

const page = usePage();
const user = computed(() => page.props.auth.user);
const messages = ref([...props.initialMessages]);
const showContacts = ref(true);
const scrollContainer = ref(null);

const pollMs = ref(BASE_POLL_MS);
const pollTimer = ref(null);
const failedPolls = ref(0);

const onlineUsers = computed(() => props.activeUsers.filter((u) => u.is_active));

const scrollToBottom = () => {
    nextTick(() => {
        if (scrollContainer.value) {
            scrollContainer.value.scrollTop = scrollContainer.value.scrollHeight;
        }
    });
};

const normalizeIncomingMessage = (msg) => ({
    id: msg.id,
    user_id: msg.user_id,
    content: msg.content,
    created_at: msg.created_at,
    user: {
        id: msg.user?.id,
        name: msg.user?.name ?? 'Operator',
        alias: msg.user?.alias ?? null,
        avatar: msg.user?.avatar ?? null,
    },
});

const upsertMessage = (message) => {
    const idx = messages.value.findIndex((item) => item.id === message.id);

    if (idx === -1) {
        messages.value.push(message);
        return;
    }

    messages.value[idx] = message;
};

const syncFromServer = (pagePayload) => {
    messages.value = pagePayload.initialMessages;
    failedPolls.value = 0;
};

const schedulePolling = () => {
    if (pollTimer.value) {
        window.clearInterval(pollTimer.value);
    }

    pollTimer.value = window.setInterval(() => {
        router.reload({
            only: ['initialMessages', 'activeUsers'],
            preserveScroll: true,
            onSuccess: (inertiaPage) => {
                syncFromServer(inertiaPage.props);
            },
            onError: () => {
                failedPolls.value += 1;
                const backoff = Math.min(BASE_POLL_MS * (failedPolls.value + 1), MAX_BACKOFF_MS);
                pollMs.value = backoff;
                schedulePolling();
            },
        });
    }, pollMs.value);
};

const handleVisibilityChange = () => {
    pollMs.value = document.hidden ? BACKGROUND_POLL_MS : BASE_POLL_MS;
    schedulePolling();
};

const connectEcho = () => {
    const echo = window.Echo;

    if (!echo) {
        return;
    }

    echo.channel('lawangsewu.chat.global')
        .listen('.chat.message.sent', (payload) => {
            if (!payload?.message) {
                return;
            }

            upsertMessage(normalizeIncomingMessage(payload.message));
            scrollToBottom();
        });
};

onMounted(() => {
    scrollToBottom();
    schedulePolling();
    handleVisibilityChange();
    connectEcho();

    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    if (pollTimer.value) {
        window.clearInterval(pollTimer.value);
    }

    document.removeEventListener('visibilitychange', handleVisibilityChange);

    if (window.Echo) {
        window.Echo.leave('lawangsewu.chat.global');
    }
});

const form = useForm({
    content: '',
});

const sendMessage = () => {
    if (!form.content.trim() || form.processing) {
        return;
    }

    form.post(route('lawangsewu.chat.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            scrollToBottom();
        },
    });
};

watch(() => props.initialMessages, (newMessages) => {
    const wasAtBottom = scrollContainer.value
        && (scrollContainer.value.scrollHeight - scrollContainer.value.scrollTop <= scrollContainer.value.clientHeight + 100);

    messages.value = newMessages;

    if (wasAtBottom) {
        scrollToBottom();
    }
}, { deep: true });
</script>

<template>
    <Head title="Interkom Internal" />

    <LawangsewuLayout
        current-route="chat"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6 h-[calc(100vh-140px)] flex flex-col">
            <!-- Header Section -->
            <section class="card-surface p-5 py-4 shrink-0">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-600/10 text-blue-600">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-lg font-black uppercase tracking-[0.2em] text-[var(--text-1)]">Interkom Internal</h1>
                            <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest">Koordinasi Real-time Satu Komando</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden xl:flex items-center gap-2 px-4 py-2 rounded-xl bg-[var(--surface-2)] border border-[var(--border)]">
                            <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-2)]">{{ onlineUsers.length }} Petugas Online</span>
                        </div>
                        <button @click="showContacts = !showContacts" class="secondary-button !py-2">
                            {{ showContacts ? 'Sembunyikan Anggota' : 'Tampilkan Anggota' }}
                        </button>
                    </div>
                </div>
            </section>

            <div class="flex-1 flex gap-6 min-h-0">
                <!-- Chat Main Area -->
                <section class="flex-1 flex flex-col card-surface overflow-hidden !p-0">
                    <!-- Global Channel Header -->
                    <div class="px-6 py-4 border-b border-[var(--border)] bg-[var(--surface-2)]/30 backdrop-blur-sm flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="h-8 w-8 rounded-lg bg-blue-500/20 flex items-center justify-center text-blue-500 font-black text-[10px]">#</div>
                            <span class="text-xs font-black uppercase tracking-[0.2em] text-[var(--text-1)]">Internal-Umum</span>
                        </div>
                        <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest italic opacity-60">Pesan dihapus otomatis dalam 30 hari</p>
                    </div>

                    <!-- Messages List -->
                    <div 
                        ref="scrollContainer"
                        class="flex-1 overflow-y-auto p-6 space-y-6 scrollbar-thin scrollbar-thumb-[var(--border)]"
                    >
                        <template v-if="messages.length">
                            <ChatBubble
                                v-for="msg in messages"
                                :key="msg.id"
                                :message="{
                                    ...msg,
                                    alias: msg.user.alias || msg.user.name,
                                    avatar: msg.user.avatar,
                                    body: msg.content,
                                    time: new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                                }"
                                :own="msg.user_id === user.id"
                            />
                        </template>
                        <div v-else class="h-full flex flex-col items-center justify-center opacity-30 gap-4">
                            <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                            </svg>
                            <p class="text-xs font-black uppercase tracking-[0.3em]">Belum ada percakapan</p>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div class="p-4 bg-[var(--surface-2)]/50 border-t border-[var(--border)]">
                        <form @submit.prevent="sendMessage" class="flex gap-3">
                            <input 
                                v-model="form.content"
                                type="text" 
                                class="input-surface flex-1 !h-12 !px-5" 
                                placeholder="Tulis pesan rahasia atau koordinasi..."
                                :disabled="form.processing"
                                autofocus
                            >
                            <button 
                                type="submit" 
                                class="github-button !h-12 !px-8 !bg-blue-600 hover:!bg-blue-700 disabled:opacity-50"
                                :disabled="form.processing || !form.content.trim()"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Sidebar Right (Anggota) -->
                <aside v-if="showContacts" class="w-80 flex flex-col gap-6 shrink-0">
                    <!-- Current User Profile Card -->
                    <section class="card-surface p-5 border-blue-500/20 bg-blue-500/[0.02]">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-blue-500 mb-4">Profil Interkom</p>
                        <div class="flex items-center gap-4 mb-5">
                            <img v-if="user.avatar" :src="user.avatar" class="h-12 w-12 rounded-2xl border-2 border-blue-500/20 shadow-lg shadow-blue-500/10">
                            <div v-else class="h-12 w-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white font-black">
                                {{ (user.alias || user.name)[0].toUpperCase() }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-[var(--text-1)] truncate capitalize">{{ user.alias || user.name }}</p>
                                <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest italic">{{ user.role }}</p>
                            </div>
                        </div>
                        <Link :href="route('profile.edit')" class="secondary-button w-full !text-[10px] !py-2.5">
                            PENGATURAN ALIAS
                        </Link>
                    </section>

                    <!-- Active Users List -->
                    <section class="flex-1 card-surface p-5 flex flex-col overflow-hidden">
                        <p class="text-[10px] font-black uppercase tracking-[0.3em] text-[var(--text-3)] mb-4">Anggota Aktif</p>
                        <div class="flex-1 overflow-y-auto space-y-3 pr-2 scrollbar-thin">
                            <div v-for="u in activeUsers" :key="u.id" class="flex items-center gap-3 p-2 rounded-xl hover:bg-[var(--surface-2)] transition-colors group cursor-pointer">
                                <div class="relative">
                                    <img v-if="u.avatar" :src="u.avatar" class="h-8 w-8 rounded-lg object-cover grayscale-[0.5] group-hover:grayscale-0 transition-all">
                                    <div v-else class="h-8 w-8 rounded-lg bg-[var(--surface-3)] text-[var(--text-3)] font-black text-[10px] flex items-center justify-center">
                                        {{ (u.alias || u.name)[0].toUpperCase() }}
                                    </div>
                                    <div class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full border-2 border-[var(--surface-1)] bg-emerald-500 shadow-sm"></div>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-black text-[var(--text-1)] truncate">{{ u.alias || u.name }}</p>
                                    <p class="text-[9px] font-bold text-[var(--text-3)] uppercase tracking-tighter opacity-60">{{ u.role }}</p>
                                </div>
                            </div>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </LawangsewuLayout>
</template>
