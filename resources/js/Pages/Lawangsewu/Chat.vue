<script setup>
import ChatBubble from '@/Components/lawangsewu/ChatBubble.vue';
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
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
const showContacts = ref(false);
const scrollContainer = ref(null);
const draftContent = ref('');
const attachmentFile = ref(null);
const composerInput = ref(null);
const emojiPicker = ref(null);
const emojiToggle = ref(null);
const fileInput = ref(null);
const showEmojiPicker = ref(false);
const mediaError = ref('');
const isPreparingAttachment = ref(false);
const isSubmitting = ref(false);
const uploadProgress = ref(null);
const attachmentSummary = ref(null);
const attachmentPreviewUrl = ref('');
const showAttachmentPreviewModal = ref(false);
const maxAttachmentBytes = 2 * 1024 * 1024;
const emojiList = ['😀', '😁', '😂', '🤣', '😊', '😍', '🤩', '😎', '🤗', '😇', '🙏', '👍', '👌', '👏', '🔥', '💯', '🎉', '❤️'];

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
    metadata: msg.metadata ?? null,
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

const closeEmojiPicker = () => {
    showEmojiPicker.value = false;
};

const closeAttachmentPreviewModal = () => {
    showAttachmentPreviewModal.value = false;
};

const syncMessagesNow = () => {
    router.reload({
        only: ['initialMessages', 'activeUsers'],
        preserveScroll: true,
        onSuccess: (inertiaPage) => {
            syncFromServer(inertiaPage.props);
            scrollToBottom();
        },
    });
};

const formatBytes = (value) => {
    if (!value) {
        return '0 B';
    }

    if (value < 1024) {
        return `${value} B`;
    }

    if (value < 1024 * 1024) {
        return `${(value / 1024).toFixed(1)} KB`;
    }

    return `${(value / (1024 * 1024)).toFixed(2)} MB`;
};

const clearAttachment = () => {
    closeAttachmentPreviewModal();

    if (attachmentPreviewUrl.value) {
        URL.revokeObjectURL(attachmentPreviewUrl.value);
    }

    attachmentFile.value = null;
    attachmentSummary.value = null;
    attachmentPreviewUrl.value = '';
    mediaError.value = '';
    uploadProgress.value = null;

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const loadImageElement = (file) => new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new Image();

    image.onload = () => {
        URL.revokeObjectURL(objectUrl);
        resolve(image);
    };

    image.onerror = () => {
        URL.revokeObjectURL(objectUrl);
        reject(new Error('Gagal membaca gambar.'));
    };

    image.src = objectUrl;
});

const canvasToBlob = (canvas, type, quality) => new Promise((resolve, reject) => {
    canvas.toBlob((blob) => {
        if (blob) {
            resolve(blob);
            return;
        }

        reject(new Error('Gagal membuat hasil kompresi gambar.'));
    }, type, quality);
});

const compressImageIfNeeded = async (file) => {
    if (file.size <= maxAttachmentBytes) {
        return file;
    }

    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
        throw new Error('File gambar ini masih di atas 2 MB dan belum bisa dikompres otomatis. Coba JPG/PNG/WebP.');
    }

    const image = await loadImageElement(file);
    const canvas = document.createElement('canvas');
    const context = canvas.getContext('2d');

    if (!context) {
        throw new Error('Browser tidak mendukung kompres gambar di perangkat ini.');
    }

    const targetType = file.type === 'image/webp' || file.type === 'image/png' ? 'image/webp' : 'image/jpeg';
    const scales = [1, 0.92, 0.84, 0.76, 0.68, 0.6];
    const qualities = [0.9, 0.82, 0.74, 0.66, 0.58, 0.5, 0.42];

    for (const scale of scales) {
        const width = Math.max(1, Math.round(image.width * scale));
        const height = Math.max(1, Math.round(image.height * scale));
        canvas.width = width;
        canvas.height = height;
        context.clearRect(0, 0, width, height);
        context.drawImage(image, 0, 0, width, height);

        for (const quality of qualities) {
            const blob = await canvasToBlob(canvas, targetType, quality);

            if (blob.size <= maxAttachmentBytes) {
                const nextName = file.name.replace(/\.[^.]+$/, targetType === 'image/webp' ? '.webp' : '.jpg');

                return new File([blob], nextName, {
                    type: targetType,
                    lastModified: Date.now(),
                });
            }
        }
    }

    throw new Error('Gambar tidak bisa diperkecil hingga 2 MB secara otomatis. Coba resolusi yang lebih kecil.');
};

const handleAttachmentChange = async (event) => {
    const [selectedFile] = event.target.files || [];

    if (!selectedFile) {
        return;
    }

    mediaError.value = '';
    isPreparingAttachment.value = true;

    try {
        let preparedFile = selectedFile;

        if (selectedFile.type.startsWith('image/')) {
            preparedFile = await compressImageIfNeeded(selectedFile);
        } else if (selectedFile.size > maxAttachmentBytes) {
            throw new Error('Video harus maksimal 2 MB. Kompres otomatis video belum tersedia di browser ini.');
        }

        if (preparedFile.size > maxAttachmentBytes) {
            throw new Error('Ukuran file setelah diproses masih lebih dari 2 MB.');
        }

        attachmentFile.value = preparedFile;
        attachmentPreviewUrl.value = URL.createObjectURL(preparedFile);
        attachmentSummary.value = {
            name: preparedFile.name,
            size: formatBytes(preparedFile.size),
            kind: preparedFile.type.startsWith('video/') ? 'Video' : 'Gambar',
            mime: preparedFile.type,
        };
    } catch (error) {
        clearAttachment();
        mediaError.value = error instanceof Error ? error.message : 'Lampiran gagal diproses.';
    } finally {
        isPreparingAttachment.value = false;
    }
};

const handleDocumentPointerDown = (event) => {
    if (!showEmojiPicker.value) {
        return;
    }

    const target = event.target;

    if (emojiPicker.value?.contains(target) || emojiToggle.value?.contains(target)) {
        return;
    }

    closeEmojiPicker();
};

const handleEscapeKey = (event) => {
    if (event.key === 'Escape') {
        closeEmojiPicker();
        closeAttachmentPreviewModal();
    }
};

const insertEmojiAtCursor = (emoji) => {
    if (!emoji) {
        return;
    }

    const input = composerInput.value;

    if (!input) {
        draftContent.value = `${draftContent.value}${emoji}`;
        closeEmojiPicker();
        return;
    }

    const start = input.selectionStart ?? draftContent.value.length;
    const end = input.selectionEnd ?? draftContent.value.length;
    draftContent.value = `${draftContent.value.slice(0, start)}${emoji}${draftContent.value.slice(end)}`;

    nextTick(() => {
        input.focus();
        const cursor = start + emoji.length;
        input.setSelectionRange(cursor, cursor);
    });

    closeEmojiPicker();
};

onMounted(() => {
    scrollToBottom();
    schedulePolling();
    handleVisibilityChange();
    connectEcho();

    document.addEventListener('visibilitychange', handleVisibilityChange);
    document.addEventListener('pointerdown', handleDocumentPointerDown);
    document.addEventListener('keydown', handleEscapeKey);
});

onUnmounted(() => {
    if (pollTimer.value) {
        window.clearInterval(pollTimer.value);
    }

    document.removeEventListener('visibilitychange', handleVisibilityChange);
    document.removeEventListener('pointerdown', handleDocumentPointerDown);
    document.removeEventListener('keydown', handleEscapeKey);

    if (window.Echo) {
        window.Echo.leave('lawangsewu.chat.global');
    }

    if (attachmentPreviewUrl.value) {
        URL.revokeObjectURL(attachmentPreviewUrl.value);
    }

});

const sendMessage = () => {
    if ((!draftContent.value.trim() && !attachmentFile.value) || isSubmitting.value || isPreparingAttachment.value) {
        return;
    }

    mediaError.value = '';
    isSubmitting.value = true;
    uploadProgress.value = attachmentFile.value ? 0 : null;

    const payload = new FormData();
    const trimmedContent = draftContent.value.trim();

    if (trimmedContent) {
        payload.append('content', trimmedContent);
    }

    if (attachmentFile.value) {
        payload.append('attachment', attachmentFile.value);
    }

    window.axios.post(route('lawangsewu.chat.store'), payload, {
        headers: {
            Accept: 'application/json',
        },
        onUploadProgress: (event) => {
            if (!event.total) {
                return;
            }

            uploadProgress.value = Math.round((event.loaded * 100) / event.total);
        },
    }).then((response) => {
        const message = response?.data?.data;

        if (message) {
            upsertMessage({
                id: message.id,
                user_id: message.user_id ?? user.value.id,
                content: message.body ?? '',
                created_at: new Date().toISOString(),
                metadata: message.attachment ? { attachment: message.attachment } : null,
                user: {
                    id: user.value.id,
                    name: message.realName || user.value.name || 'Operator',
                    alias: message.alias || user.value.alias || user.value.name,
                    avatar: user.value.avatar || null,
                },
            });
        }

        draftContent.value = '';
        clearAttachment();
        closeEmojiPicker();
        uploadProgress.value = null;
        scrollToBottom();
        syncMessagesNow();
    }).catch((error) => {
        const errors = error?.response?.data?.errors || {};
        mediaError.value = errors.attachment?.[0] || (!attachmentFile.value ? errors.content?.[0] : '') || error?.response?.data?.message || 'Gagal mengirim pesan.';
    }).finally(() => {
        isSubmitting.value = false;

        if (!attachmentFile.value) {
            uploadProgress.value = null;
        }
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
    <Head title="Interkom Chat Masnya dan Mbaknya" />

    <LawangsewuLayout
        current-route="chat"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-4 h-[calc(100vh-140px)] flex flex-col sm:space-y-6">
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
                            <h1 class="text-lg font-black tracking-[0.12em] text-[var(--text-1)]">Interkom Chat Masnya dan Mbaknya</h1>
                            <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest">Ruang obrolan internal yang lebih santai</p>
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

            <div class="flex-1 flex flex-col gap-4 min-h-0 xl:flex-row xl:gap-6">
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
                                    user_id: msg.user_id,
                                    alias: msg.user.alias || msg.user.name,
                                    realName: msg.user.name,
                                    avatar: msg.user.avatar,
                                    body: msg.content,
                                    attachment: msg.metadata?.attachment || msg.attachment || null,
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
                        <form @submit.prevent="sendMessage" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                            <div class="relative flex-1">
                                <div
                                    v-if="showEmojiPicker"
                                    ref="emojiPicker"
                                    class="absolute bottom-[calc(100%+0.75rem)] left-0 z-20 w-[min(19rem,calc(100vw-3rem))] rounded-[24px] border border-[var(--border)] bg-[var(--surface-1)] p-3 shadow-[var(--shadow)]"
                                >
                                    <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.24em] text-[var(--text-3)]">
                                        Pilih Emoji
                                    </p>
                                    <div class="grid grid-cols-6 gap-2">
                                        <button
                                            v-for="emoji in emojiList"
                                            :key="emoji"
                                            type="button"
                                            class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[var(--surface-2)] text-xl transition hover:bg-[var(--accent-soft)]"
                                            @click="insertEmojiAtCursor(emoji)"
                                        >
                                            {{ emoji }}
                                        </button>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 rounded-[18px] border border-[var(--border)] bg-[var(--surface-1)] px-2.5 py-2 shadow-sm sm:gap-3 sm:rounded-[20px] sm:px-3">
                                    <input
                                        ref="fileInput"
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime"
                                        class="hidden"
                                        @change="handleAttachmentChange"
                                    >

                                    <button
                                        type="button"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface-2)] text-[var(--text-2)] transition hover:border-[var(--accent-border)] hover:bg-[var(--accent-soft)] hover:text-[var(--text-1)] sm:h-10 sm:w-10 sm:rounded-2xl"
                                        aria-label="Unggah gambar atau video"
                                        @click="fileInput?.click()"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                        </svg>
                                    </button>

                                    <button
                                        ref="emojiToggle"
                                        type="button"
                                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface-2)] text-lg transition hover:border-[var(--accent-border)] hover:bg-[var(--accent-soft)] sm:h-10 sm:w-10 sm:rounded-2xl sm:text-xl"
                                        :aria-expanded="showEmojiPicker"
                                        aria-label="Buka panel emoji"
                                        @click="showEmojiPicker = !showEmojiPicker"
                                    >
                                        <span class="translate-y-[1px]">😊</span>
                                    </button>

                                    <input
                                        ref="composerInput"
                                        v-model="draftContent"
                                        type="text"
                                        class="min-w-0 flex-1 border-0 bg-transparent px-1 py-0 text-sm text-[var(--text-1)] outline-none placeholder:text-[var(--text-3)]"
                                        placeholder="Tulis pesan rahasia atau koordinasi..."
                                        :disabled="isSubmitting"
                                        autofocus
                                        @focus="closeEmojiPicker"
                                    >
                                </div>

                                <div v-if="attachmentSummary || mediaError || isPreparingAttachment" class="mt-2 space-y-2 px-1">
                                    <div
                                        v-if="attachmentSummary"
                                        class="flex items-center justify-between gap-3 rounded-2xl border border-[var(--accent-border)] bg-[var(--accent-soft)] px-3 py-2 text-[11px]"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate font-bold text-[var(--text-1)]">{{ attachmentSummary.kind }} siap dikirim</p>
                                            <p class="truncate text-[var(--text-2)]">{{ attachmentSummary.name }} · {{ attachmentSummary.size }}</p>
                                        </div>
                                        <button
                                            type="button"
                                            class="rounded-xl px-2 py-1 text-[var(--text-2)] transition hover:bg-white/40 hover:text-[var(--text-1)]"
                                            @click="clearAttachment"
                                        >
                                            Hapus
                                        </button>
                                    </div>

                                    <div
                                        v-if="attachmentSummary && attachmentPreviewUrl"
                                        class="overflow-hidden rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-2"
                                    >
                                        <img
                                            v-if="attachmentSummary.kind === 'Gambar'"
                                            :src="attachmentPreviewUrl"
                                            :alt="attachmentSummary.name"
                                            class="max-h-40 w-full cursor-zoom-in rounded-xl object-cover sm:max-h-52"
                                            @click="showAttachmentPreviewModal = true"
                                        >
                                        <video
                                            v-else
                                            :src="attachmentPreviewUrl"
                                            class="max-h-40 w-full rounded-xl bg-black sm:max-h-52"
                                            controls
                                            playsinline
                                            preload="metadata"
                                        />
                                    </div>

                                    <p v-if="isPreparingAttachment" class="text-[11px] font-medium text-[var(--text-2)]">
                                        Menyiapkan lampiran dan mencoba kompres otomatis ke maksimal 2 MB...
                                    </p>

                                    <div
                                        v-if="uploadProgress !== null"
                                        class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-3 py-2"
                                    >
                                        <div class="mb-1 flex items-center justify-between text-[11px] font-medium text-[var(--text-2)]">
                                            <span>Mengunggah lampiran...</span>
                                            <span>{{ uploadProgress }}%</span>
                                        </div>
                                        <div class="h-2 overflow-hidden rounded-full bg-[var(--surface-2)]">
                                            <div
                                                class="h-full rounded-full bg-blue-600 transition-all duration-300"
                                                :style="{ width: `${uploadProgress}%` }"
                                            />
                                        </div>
                                    </div>

                                    <p v-if="mediaError" class="text-[11px] font-medium text-rose-500">
                                        {{ mediaError }}
                                    </p>
                                </div>
                            </div>
                            <button 
                                type="submit" 
                                class="github-button h-11 w-full self-stretch !bg-blue-600 !px-6 hover:!bg-blue-700 disabled:opacity-50 sm:!h-12 sm:w-auto sm:!px-8"
                                :disabled="isSubmitting || isPreparingAttachment || (!draftContent.trim() && !attachmentFile)"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                                </svg>
                            </button>
                        </form>
                    </div>
                </section>

                <!-- Sidebar Right (Anggota) -->
                <aside v-if="showContacts" class="w-full xl:w-80 flex flex-col gap-6 xl:shrink-0">
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

        <div
            v-if="showAttachmentPreviewModal && attachmentSummary?.kind === 'Gambar' && attachmentPreviewUrl"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm"
            @click.self="closeAttachmentPreviewModal"
        >
            <div class="relative w-full max-w-5xl">
                <button
                    type="button"
                    class="absolute right-3 top-3 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-black/55 text-white transition hover:bg-black/75"
                    aria-label="Tutup preview gambar"
                    @click="closeAttachmentPreviewModal"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>

                <img
                    :src="attachmentPreviewUrl"
                    :alt="attachmentSummary.name"
                    class="max-h-[85vh] w-full rounded-3xl object-contain"
                >
            </div>
        </div>
    </LawangsewuLayout>
</template>

<style scoped>
emoji-picker {
    --border-color: transparent;
    --background: var(--surface-1);
    --category-emoji-padding: 0.45rem;
    --category-emoji-size: 1.4rem;
    --emoji-size: 1.35rem;
    --indicator-color: var(--accent);
    --input-border-color: var(--border);
    --input-font-color: var(--text-1);
    --input-placeholder-color: var(--text-3);
    --input-background-color: var(--surface-2);
    --nav-button-active-background: var(--accent-soft);
    --outline-color: var(--accent-border);
    --preview-background: var(--surface-2);
}
</style>
