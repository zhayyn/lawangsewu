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
    ticketStats:  { type: Object, default: () => ({ total: 0, open: 0, replied: 0, sent: 0, closed: 0, pengaduan: 0, konsultasi: 0, umum: 0, todayTotal: 0 }) },
    recentTickets:{ type: Array,  default: () => [] },
});

// ─── State ────────────────────────────────────────────
const isLoading      = ref(false);
const isBusy        = ref(false);
const autoRefresh   = ref(true);
const pollRef       = ref(null);

const runtimeHealth     = ref({});
const qrDataUrl         = ref('');
const historyItems      = ref([]);
const lidMappings       = ref({}); // lid baseUser → pn baseUser (e.g. '229583802597421' → '6285123456789')
const connectedInfo     = ref(null);   // info device yang terkoneksi
const qrPollingRef      = ref(null);   // interval polling QR
const qrLoading         = ref(false);  // sedang memuat QR
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
const threadZoom            = ref(100);
const threadVisibleCount    = ref(12);
const markEditorOpen        = ref(false);
const markState             = ref('idle');
const markDraftDirty        = ref(false);
const markForm              = ref({
    label: '',
    tone: 'amber',
    note: '',
    isPinned: false,
});

// Reply / Send
const replyText  = ref('');
const replyState = ref('idle');
const sendTo     = ref('');
const sendText   = ref('');
const sendState  = ref('idle');
const replyCooldownRef = ref(null);
const sendCooldownRef = ref(null);

// Handover
const handoverReason  = ref('');
const handoverLoading = ref(false);
const showHandoverModal = ref(false);
const handoverEnabled = ref(true);
const handoverToggling = ref(false);

// Status
const statusText = ref('Memeriksa koneksi...');
const statusTone = ref('warn');

// Tickets
const tickets           = ref(props.recentTickets ?? []);
const latestTicketStats = ref({ ...props.ticketStats });
const ticketFilter      = ref({ type: '', status: '' });
const ticketLoading     = ref(false);
const activeTicketId    = ref(null);
const activeTicket      = computed(() => tickets.value.find(t => t.id === activeTicketId.value) || null);
const ticketReplyText   = ref('');
const ticketReplyState  = ref('idle');
const ticketTransferTo  = ref('');

const isAdmin      = computed(() => Boolean(props.authUser?.isAdmin));
const isSuperAdmin = computed(() => Boolean(props.authUser?.isSuperAdmin));
const isConnected  = computed(() => Boolean(runtimeHealth.value?.connected || runtimeHealth.value?.status === 'connected'));
const hasRealtime = computed(() => typeof window !== 'undefined' && Boolean(window.Echo));
const myId = computed(() => props.authUser?.id);

// Ownership (display-only — everyone can reply)
const iMineConvo = computed(() => activeConvo.value?.owner?.id === myId.value);
const isUnclaimedConvo = computed(() => !activeConvo.value?.owner);
// Every authenticated user may reply to any conversation.
const canReply = computed(() => Boolean(activeConvo.value));
const hasPendingHandover = computed(() => Boolean(activeConvo.value?.pendingHandover));
const iRequestedHandover = computed(() =>
    activeConvo.value?.pendingHandover?.requestor?.id === myId.value
);
const conversationLockState = computed(() => activeConvo.value?.ownership || 'unclaimed');
const activeCustomerMark = computed(() => activeConvo.value?.customerMark || null);
const ownerPresenceLabel = computed(() => ({
    active: 'operator aktif sekarang',
    standby: 'operator standby',
    idle: 'operator idle',
}[activeConvo.value?.ownerPresence] || ''));

const statusClass = computed(() => ({
    ok:     'bg-emerald-500/20 border-emerald-500/40 text-emerald-300',
    warn:   'bg-amber-500/20 border-amber-500/40 text-amber-300',
    danger: 'bg-rose-500/20 border-rose-500/40 text-rose-300',
}[statusTone.value] || 'bg-slate-500/20 border-slate-500/40 text-slate-200'));

const groupParticipantCount = computed(() => {
    if (!activeConvo.value?.isGroup) return null;

    const distinct = new Set(
        conversationMessages.value
            .filter((msg) => msg.direction === 'inbound')
            .map((msg) => msg.senderKey)
            .filter(Boolean),
    );

    return distinct.size || null;
});

const threadHeightPx = computed(() => {
    const perMessage = 72 * (threadZoom.value / 100);
    const chrome = 120;
    const target = (threadVisibleCount.value * perMessage) + chrome;
    return Math.max(320, Math.min(980, Math.round(target)));
});

const threadViewportStyle = computed(() => ({
    minHeight: `${Math.max(260, threadHeightPx.value - 140)}px`,
    maxHeight: `${threadHeightPx.value}px`,
}));

const customBackgroundStyle = computed(() => {
    if (!props.config?.background) return null;
    const bg = props.config.background.trim();
    if (bg.startsWith('http') || bg.startsWith('/')) {
        return { backgroundImage: `url('${bg}')`, backgroundSize: 'cover', backgroundPosition: 'center' };
    }
    return { background: bg };
});

const threadMessageScaleStyle = computed(() => ({
    fontSize: `${threadZoom.value}%`,
}));

const threadGapClass = computed(() => {
    if (threadZoom.value <= 90) return 'space-y-2';
    if (threadZoom.value >= 115) return 'space-y-4';
    return 'space-y-3';
});

const markToneClass = (tone) => ({
    amber: 'bg-amber-100 text-amber-700 border-amber-200',
    emerald: 'bg-emerald-100 text-emerald-700 border-emerald-200',
    rose: 'bg-rose-100 text-rose-700 border-rose-200',
    sky: 'bg-sky-100 text-sky-700 border-sky-200',
    violet: 'bg-violet-100 text-violet-700 border-violet-200',
    slate: 'bg-slate-100 text-slate-700 border-slate-200',
}[tone || 'amber'] || 'bg-amber-100 text-amber-700 border-amber-200');

const markToneOptions = [
    { value: 'amber', label: 'Amber' },
    { value: 'emerald', label: 'Emerald' },
    { value: 'rose', label: 'Rose' },
    { value: 'sky', label: 'Sky' },
    { value: 'violet', label: 'Violet' },
    { value: 'slate', label: 'Slate' },
];

const markTonePickerClass = (tone) => ({
    amber: markForm.value.tone === 'amber'
        ? 'border-amber-400 bg-gradient-to-r from-amber-200 to-orange-300 text-amber-900 shadow-[0_8px_20px_rgba(251,191,36,0.35)]'
        : 'border-amber-200 bg-white text-amber-800 hover:border-amber-300 hover:bg-amber-50',
    emerald: markForm.value.tone === 'emerald'
        ? 'border-emerald-400 bg-gradient-to-r from-emerald-200 to-teal-300 text-emerald-900 shadow-[0_8px_20px_rgba(16,185,129,0.28)]'
        : 'border-emerald-200 bg-white text-emerald-800 hover:border-emerald-300 hover:bg-emerald-50',
    rose: markForm.value.tone === 'rose'
        ? 'border-rose-400 bg-gradient-to-r from-rose-200 to-pink-300 text-rose-900 shadow-[0_8px_20px_rgba(244,63,94,0.30)]'
        : 'border-rose-200 bg-white text-rose-800 hover:border-rose-300 hover:bg-rose-50',
    sky: markForm.value.tone === 'sky'
        ? 'border-sky-400 bg-gradient-to-r from-sky-200 to-cyan-300 text-sky-900 shadow-[0_8px_20px_rgba(14,165,233,0.28)]'
        : 'border-sky-200 bg-white text-sky-800 hover:border-sky-300 hover:bg-sky-50',
    violet: markForm.value.tone === 'violet'
        ? 'border-violet-400 bg-gradient-to-r from-violet-200 to-fuchsia-300 text-violet-900 shadow-[0_8px_20px_rgba(139,92,246,0.32)]'
        : 'border-violet-200 bg-white text-violet-800 hover:border-violet-300 hover:bg-violet-50',
    slate: markForm.value.tone === 'slate'
        ? 'border-slate-400 bg-gradient-to-r from-slate-200 to-gray-300 text-slate-900 shadow-[0_8px_20px_rgba(100,116,139,0.28)]'
        : 'border-slate-200 bg-white text-slate-800 hover:border-slate-300 hover:bg-slate-50',
}[tone || 'amber']);

const hashText = (text) => {
    const value = String(text || 'x');
    let hash = 0;
    for (let i = 0; i < value.length; i += 1) {
        hash = (hash * 37 + value.charCodeAt(i)) % 2147483647;
    }
    return Math.abs(hash);
};

const avatarToneClassFor = (seed) => {
    const classes = [
        'avatar-tone-1',
        'avatar-tone-2',
        'avatar-tone-3',
        'avatar-tone-4',
        'avatar-tone-5',
        'avatar-tone-6',
    ];
    return classes[hashText(seed) % classes.length];
};

const initialsFromName = (name) => {
    const text = String(name || '').trim();
    if (!text) return '?';

    const parts = text.split(/\s+/).slice(0, 2);
    return parts.map((part) => part[0]?.toUpperCase() || '').join('') || text[0].toUpperCase();
};

const onProfileImageError = (conversationId) => {
    const idx = conversations.value.findIndex((item) => item.conversationId === conversationId);
    if (idx === -1) return;

    conversations.value[idx] = {
        ...conversations.value[idx],
        profilePhotoUrl: null,
    };
};

const outgoingTickIcon = (status) => ({
    sending: '○',
    queued: '◔',
    sent: '✓',
    delivered: '✓✓',
    read: '✓✓',
    failed: '⚠',
}[status] || '');

const outgoingTickClass = (status) => ({
    sent: 'text-slate-500',
    delivered: 'text-slate-500',
    read: 'text-sky-600',
    queued: 'text-slate-400',
    sending: 'text-slate-400',
    failed: 'text-rose-500',
}[status] || 'text-slate-400');

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

const scheduleReplyStateReset = (delay = 1200) => {
    if (replyCooldownRef.value) clearTimeout(replyCooldownRef.value);
    replyCooldownRef.value = setTimeout(() => {
        replyState.value = 'idle';
        replyCooldownRef.value = null;
    }, delay);
};

const scheduleSendStateReset = (delay = 1200) => {
    if (sendCooldownRef.value) clearTimeout(sendCooldownRef.value);
    sendCooldownRef.value = setTimeout(() => {
        sendState.value = 'idle';
        sendCooldownRef.value = null;
    }, delay);
};

const sortConversations = (list) => {
    return [...list].sort((a, b) => {
        const activityA = Number(a?.lastActivityTs || 0);
        const activityB = Number(b?.lastActivityTs || 0);
        if (activityA !== activityB) return activityB - activityA;

        const unreadA = Number(a?.unreadCount || 0);
        const unreadB = Number(b?.unreadCount || 0);
        if (unreadA !== unreadB) return unreadB - unreadA;

        return String(a?.displayTitle || a?.remoteNumber || '').localeCompare(
            String(b?.displayTitle || b?.remoteNumber || ''),
            'id',
        );
    });
};

const mergeConversation = (incoming) => {
    if (!incoming?.conversationId) return;

    const normalized = normalizeConversation(incoming);

    const idx = conversations.value.findIndex((item) => item.conversationId === normalized.conversationId);
    if (idx === -1) {
        conversations.value.unshift(normalized);
    } else {
        const merged = {
            ...conversations.value[idx],
            ...normalized,
        };
        conversations.value.splice(idx, 1);
        conversations.value.unshift(normalizeConversation(merged));
    }

    conversations.value = sortConversations(conversations.value);
};

const upsertConversationMessage = (incoming) => {
    if (!incoming) return;

    const normalizedIncoming = normalizeMessage(incoming);

    const existingById = normalizedIncoming.id
        ? conversationMessages.value.findIndex((item) => item.id === normalizedIncoming.id)
        : -1;

    if (existingById !== -1) {
        conversationMessages.value[existingById] = {
            ...conversationMessages.value[existingById],
            ...normalizedIncoming,
        };
        return;
    }

    const existingTempIndex = normalizedIncoming.direction === 'outbound'
        ? conversationMessages.value.findIndex((item) => item._tempId && item.text === normalizedIncoming.text && item.status === 'sending')
        : -1;

    if (existingTempIndex !== -1) {
        conversationMessages.value[existingTempIndex] = {
            ...conversationMessages.value[existingTempIndex],
            ...normalizedIncoming,
            _tempId: null,
        };
    } else {
        conversationMessages.value.push(normalizedIncoming);
    }

    nextTick(() => {
        if (threadEl.value) threadEl.value.scrollTop = threadEl.value.scrollHeight;
    });
};

const pushTempOutboundMessage = (text) => {
    const tempId = `temp-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
    conversationMessages.value.push(normalizeMessage({
        _tempId: tempId,
        direction: 'outbound',
        operator: props.authUser?.alias || props.authUser?.name || 'Operator',
        senderName: props.authUser?.alias || props.authUser?.name || 'Anda',
        senderKey: String(props.authUser?.id || 'self'),
        sentAt: 'sekarang',
        text,
        type: 'text',
        status: 'sending',
    }));

    nextTick(() => {
        if (threadEl.value) threadEl.value.scrollTop = threadEl.value.scrollHeight;
    });

    return tempId;
};

const markTempMessageStatus = (tempId, status) => {
    const idx = conversationMessages.value.findIndex((msg) => msg._tempId === tempId);
    if (idx === -1) return;
    conversationMessages.value[idx] = {
        ...conversationMessages.value[idx],
        status,
    };
};

const memberColorMap = [
    'member-color-1',
    'member-color-2',
    'member-color-3',
    'member-color-4',
    'member-color-5',
    'member-color-6',
    'member-color-7',
    'member-color-8',
];

const isGroupByRemote = (remoteNumber) => {
    const value = String(remoteNumber || '');
    return value.endsWith('@g.us') || value.includes('group:');
};

const normalizeRemoteIdentifier = (value) => {
    const raw = String(value || '').trim();
    if (!raw) return '';

    // Tolerate typo payload variants such as @llid.
    return raw.replace(/@llid$/i, '@lid');
};

const isLidNumber = (value) => {
    const normalized = normalizeRemoteIdentifier(value);
    return String(normalized || '').endsWith('@lid');
};

const formatWaPhoneNumber = (raw) => {
    const digits = String(raw || '').replace(/\D/g, '');
    if (!digits) return '';

    if (digits.startsWith('62')) return digits;
    if (digits.startsWith('0')) return `62${digits.slice(1)}`;
    if (digits.startsWith('8')) return `62${digits}`;

    return digits;
};

const getCleanRemoteNumber = (value) => {
    const normalized = normalizeRemoteIdentifier(value);
    if (!normalized) return '-';

    if (normalized.endsWith('@g.us')) {
        return normalized.replace(/@g\.us$/i, '');
    }

    // Return only the number part without any suffix
    const clean = normalized.replace(/@(g\.us|s\.whatsapp\.net|lid)$/i, '');

    // Jika ini adalah LID dan ada mapping ke nomor HP, gunakan nomor HP tersebut
    if (normalized.endsWith('@lid') && lidMappings.value[clean]) {
        const pn = lidMappings.value[clean];
        return formatWaPhoneNumber(pn) || pn;
    }

    // Pastikan nomor tampil konsisten sebagai 62xx... saat memungkinkan.
    const formatted = formatWaPhoneNumber(clean);
    return formatted || clean;
};

const getLidBaseNumber = (value) => {
    const normalized = normalizeRemoteIdentifier(value);
    if (!normalized || !normalized.endsWith('@lid')) return '';
    return normalized.replace(/@lid$/i, '');
};

const getMappedWaNumber = (value) => {
    const lidBase = getLidBaseNumber(value);
    if (!lidBase) return '';
    const mapped = lidMappings.value[lidBase];
    if (!mapped) return '';
    return formatWaPhoneNumber(mapped) || String(mapped);
};

const primaryContactNumber = (value) => {
    const normalized = normalizeRemoteIdentifier(value);
    if (!normalized) return '-';
    if (!isLidNumber(normalized)) return getCleanRemoteNumber(normalized);

    const mappedWa = getMappedWaNumber(normalized);
    return mappedWa || `LID: ${getLidBaseNumber(normalized)}`;
};

const formatRemoteLabel = (value) => {
    const normalized = normalizeRemoteIdentifier(value);
    if (!normalized) return '-';

    if (normalized.endsWith('@g.us')) {
        return `${normalized.replace(/@g\.us$/i, '')} (grup)`;
    }

    if (normalized.endsWith('@s.whatsapp.net')) {
        return `${normalized.replace(/@s\.whatsapp\.net$/i, '')} (wa)`;
    }

    if (normalized.endsWith('@lid')) {
        return `${normalized.replace(/@lid$/i, '')} (lid)`;
    }

    return normalized;
};

const memberColorClassFor = (senderKey) => {
    const key = String(senderKey || 'guest');
    let hash = 0;
    for (let i = 0; i < key.length; i += 1) {
        hash = (hash * 31 + key.charCodeAt(i)) % 2147483647;
    }
    return memberColorMap[Math.abs(hash) % memberColorMap.length];
};

const normalizeConversation = (raw = {}) => {
    const remoteNumber = normalizeRemoteIdentifier(raw.remoteNumber || '');
    const isGroup = Boolean(raw.isGroup) || isGroupByRemote(remoteNumber);
    const groupName = raw.groupName || (isGroup ? raw.remoteName : null);
    const aliasLabel = String(raw.customerMark?.label || '').trim();
    const waName = String(raw.remoteName || '').trim(); // notifyName from WhatsApp
    // Non-group title priority: alias > WA display name > normalized number
    const displayTitle = isGroup 
        ? (raw.displayTitle || groupName || 'Grup WhatsApp') 
        : (aliasLabel || waName || getCleanRemoteNumber(remoteNumber));

    return {
        ...raw,
        remoteNumber,
        isGroup,
        groupName,
        displayTitle,
        customerMark: raw.customerMark || null,
    };
};

const normalizeMessage = (raw = {}) => {
    const metadata = raw?.metadata && typeof raw.metadata === 'object' ? raw.metadata : {};
    const remoteNumber = normalizeRemoteIdentifier(raw.remoteNumber || activeConvo.value?.remoteNumber || '');
    const isGroup = Boolean(raw.isGroup) || isGroupByRemote(remoteNumber) || Boolean(metadata.isGroup);
    const media = metadata?.media && typeof metadata.media === 'object' ? metadata.media : {};
    const mediaKind = raw.mediaKind || media.kind || (raw.type === 'sticker' ? 'sticker' : (raw.type === 'image' ? 'image' : null));
    const mediaMime = raw.mediaMime || media.mimetype || metadata?.mimetype || null;
    const mediaUrl = raw.mediaUrl || media.dataUrl || media.previewDataUrl || null;
    const hasVisualMedia = Boolean(mediaUrl && (mediaKind === 'image' || mediaKind === 'sticker' || String(mediaMime || '').startsWith('image/')));
    const mediaCaption = String(raw.text || media.caption || '').trim();

    const senderKey = String(
        raw.senderKey
        || metadata.participant
        || metadata.author
        || metadata.from
        || remoteNumber
        || 'guest',
    );

    const senderDisplay = raw.direction === 'outbound'
        ? (raw.operator || props.authUser?.alias || props.authUser?.name || 'Anda')
        : (raw.senderName || metadata.participantName || metadata.senderName || metadata.pushName || (isGroup ? 'Anggota Grup' : (activeConvo.value?.remoteName || 'Kontak')));

    return {
        ...raw,
        remoteNumber,
        metadata,
        isGroup,
        groupName: raw.groupName || metadata.groupName || metadata.groupSubject || (isGroup ? (activeConvo.value?.groupName || activeConvo.value?.remoteName) : null),
        mediaKind,
        mediaMime,
        mediaUrl,
        hasVisualMedia,
        mediaCaption,
        senderKey,
        senderDisplay,
        senderColorClass: raw.direction === 'outbound' ? 'member-color-self' : memberColorClassFor(senderKey),
        avatarToneClass: avatarToneClassFor(senderKey || senderDisplay || remoteNumber),
        senderInitials: initialsFromName(senderDisplay),
    };
};

const bubbleWrapClass = (msg) => (
    msg.direction === 'outbound'
        ? 'justify-end'
        : 'justify-start'
);

const bubbleCardClass = (msg) => {
    if (msg.direction === 'outbound') {
        return [
            'bubble-outbound text-slate-900 rounded-tr-sm',
            msg.status === 'sending' ? 'bubble-sending' : '',
            msg.status === 'failed' ? 'bubble-failed' : '',
            msg._tempId ? 'bubble-pop-in' : '',
        ];
    }

    return ['bubble-inbound text-slate-900 rounded-tl-sm', msg._tempId ? 'bubble-pop-in' : ''];
};

const bubbleMetaClass = (msg) => (
    msg.direction === 'outbound' ? 'text-slate-600' : 'text-slate-500'
);

const bubbleFooterClass = (msg) => (
    msg.direction === 'outbound' ? 'text-slate-500' : 'text-slate-500'
);

const outgoingStatusClass = (msg) => ({
    sent: 'text-sky-600',
    queued: 'text-slate-500',
    sending: 'text-slate-500',
    failed: 'text-rose-500',
}[msg.status] || '');

const messageTypeLabel = (type, msg = null) => {
    const normalized = String(type || '').toLowerCase();
    if (normalized === 'text') return 'text';
    if (normalized === 'image') return 'gambar';
    if (normalized === 'sticker') return 'stiker';
    if (normalized === 'document') return 'dokumen';
    if (normalized === 'video') return 'video';
    if (normalized === 'audio') return 'audio';

    if (msg?.hasVisualMedia) {
        return msg.mediaKind === 'sticker' ? 'stiker' : 'gambar';
    }

    return normalized || 'pesan';
};

const syncMarkFormFromActive = () => {
    if (markEditorOpen.value && markDraftDirty.value && markState.value !== 'saving') {
        return;
    }

    const mark = activeConvo.value?.customerMark;
    markForm.value = {
        label: mark?.label || '',
        tone: mark?.tone || 'amber',
        note: mark?.note || '',
        isPinned: Boolean(mark?.isPinned),
    };
    markDraftDirty.value = false;
};

const toggleMarkEditor = () => {
    markEditorOpen.value = !markEditorOpen.value;
    if (markEditorOpen.value) {
        syncMarkFormFromActive();
        return;
    }
    markDraftDirty.value = false;
};

const saveCustomerMark = async () => {
    if (!activeConvoId.value) return;

    const label = markForm.value.label.trim();
    if (!label) {
        markState.value = 'error';
        setTimeout(() => { markState.value = 'idle'; }, 1400);
        return;
    }

    markState.value = 'saving';
    try {
        const result = await callApi('mark-customer', {
            method: 'post',
            data: {
                conversation_id: activeConvoId.value,
                label,
                tone: markForm.value.tone,
                note: markForm.value.note,
                is_pinned: markForm.value.isPinned,
            },
        });

        mergeConversation({
            conversationId: activeConvoId.value,
            customerMark: result?.mark || {
                label,
                tone: markForm.value.tone,
                note: markForm.value.note,
                isPinned: markForm.value.isPinned,
            },
        });

        markDraftDirty.value = false;

        markState.value = 'saved';
        appendLog('Tanda customer diperbarui', { conversation: activeConvoId.value, label });
        setTimeout(() => { markState.value = 'idle'; }, 1200);
    } catch (err) {
        markState.value = 'error';
        appendLog('Gagal menyimpan tanda customer', { error: err?.error });
        setTimeout(() => { markState.value = 'idle'; }, 1800);
    }
};

const clearCustomerMark = async () => {
    if (!activeConvoId.value) return;

    markState.value = 'saving';
    try {
        await callApi('unmark-customer', {
            method: 'post',
            data: { conversation_id: activeConvoId.value },
        });

        mergeConversation({
            conversationId: activeConvoId.value,
            customerMark: null,
        });

        syncMarkFormFromActive();
        markDraftDirty.value = false;
        markState.value = 'saved';
        appendLog('Tanda customer dihapus', { conversation: activeConvoId.value });
        setTimeout(() => { markState.value = 'idle'; }, 1200);
    } catch (err) {
        markState.value = 'error';
        appendLog('Gagal menghapus tanda customer', { error: err?.error });
        setTimeout(() => { markState.value = 'idle'; }, 1800);
    }
};

const setThreadZoom = (value) => {
    threadZoom.value = Math.max(80, Math.min(130, Number(value) || 100));
};

const setThreadVisibleCount = (value) => {
    threadVisibleCount.value = Math.max(6, Math.min(24, Number(value) || 12));
};

const adjustThreadZoom = (delta) => {
    setThreadZoom(threadZoom.value + delta);
};

const ticketTypeOptions = [
    { value: 'pengaduan', label: '📢 Pengaduan' },
    { value: 'konsultasi', label: '💬 Konsultasi' },
    { value: 'umum', label: '📩 Umum' },
];

const normalizeTicketType = (type) => {
    const allowed = ['pengaduan', 'konsultasi', 'umum', 'live_konsul'];
    return allowed.includes(type) ? type : '';
};

const canClassifyTicket = (ticket) => {
    if (!ticket) return false;
    return ticket.status === 'open' || ticket.status === 'replied';
};

// ─── Refresh Methods ──────────────────────────────────
const refreshHealth = async () => {
    try {
        const data = await callApi('health');
        runtimeHealth.value = data || {};
        connectedInfo.value = data?.info || null;
        if (isConnected.value) {
            statusText.value = 'Device terhubung. Inbox aktif.';
            statusTone.value = 'ok';
            stopQrPolling();
            qrDataUrl.value = '';
        } else if (runtimeHealth.value?.hasQr) {
            statusText.value = 'Device belum login. Pindai QR Code untuk terhubung.';
            statusTone.value = 'warn';
        } else {
            statusText.value = 'Runtime tidak merespons atau device terputus.';
            statusTone.value = 'danger';
        }
    } catch {
        statusText.value = 'Runtime tidak terjangkau.';
        statusTone.value = 'danger';
    }
};

const refreshQr = async () => {
    if (isConnected.value) { qrDataUrl.value = ''; return; }
    try {
        const d = await callApi('qr');
        const url = d?.qrDataUrl || '';
        if (url) {
            qrDataUrl.value = url;
            // Jika QR tersedia, stop polling — QR sudah di tangan user
        } else if (d?.connected) {
            // Ternyata sudah connect
            qrDataUrl.value = '';
            stopQrPolling();
            await refreshHealth();
        }
    } catch { /* silent */ }
};

// Minta runtime generate QR baru
const generateQr = async () => {
    if (isConnected.value) return;
    qrLoading.value = true;
    qrDataUrl.value = '';
    appendLog('Generate QR diminta...');
    try {
        await callApi('refresh-qr', { method: 'post' });
        appendLog('Permintaan QR dikirim. Menunggu QR dari runtime...');
        // Mulai polling QR setiap 4 detik
        startQrPolling();
    } catch (err) {
        appendLog('Generate QR gagal', { error: err?.error });
    } finally {
        qrLoading.value = false;
    }
};

const startQrPolling = () => {
    stopQrPolling();
    qrPollingRef.value = setInterval(async () => {
        await refreshQr();
        await refreshHealth();
        // Jika sudah terkoneksi, stop
        if (isConnected.value) stopQrPolling();
    }, 4000);
};

const stopQrPolling = () => {
    if (qrPollingRef.value) {
        clearInterval(qrPollingRef.value);
        qrPollingRef.value = null;
    }
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

const refreshLidMappings = async () => {
    try {
        const data = await callApi('lid-mappings');
        if (data?.pairs && Array.isArray(data.pairs)) {
            const map = {};
            data.pairs.forEach(({ lid, pn }) => {
                // strip suffix: '229583802597421@lid' → '229583802597421'
                const lidBase = String(lid || '').replace(/@lid$/i, '');
                const pnBase  = String(pn  || '').replace(/@(s\.whatsapp\.net|pn)$/i, '');
                if (lidBase && pnBase) map[lidBase] = pnBase;
            });
            lidMappings.value = map;
        }
    } catch { /* silent */ }
};

const refreshInboxList = async (preserveActive = true) => {
    try {
        const data = await callApi('inbox');
        conversations.value = sortConversations((data?.conversations || []).map(normalizeConversation));
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
        const serverMessages = (data?.messages || []).map(normalizeMessage);
        // Preserve temp messages (sending/failed) that are not yet in DB
        const pendingTemps = conversationMessages.value.filter(
            (m) => m._tempId && ['sending', 'failed'].includes(m.status)
        );
        conversationMessages.value = [...serverMessages, ...pendingTemps];
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
        await Promise.all([refreshHealth(), refreshStats(), refreshHistory(), refreshLidMappings()]);
        
        // Only attempt heavier calls if connected or at least has health response
        if (runtimeHealth.value?.status) {
            // Pull first, then refresh list so ordering reflects newest incoming chat immediately.
            await pullInbox();
            await Promise.all([refreshInboxList(), refreshQr(), refreshConvoMessages()]);
        } else {
            // Unset data to indicate downtime
            activeConvoId.value = '';
            conversations.value = [];
            conversationMessages.value = [];
        }
    } catch (err) {
        appendLog('Error sinkronisasi', { message: err?.error || err?.message || 'Unknown' });
    } finally {
        isLoading.value = false;
        scheduleNextRefresh();
    }
};

const scheduleNextRefresh = () => {
    if (!autoRefresh.value) return;
    if (pollRef.value) clearTimeout(pollRef.value);
    
    // Backoff logic: 10s normal, 30s if disconnected
    const delay = isConnected.value ? 10000 : 30000;
    pollRef.value = setTimeout(refreshAll, delay);
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

const softResetStateAction = async () => {
    if (isBusy.value) return;
    if (!window.confirm('Reset state inbox?\n\nSemua percakapan "pending" akan ditandai selesai dan status dikembalikan ke "open". Data pesan tidak dihapus.')) return;
    isBusy.value = true;
    try {
        const d = await callApi('reset-state', { method: 'post' });
        appendLog('Reset state berhasil', d);
        await refreshAll();
    } catch (err) {
        appendLog('Reset state gagal', { error: err?.error });
    } finally {
        isBusy.value = false;
    }
};

const syncContactsAction = async () => {
    if (isBusy.value) return;
    isBusy.value = true;
    try {
        const d = await callApi('sync-contacts', { method: 'post' });
        appendLog(`Sync kontak selesai: ${d?.learned ?? 0} LID dipelajari, ${d?.lidMappings ?? 0} total mapping`, d);
        // Refresh LID map then inbox so names update
        await refreshLidMappings();
        await refreshInboxList();
    } catch (err) {
        appendLog('Sync kontak gagal', { error: err?.error });
    } finally {
        isBusy.value = false;
    }
};

const clearConversationAction = async () => {
    if (isBusy.value || !activeConvoId.value) return;
    if (!window.confirm('Hapus sesi percakapan ini dari inbox lokal?\n\nPesan, mark, dan handover untuk percakapan ini akan dihapus permanen.')) return;

    isBusy.value = true;
    try {
        const d = await callApi('clear-conversation', {
            method: 'post',
            data: { conversation_id: activeConvoId.value },
        });
        appendLog('Sesi percakapan dihapus', d);
        activeConvoId.value = '';
        conversationMessages.value = [];
        await refreshAll();
    } catch (err) {
        appendLog('Gagal menghapus sesi percakapan', { error: err?.error });
    } finally {
        isBusy.value = false;
    }
};

const openReportsPage = () => {
    const fallback = '/wa-caraka/reports';
    try {
        const url = typeof route === 'function' ? route('lawangsewu.wacaraka.reports') : fallback;
        window.location.assign(url || fallback);
    } catch {
        window.location.assign(fallback);
    }
};

const selectConversation = async (convoId) => {
    activeConvoId.value = convoId;
    markEditorOpen.value = false;
    await refreshConvoMessages(convoId);
};

const replyToConversation = async () => {
    const text = replyText.value.trim();
    if (!activeConvoId.value || !text) { replyState.value = 'error'; return; }
    replyState.value = 'sending';
    const tempId = pushTempOutboundMessage(text);
    replyText.value = '';

    try {
        const result = await callApi('reply', { method: 'post', data: { conversation_id: activeConvoId.value, text } });
        markTempMessageStatus(tempId, result?.queued ? 'queued' : 'sent');
        replyState.value = 'sent';
        if (result?.conversation) {
            mergeConversation({
                conversationId: result.conversation.conversationId,
                owner: result.conversation.owner,
                claimedAt: result.conversation.claimedAt,
                status: result.conversation.status,
                ownership: result.conversation.owner?.id === myId.value ? 'mine' : 'locked',
                ownerPresence: 'active',
                justClaimed: true,
                canReply: true,
                lockReason: null,
                unreadCount: activeConvo.value?.unreadCount ?? 0,
                remoteNumber: activeConvo.value?.remoteNumber,
                remoteName: activeConvo.value?.remoteName,
                lastActivityAt: 'baru saja',
                lastActivityTs: Math.floor(Date.now() / 1000),
                pendingHandover: null,
            });
        }
        appendLog(result?.queued ? 'Balasan masuk antrean kirim' : 'Balasan terkirim', { convoId: activeConvoId.value, mode: 'optimistic' });
        refreshStats();
        if (!hasRealtime.value) {
            refreshInboxList();
        }
        scheduleReplyStateReset();
    } catch (err) {
        markTempMessageStatus(tempId, 'failed');
        replyState.value = 'error';
        appendLog('Balasan gagal', { error: err?.error });
        scheduleReplyStateReset(2200);
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
        appendLog('Pesan langsung masuk antrean kirim', { to });
        refreshStats();
        if (!hasRealtime.value) {
            refreshInboxList();
        }
        scheduleSendStateReset();
    } catch (err) {
        sendState.value = 'error';
        appendLog('Kirim langsung gagal', { error: err?.error, to });
        scheduleSendStateReset(2200);
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

const deleteMessageAction = async (messageId) => {
    if (!messageId || !confirm('Yakin ingin menghapus pesan ini? Hapus data bersifat permanen.')) return;
    try {
        await callApi('delete-message', { method: 'post', data: { message_id: messageId } });
        appendLog('Pesan dihapus');
        await refreshConvoMessages();
        await refreshStats();
    } catch (err) {
        appendLog('Gagal menghapus pesan', { error: err?.error });
        alert(err?.error || 'Gagal menghapus pesan.');
    }
};

// ─── Ticket Methods ───────────────────────────────────
const loadTickets = async () => {
    ticketLoading.value = true;
    try {
        const params = { limit: 30 };
        if (ticketFilter.value.type)   params.type   = ticketFilter.value.type;
        if (ticketFilter.value.status) params.status = ticketFilter.value.status;
        tickets.value = await callApi('tickets', { params });
        latestTicketStats.value = await callApi('ticket-stats');
    } catch (err) {
        appendLog('Gagal memuat tiket', { error: err?.error });
    } finally {
        ticketLoading.value = false;
    }
};

const selectTicket = (id) => {
    activeTicketId.value = id;
    const t = tickets.value.find(x => x.id === id);
    ticketReplyText.value = t?.reply || '';
    ticketReplyState.value = 'idle';
    ticketTransferTo.value = '';
};

const saveTicketReply = async () => {
    if (!activeTicketId.value || !ticketReplyText.value.trim()) { ticketReplyState.value = 'error'; return; }
    ticketReplyState.value = 'saving';
    try {
        await callApi('ticket-reply', { method: 'post', data: { ticket_id: activeTicketId.value, reply: ticketReplyText.value.trim() } });
        ticketReplyState.value = 'saved';
        await loadTickets();
        setTimeout(() => ticketReplyState.value = 'idle', 2000);
    } catch (err) {
        ticketReplyState.value = 'error';
        appendLog('Simpan balasan gagal', { error: err?.error });
        setTimeout(() => ticketReplyState.value = 'idle', 2000);
    }
};

const sendTicketReply = async () => {
    if (!activeTicketId.value) return;
    ticketReplyState.value = 'sending';
    try {
        await callApi('ticket-send', { method: 'post', data: { ticket_id: activeTicketId.value } });
        ticketReplyState.value = 'sent';
        appendLog('Balasan tiket terkirim ke WA', { id: activeTicketId.value });
        await loadTickets();
        activeTicketId.value = null;
        setTimeout(() => ticketReplyState.value = 'idle', 2000);
    } catch (err) {
        ticketReplyState.value = 'error';
        appendLog('Kirim balasan gagal', { error: err?.error });
        setTimeout(() => ticketReplyState.value = 'idle', 2000);
    }
};

const transferTicketAction = async () => {
    if (!activeTicketId.value || !ticketTransferTo.value) return;
    try {
        await callApi('ticket-transfer', { method: 'post', data: { ticket_id: activeTicketId.value, new_type: ticketTransferTo.value } });
        appendLog(`Tiket dipindah ke ${ticketTransferTo.value}`);
        ticketTransferTo.value = '';
        await loadTickets();
        activeTicketId.value = null;
    } catch (err) { appendLog('Transfer gagal', { error: err?.error }); }
};

const classifyTicketType = async (newType) => {
    if (!activeTicketId.value || !newType) return;
    try {
        await callApi('ticket-transfer', {
            method: 'post',
            data: { ticket_id: activeTicketId.value, new_type: newType },
        });
        appendLog(`Tipe tiket diubah ke ${newType}`);
        const selectedId = activeTicketId.value;
        await loadTickets();
        if (selectedId) selectTicket(selectedId);
    } catch (err) {
        appendLog('Gagal ubah tipe tiket', { error: err?.error });
    }
};

const closeTicketAction = async () => {
    if (!activeTicketId.value) return;
    try {
        await callApi('ticket-close', { method: 'post', data: { ticket_id: activeTicketId.value } });
        appendLog('Tiket ditutup');
        await loadTickets();
        activeTicketId.value = null;
    } catch (err) { appendLog('Tutup tiket gagal', { error: err?.error }); }
};

const ticketTypeLabel = (type) => ({ pengaduan: '📢 Pengaduan', konsultasi: '💬 Konsultasi', umum: '📩 Umum', live_konsul: '📞 Live' }[type] || 'Belum ditentukan');
const ticketStatusClass = (s) => ({ open: 'bg-amber-100 text-amber-700', replied: 'bg-blue-100 text-blue-700', sent: 'bg-emerald-100 text-emerald-700', closed: 'bg-slate-100 text-slate-500' }[s] || 'bg-slate-100 text-slate-400');

// ─── Realtime ─────────────────────────────────────────
const connectRealtime = () => {
    const echo = window.Echo;
    if (!echo) { appendLog('Realtime: Reverb tidak aktif. Gunakan polling.'); return; }
    echo.private('lawangsewu.wacaraka.inbox')
        .listen('.wa-caraka.message.received', async (event) => {
            appendLog('Pesan masuk (Reverb)', event?.message?.remoteNumber && { from: event.message.remoteNumber });
            if (event?.message?.conversationId === activeConvoId.value) {
                upsertConversationMessage(event.message);
            }
            refreshInboxList();
            refreshStats();
        })
        .listen('.wa-caraka.message.synced', (event) => {
            const message = event?.message;
            if (!message) return;

            if (!activeConvoId.value || message.conversationId === activeConvoId.value) {
                upsertConversationMessage(message);
            }

            mergeConversation({
                conversationId: message.conversationId,
                remoteNumber: message.remoteNumber,
                lastActivityAt: 'baru saja',
                unreadCount: activeConvo.value?.conversationId === message.conversationId ? 0 : undefined,
            });
            refreshStats();
        })
        .listen('.wa-caraka.conversation.updated', (event) => {
            if (!event?.conversation) return;
            mergeConversation(event.conversation);
        });
};

const setAutoRefresh = (val) => {
    autoRefresh.value = val;
    if (pollRef.value) { clearTimeout(pollRef.value); pollRef.value = null; }
    if (val) scheduleNextRefresh();
};

// Watch active convo — refresh messages on change
watch(activeConvoId, (id) => {
    if (id) {
        refreshConvoMessages(id);
    }
    syncMarkFormFromActive();
});

watch(() => activeConvo.value?.customerMark, () => {
    syncMarkFormFromActive();
}, { deep: true });

watch([threadZoom, threadVisibleCount], ([zoom, count]) => {
    if (typeof window === 'undefined') return;
    localStorage.setItem('wacaraka.thread.zoom', String(zoom));
    localStorage.setItem('wacaraka.thread.visibleCount', String(count));
});

const fetchHandoverStatus = async () => {
    try {
        const res = await callApi('get-handover-enabled');
        handoverEnabled.value = res.handoverEnabled ?? true;
    } catch (e) {
        console.warn('[WaCaraka] Failed to fetch handover status:', e);
    }
};

const toggleHandoverEnabled = async () => {
    if (!isSuperAdmin.value || handoverToggling.value) return;
    
    handoverToggling.value = true;
    try {
        const newState = !handoverEnabled.value;
        await callApi('toggle-handover-enabled', {
            method: 'post',
            data: { enabled: newState },
        });
        handoverEnabled.value = newState;
        appendLog('Fitur Alih Chat', { enabled: newState });
    } catch (e) {
        console.error('[WaCaraka] Failed to toggle handover:', e);
        appendLog('❌ Toggle Alih Chat', { error: e.error || e.message });
    } finally {
        handoverToggling.value = false;
    }
};

onMounted(async () => {
    if (typeof window !== 'undefined') {
        setThreadZoom(localStorage.getItem('wacaraka.thread.zoom') ?? threadZoom.value);
        setThreadVisibleCount(localStorage.getItem('wacaraka.thread.visibleCount') ?? threadVisibleCount.value);
    }

    await fetchHandoverStatus();
    await refreshAll();
    await loadTickets();
    setAutoRefresh(true);
    connectRealtime();
});

onUnmounted(() => {
    if (pollRef.value) clearTimeout(pollRef.value);
    if (replyCooldownRef.value) clearTimeout(replyCooldownRef.value);
    if (sendCooldownRef.value) clearTimeout(sendCooldownRef.value);
    stopQrPolling();
    window.Echo?.leave('lawangsewu.wacaraka.inbox');
});
</script>

<template>
    <Head title="WA Live PTSP" />

    <LawangsewuLayout current-route="wacaraka" :nav-groups="navGroups" :app-meta="appMeta">

        <!-- ░░ Operator Desk Banner ░░ -->
        <section class="relative overflow-hidden rounded-3xl border border-[var(--accent-border)] bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.15),transparent_36%),linear-gradient(145deg,rgba(5,10,23,0.96),rgba(15,23,42,0.95))] p-4 text-white shadow-[0_20px_60px_rgba(2,6,23,0.45)] lg:px-6 lg:py-4 mb-6">
            <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-sky-500/10 blur-3xl pointer-events-none" />
            <div class="absolute -bottom-12 left-1/3 h-44 w-44 rounded-full bg-cyan-400/8 blur-3xl pointer-events-none" />

            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <p class="text-[10px] font-black uppercase tracking-[0.3em] text-sky-300/90">WA Live PTSP • Operator Desk</p>
                
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[11px] font-bold transition" :class="statusClass">
                        <span class="h-2 w-2 rounded-full bg-current animate-pulse"></span>
                        {{ statusText }}
                    </span>
                    <button v-if="isSuperAdmin" @click="toggleHandoverEnabled" :disabled="handoverToggling" class="rounded-full border px-3 py-1 text-[11px] font-bold transition" :class="handoverEnabled ? 'border-emerald-400/40 bg-emerald-400/10 text-emerald-200 hover:bg-emerald-400/20' : 'border-rose-400/40 bg-rose-400/10 text-rose-200 hover:bg-rose-400/20'" :title="handoverEnabled ? 'Fitur alih chat sedang aktif' : 'Fitur alih chat sedang dinonaktifkan'">
                        {{ handoverToggling ? '⏳' : (handoverEnabled ? '✓' : '✕') }} Alih Chat
                    </button>
                    <button v-if="isAdmin" @click="openReportsPage" class="rounded-full border border-violet-300/40 bg-violet-300/10 px-3 py-1 text-[11px] font-bold text-violet-100 hover:bg-violet-300/20 transition">
                        📊 Laporan
                    </button>
                    <button @click="refreshAll" :disabled="isLoading" class="rounded-full border border-sky-400/40 bg-sky-400/10 px-3 py-1 text-[11px] font-bold text-sky-200 hover:bg-sky-400/20 transition disabled:opacity-40">
                        {{ isLoading ? 'Memuat...' : '↻ Refresh' }}
                    </button>
                </div>
            </div>

            <div class="relative z-10 mt-3.5 grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-7">
                <article v-for="card in [
                    { label: 'Percakapan', value: latestConvoStats.total, color: 'text-sky-300' },
                    { label: 'Aktif', value: latestConvoStats.open, color: 'text-emerald-300' },
                    { label: 'Pending', value: latestConvoStats.pending, color: 'text-amber-300' },
                    { label: 'Selesai', value: latestConvoStats.closed, color: 'text-slate-300' },
                    { label: 'Pesan Masuk', value: latestMsgStats.todayInbound, color: 'text-cyan-300' },
                    { label: 'Belum Dibalas', value: latestMsgStats.unreplied, color: latestMsgStats.unreplied > 0 ? 'text-rose-300' : 'text-emerald-300' },
                    { label: 'Pending Handover', value: latestConvoStats.pendingHandovers, color: latestConvoStats.pendingHandovers > 0 ? 'text-orange-300' : 'text-slate-400' },
                ]" :key="`operator-summary-${card.label}`" class="rounded-xl border border-white/10 bg-white/5 px-3 py-2.5 backdrop-blur transition hover:bg-white/10">
                    <p class="text-[9px] font-bold uppercase tracking-[0.15em] text-slate-400">{{ card.label }}</p>
                    <p class="mt-0.5 text-lg font-black leading-none" :class="card.color">{{ card.value ?? 0 }}</p>
                </article>
            </div>
        </section>

        <!-- ░░ Main: Inbox + Thread ░░ -->
        <section class="grid min-w-0 grid-cols-[minmax(150px,42%),minmax(0,1fr)] gap-3 sm:grid-cols-[minmax(180px,36%),minmax(0,1fr)] sm:gap-4 xl:grid-cols-[clamp(280px,28%,360px),minmax(0,1fr)] xl:gap-6">

            <!-- Sidebar: Conversation List -->
            <aside class="flex min-w-0 flex-col rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] shadow-[var(--shadow)] max-h-[calc(100vh-4rem)] xl:sticky xl:top-4 xl:rounded-[2rem]">
                <div class="flex items-center justify-between gap-2 border-b border-[var(--border)] px-3 py-3 flex-shrink-0 sm:px-4 sm:py-4 xl:px-5">
                    <h2 class="text-sm font-black text-[var(--text-1)] sm:text-base">Inbox</h2>
                    <div class="flex items-center gap-2">
                        <button @click="pullInbox().then(refreshInboxList)" :disabled="isLoading"
                                class="rounded-xl border border-[var(--border)] px-2 py-1 text-[9px] font-bold text-[var(--text-2)] hover:border-sky-400/50 hover:text-sky-400 transition sm:px-2.5 sm:text-[10px]">
                            Pull
                        </button>
                        <span class="rounded-full bg-[var(--surface-2)] px-2 py-0.5 text-[9px] font-bold text-[var(--text-2)] sm:px-2.5 sm:text-[10px]">{{ conversations.length }}</span>
                    </div>
                </div>

                <p class="px-3 py-2 text-[9px] text-[var(--text-2)] flex-shrink-0 sm:px-4 sm:text-[10px] xl:px-5">{{ inboxSyncText }}</p>

                <div class="flex-1 min-h-0 overflow-y-auto divide-y divide-[var(--border)]">
                    <button v-for="c in conversations" :key="c.conversationId"
                            @click="selectConversation(c.conversationId)"
                            class="group w-full px-2.5 py-2.5 text-left transition sm:px-3 sm:py-3 xl:px-4 xl:py-3.5"
                            :class="activeConvoId === c.conversationId ? 'bg-sky-500/10 border-l-2 border-sky-400' : 'hover:bg-[var(--surface-2)] border-l-2 border-transparent'">
                        <div class="flex items-start gap-2 sm:gap-3">
                            <div class="mt-0.5 h-8 w-8 flex-shrink-0 overflow-hidden rounded-full ring-1 ring-white/40 sm:h-9 sm:w-9 xl:h-10 xl:w-10">
                                <img
                                    v-if="c.profilePhotoUrl"
                                    :src="c.profilePhotoUrl"
                                    alt="Foto profil WA"
                                    class="h-full w-full object-cover"
                                    referrerpolicy="no-referrer"
                                    @error="onProfileImageError(c.conversationId)"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-[10px] font-black text-white sm:text-[11px] xl:text-xs"
                                     :class="avatarToneClassFor(c.conversationId || c.remoteNumber)">
                                    {{ initialsFromName(c.displayTitle) }}
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-xs font-bold text-[var(--text-1)] sm:text-[13px] xl:text-sm">
                                        <span v-if="c.isGroup" class="mr-1">👥</span>{{ c.displayTitle }}
                                    </p>
                                    <!-- Unread badge -->
                                    <span v-if="c.unreadCount > 0" class="flex-shrink-0 rounded-full bg-rose-500 px-1.5 py-0.5 text-[9px] font-black text-white sm:px-2 sm:text-[10px]">
                                        {{ c.unreadCount }}
                                    </span>
                                </div>

                                <p class="mt-0.5 text-[9px] text-[var(--text-2)] font-mono sm:text-[10px]">{{ primaryContactNumber(c.remoteNumber) }}</p>
                                <p v-if="c.groupName && c.isGroup" class="mt-0.5 text-[9px] text-[var(--text-2)] sm:text-[10px]">
                                    Nama Group: <span class="font-semibold">{{ c.groupName }}</span>
                                </p>
                                <p v-if="c.remoteName && !c.isGroup" class="mt-0.5 text-[9px] text-[var(--text-2)] sm:text-[10px]">
                                    Nama WA: <span class="font-semibold">{{ c.remoteName }}</span>
                                </p>

                                <div class="mt-1.5 flex flex-wrap items-center gap-1">
                                    <span class="rounded-full px-1.5 py-0.5 text-[9px] font-bold sm:px-2 sm:text-[10px]"
                                          :class="{
                                              'bg-emerald-500/15 text-emerald-600': c.status === 'open',
                                              'bg-amber-500/15 text-amber-600': c.status === 'pending',
                                              'bg-slate-500/15 text-slate-500': c.status === 'closed',
                                          }">
                                        {{ c.status === 'pending' ? 'belum dibaca' : c.status }}
                                    </span>
                                    <span v-if="c.customerMark" class="rounded-full border px-1.5 py-0.5 text-[9px] font-bold sm:px-2 sm:text-[10px]"
                                          :class="markToneClass(c.customerMark.tone)">
                                        {{ c.customerMark.label }}
                                    </span>
                                    <span v-if="c.ownership === 'mine'" class="rounded-full bg-sky-500/12 px-1.5 py-0.5 text-[9px] font-bold text-sky-600 sm:px-2 sm:text-[10px]">
                                        aktif kamu
                                    </span>
                                    <span v-if="c.owner" class="rounded-full bg-blue-500/12 px-1.5 py-0.5 text-[9px] font-semibold text-blue-600 sm:px-2 sm:text-[10px]">
                                        {{ c.owner.alias || c.owner.name }}
                                    </span>
                                    <span v-if="c.justClaimed" class="rounded-full bg-violet-500/12 px-1.5 py-0.5 text-[9px] font-bold text-violet-600 sm:px-2 sm:text-[10px]">
                                        baru takeover
                                    </span>
                                    <span v-if="c.ownerPresence === 'active'" class="rounded-full bg-emerald-500/12 px-1.5 py-0.5 text-[9px] font-bold text-emerald-600 sm:px-2 sm:text-[10px]">
                                        aktif sekarang
                                    </span>
                                    <span v-else-if="c.ownerPresence === 'standby'" class="rounded-full bg-sky-500/12 px-1.5 py-0.5 text-[9px] font-bold text-sky-600 sm:px-2 sm:text-[10px]">
                                        standby
                                    </span>
                                    <span v-else-if="c.ownerPresence === 'idle'" class="rounded-full bg-slate-500/12 px-1.5 py-0.5 text-[9px] font-bold text-slate-500 sm:px-2 sm:text-[10px]">
                                        idle
                                    </span>
                                    <span v-if="c.pendingHandover" class="rounded-full bg-orange-500/15 px-1.5 py-0.5 text-[9px] font-bold text-orange-600 sm:px-2 sm:text-[10px]">
                                        handover ⏳
                                    </span>
                                </div>

                                <p class="mt-1 text-[9px] text-[var(--text-2)] sm:mt-1.5 sm:text-[10px]">
                                    {{ c.lastActivityAt || '—' }}
                                    <span v-if="c.claimedAt" class="ml-1 text-[var(--text-2)]/80">· diklaim {{ c.claimedAt }}</span>
                                </p>
                            </div>
                        </div>
                    </button>

                    <div v-if="conversations.length === 0" class="px-5 py-10 text-center">
                        <p class="text-sm text-[var(--text-2)]">Belum ada percakapan.</p>
                        <p class="mt-1 text-xs text-[var(--text-2)]">Pesan akan muncul saat runtime mengirim webhook atau pull inbox berhasil.</p>
                    </div>
                </div>

            </aside>

            <!-- Main: Thread + Reply -->
            <div class="min-w-0 flex flex-col gap-3 sm:gap-4">

                <!-- Thread Header -->
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-3 shadow-[var(--shadow)] sm:p-4 xl:rounded-[2rem] xl:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <div v-if="activeConvo" class="mt-0.5 h-9 w-9 flex-shrink-0 overflow-hidden rounded-full ring-1 ring-white/40 sm:h-10 sm:w-10 xl:h-12 xl:w-12">
                                <img
                                    v-if="activeConvo.profilePhotoUrl"
                                    :src="activeConvo.profilePhotoUrl"
                                    alt="Foto profil WA"
                                    class="h-full w-full object-cover"
                                    referrerpolicy="no-referrer"
                                    @error="onProfileImageError(activeConvo.conversationId)"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-xs font-black text-white xl:text-sm"
                                     :class="avatarToneClassFor(activeConvo.conversationId || activeConvo.remoteNumber)">
                                    {{ initialsFromName(activeConvo?.displayTitle || activeConvo?.remoteName || activeConvo?.remoteNumber) }}
                                </div>
                            </div>

                            <div class="min-w-0">
                            <h2 class="truncate text-base font-black text-[var(--text-1)] sm:text-lg xl:text-xl">
                                <span v-if="activeConvo?.isGroup" class="mr-1">👥</span>{{ activeConvo?.displayTitle || 'Pilih Percakapan' }}
                            </h2>
                            <p v-if="activeConvo?.remoteNumber" class="text-[10px] font-mono text-[var(--text-2)] sm:text-xs">
                                {{ primaryContactNumber(activeConvo.remoteNumber) }}
                                <span v-if="isLidNumber(activeConvo.remoteNumber) && !getMappedWaNumber(activeConvo.remoteNumber)" class="ml-1 text-[10px] text-amber-600 font-bold">(Legacy WhatsApp)</span>
                            </p>
                            <p v-if="activeConvo?.groupName && activeConvo?.isGroup" class="text-[10px] text-[var(--text-2)] sm:text-xs">
                                Nama Group: <span class="font-semibold text-[var(--text-1)]">{{ activeConvo.groupName }}</span>
                            </p>
                            <p v-if="activeConvo?.remoteName && !activeConvo?.isGroup" class="text-[10px] text-[var(--text-2)] sm:text-xs">
                                Nama WA: <span class="font-semibold text-[var(--text-1)]">{{ activeConvo.remoteName }}</span>
                            </p>
                            <p v-if="activeConvo?.isGroup && groupParticipantCount" class="mt-1 text-[10px] font-semibold text-violet-600 sm:text-[11px]">
                                {{ groupParticipantCount }} anggota terdeteksi di thread ini
                            </p>
                            <div v-if="activeConvo" class="mt-2 flex flex-wrap items-center gap-1.5 sm:gap-2">
                                <span v-if="activeConvo.isGroup" class="rounded-full border border-violet-200 bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700 sm:px-2.5 sm:text-xs">
                                    Grup
                                </span>
                                <span v-if="activeCustomerMark" class="rounded-full border px-2 py-1 text-[10px] font-bold sm:px-2.5 sm:text-xs" :class="markToneClass(activeCustomerMark.tone)">
                                    {{ activeCustomerMark.label }}
                                </span>
                                <!-- Status -->
                                <span class="rounded-full px-2 py-1 text-[10px] font-bold sm:px-2.5 sm:text-xs"
                                      :class="{
                                          'bg-emerald-100 text-emerald-700': activeConvo.status === 'open',
                                          'bg-amber-100 text-amber-700': activeConvo.status === 'pending',
                                          'bg-slate-100 text-slate-500': activeConvo.status === 'closed',
                                      }">
                                    {{ activeConvo.status }}
                                </span>
                                <!-- Owner -->
                                <span v-if="activeConvo.owner" class="rounded-full border border-blue-200 bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700 sm:px-2.5 sm:text-xs">
                                    Ditangani: {{ activeConvo.owner.alias || activeConvo.owner.name }}
                                    <span v-if="iMineConvo" class="ml-1 text-blue-400">(kamu)</span>
                                </span>
                                <span v-else class="rounded-full border border-dashed border-slate-300 px-2 py-1 text-[10px] text-slate-500 sm:px-2.5 sm:text-xs">Belum ada petugas</span>
                                <span v-if="activeConvo.claimedAt" class="rounded-full border border-slate-200 bg-white px-2 py-1 text-[10px] font-semibold text-slate-500 sm:px-2.5 sm:text-xs">
                                    dikunci {{ activeConvo.claimedAt }}
                                </span>
                                <span v-if="activeConvo.justClaimed" class="rounded-full border border-violet-200 bg-violet-50 px-2 py-1 text-[10px] font-bold text-violet-700 sm:px-2.5 sm:text-xs">
                                    takeover baru
                                </span>
                                <span v-if="ownerPresenceLabel" class="rounded-full border border-emerald-200 bg-emerald-50 px-2 py-1 text-[10px] font-bold text-emerald-700 sm:px-2.5 sm:text-xs">
                                    {{ ownerPresenceLabel }}
                                </span>
                                <span class="rounded-full px-2 py-1 text-[10px] font-bold sm:px-2.5 sm:text-xs"
                                      :class="conversationLockState === 'mine'
                                          ? 'border border-sky-200 bg-sky-50 text-sky-700'
                                          : conversationLockState === 'locked'
                                              ? 'border border-rose-200 bg-rose-50 text-rose-700'
                                              : 'border border-emerald-200 bg-emerald-50 text-emerald-700'">
                                    {{ conversationLockState === 'mine' ? 'mode operator aktif' : conversationLockState === 'locked' ? 'aktif operator lain' : 'belum dibalas' }}
                                </span>
                            </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div v-if="activeConvo" class="flex flex-wrap gap-1.5 sm:gap-2">
                            <button @click="refreshConvoMessages()" class="rounded-xl border border-[var(--border)] px-2 py-1.5 text-[10px] text-[var(--text-2)] hover:border-sky-400/50 transition sm:px-3 sm:text-xs">↻ Muat ulang</button>

                            <!-- Close (owner or admin) -->
                            <button v-if="(iMineConvo || isAdmin) && activeConvo.status !== 'closed'"
                                    @click="closeConvo"
                                    class="rounded-xl border border-slate-200 px-2 py-1.5 text-[10px] text-slate-600 hover:bg-slate-100 transition sm:px-3 sm:text-xs">
                                ✓ Selesaikan
                            </button>



                            <!-- Force takeover (admin) -->
                            <button v-if="isAdmin && !iMineConvo && activeConvo.owner"
                                    @click="forceHandover(activeConvo.conversationId)"
                                    class="rounded-xl border border-rose-200 bg-rose-50 px-2 py-1.5 text-[10px] text-rose-700 hover:bg-rose-100 transition sm:px-3 sm:text-xs">
                                ⚡ Ambil Alih (Admin)
                            </button>

                            <button @click="toggleMarkEditor"
                                    class="rounded-xl border border-violet-200 bg-violet-50 px-2 py-1.5 text-[10px] text-violet-700 hover:bg-violet-100 transition sm:px-3 sm:text-xs">
                                🏷 Atur Alias
                            </button>

                            <button v-if="isSuperAdmin" @click="clearConversationAction"
                                    class="rounded-xl border border-rose-300 bg-rose-50 px-2 py-1.5 text-[10px] text-rose-700 hover:bg-rose-100 transition sm:px-3 sm:text-xs">
                                🗑 Hapus Sesi
                            </button>
                        </div>
                    </div>

                    <div v-if="activeConvo && markEditorOpen" class="mt-4 rounded-2xl border border-violet-200 bg-violet-50/60 p-4">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-violet-700">Alias Customer (Personal per Operator)</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <input v-model="markForm.label" @input="markDraftDirty = true" type="text" maxlength="40" placeholder="Contoh: Pak Budi PTSP"
                                   class="rounded-xl border border-violet-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-violet-400" />
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="tone in markToneOptions" :key="tone.value" type="button"
                                        @click="markForm.tone = tone.value; markDraftDirty = true"
                                        class="rounded-xl border px-2 py-2 text-[11px] font-black tracking-wide transition"
                                        :class="markTonePickerClass(tone.value)">
                                    {{ tone.label }}
                                </button>
                            </div>
                        </div>
                        <textarea v-model="markForm.note" @input="markDraftDirty = true" rows="2" maxlength="255" placeholder="Catatan internal singkat..."
                                  class="mt-3 w-full resize-none rounded-xl border border-violet-200 bg-white px-3 py-2 text-sm text-slate-800 outline-none focus:border-violet-400" />
                        <label class="mt-2 inline-flex items-center gap-2 text-xs font-semibold text-violet-700">
                            <input v-model="markForm.isPinned" @change="markDraftDirty = true" type="checkbox" class="rounded border-violet-300 text-violet-600" />
                            Pin conversation ini di daftar inbox saya
                        </label>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <button @click="saveCustomerMark" :disabled="markState === 'saving'"
                                    class="rounded-xl bg-violet-600 px-4 py-2 text-xs font-black text-white hover:bg-violet-700 transition disabled:opacity-40">
                                {{ markState === 'saving' ? 'Menyimpan...' : 'Simpan Alias' }}
                            </button>
                            <button @click="clearCustomerMark" :disabled="markState === 'saving' || !activeCustomerMark"
                                    class="rounded-xl border border-rose-200 bg-white px-4 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 transition disabled:opacity-40">
                                Hapus Alias
                            </button>
                            <span v-if="markState === 'saved'" class="inline-flex items-center rounded-xl bg-emerald-100 px-3 py-2 text-xs font-bold text-emerald-700">✓ Tersimpan</span>
                            <span v-if="markState === 'error'" class="inline-flex items-center rounded-xl bg-rose-100 px-3 py-2 text-xs font-bold text-rose-700">✕ Cek input</span>
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
                     class="flex-1 overflow-y-auto rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-2)] p-3 shadow-[var(--shadow)] scroll-smooth sm:p-4 xl:rounded-[2rem] xl:p-5"
                     :class="activeConvo && !customBackgroundStyle ? 'thread-surface' : ''"
                     :style="[threadViewportStyle, activeConvo && customBackgroundStyle ? customBackgroundStyle : {}]">

                    <div class="mb-3 flex flex-wrap items-center gap-2 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-3 py-2 sm:mb-4 sm:gap-3 sm:py-2.5">
                        <p class="text-[9px] font-bold uppercase tracking-[0.16em] text-[var(--text-2)] sm:text-[10px]">Tampilan Chat</p>
                        <div class="ml-auto flex items-center gap-2">
                            <button @click="adjustThreadZoom(-5)" class="rounded-lg border border-[var(--border)] px-2 py-1 text-[10px] font-bold text-[var(--text-2)] hover:border-sky-400/50 hover:text-sky-500 transition sm:text-xs">
                                A-
                            </button>
                            <span class="w-[44px] text-center text-[10px] font-bold text-[var(--text-1)] sm:w-[52px] sm:text-xs">{{ threadZoom }}%</span>
                            <button @click="adjustThreadZoom(5)" class="rounded-lg border border-[var(--border)] px-2 py-1 text-[10px] font-bold text-[var(--text-2)] hover:border-sky-400/50 hover:text-sky-500 transition sm:text-xs">
                                A+
                            </button>
                        </div>

                        <label class="flex items-center gap-2 text-[10px] text-[var(--text-2)] sm:text-xs">
                            <span>Target pesan terlihat:</span>
                            <input type="range"
                                   min="6"
                                   max="24"
                                   step="1"
                                   :value="threadVisibleCount"
                                   @input="setThreadVisibleCount($event.target.value)"
                                   class="w-20 accent-sky-500 sm:w-28" />
                            <span class="w-6 text-right font-bold text-[var(--text-1)] sm:w-7">{{ threadVisibleCount }}</span>
                        </label>
                    </div>

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

                    <div v-else :class="threadGapClass" :style="threadMessageScaleStyle">
                        <div v-for="msg in conversationMessages" :key="msg.id || msg._tempId"
                             class="flex"
                             :class="bubbleWrapClass(msg)">

                            <div v-if="msg.direction === 'inbound'" class="mt-1 mr-2 flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-black text-white"
                                 :class="msg.avatarToneClass">
                                {{ msg.senderInitials }}
                            </div>

                            <article class="max-w-[92%] rounded-2xl px-3 py-2.5 shadow-sm transition-all duration-200 sm:max-w-[86%] sm:px-4 sm:py-3 xl:max-w-[78%]"
                                     :class="bubbleCardClass(msg)">

                                <div class="mb-1.5 flex items-center justify-between gap-4 text-[10px] font-semibold"
                                     :class="bubbleMetaClass(msg)">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span v-if="msg.isGroup && msg.direction === 'inbound'" class="font-bold" :class="msg.senderColorClass">
                                            {{ msg.senderDisplay }}
                                        </span>
                                        <span v-else>{{ msg.direction === 'outbound' ? (msg.senderDisplay || msg.operator || 'Anda') : (msg.senderDisplay || 'Kontak') }}</span>
                                        <span v-if="msg.isGroup" class="rounded-full bg-slate-200/70 px-1.5 py-[1px] text-[9px] font-black uppercase tracking-wide text-slate-600">grup</span>
                                    </span>
                                    <span class="whitespace-nowrap">{{ msg.sentAt }}</span>
                                </div>

                                <div v-if="msg.hasVisualMedia" class="space-y-2">
                                    <img
                                        :src="msg.mediaUrl"
                                        :alt="msg.mediaKind === 'sticker' ? 'Sticker WhatsApp' : 'Media WhatsApp'"
                                        class="max-h-80 w-auto rounded-xl border border-slate-200/80 object-cover shadow-sm"
                                        :class="msg.mediaKind === 'sticker' ? 'h-28 w-28 object-contain border-0 bg-transparent shadow-none' : ''"
                                    />
                                    <p v-if="msg.mediaCaption" class="text-xs leading-relaxed whitespace-pre-wrap sm:text-sm">{{ msg.mediaCaption }}</p>
                                </div>
                                <p v-else class="text-xs leading-relaxed whitespace-pre-wrap sm:text-sm">{{ msg.text || (msg.mediaKind ? `[${msg.mediaKind}]` : '') || '[pesan kosong]' }}</p>

                                <div class="mt-1.5 flex items-center justify-between gap-2 text-[10px]"
                                     :class="bubbleFooterClass(msg)">
                                    <span>{{ messageTypeLabel(msg.type, msg) }}</span>
                                    <div class="flex items-center gap-2">
                                        <button v-if="isAdmin && msg.id" @click="deleteMessageAction(msg.id)"
                                            class="text-rose-500/80 hover:text-rose-500 transition" title="Hapus pesan (Admin/Superadmin)">
                                            ✕
                                        </button>
                                        <span :class="outgoingStatusClass(msg)">
                                            <span v-if="msg.direction === 'outbound' && outgoingTickIcon(msg.status)" class="mr-1 font-black" :class="outgoingTickClass(msg.status)">
                                                {{ outgoingTickIcon(msg.status) }}
                                            </span>
                                            {{ msg.status === 'queued' ? 'queued' : msg.status }}
                                            <span v-if="msg.repliedAt" class="ml-1">· dibalas {{ msg.repliedAt }}</span>
                                        </span>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>

                <!-- Reply Box -->
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-3 shadow-[var(--shadow)] sm:p-4 xl:rounded-[2rem] xl:p-5">
                    <div class="flex flex-col gap-2 sm:gap-3 xl:flex-row">
                        <textarea v-model="replyText"
                                  rows="3"
                                  :disabled="!activeConvo || !canReply"
                                  :placeholder="!activeConvo ? 'Pilih percakapan' : !canReply ? 'Tidak diizinkan membalas' : `Balas ke ${activeConvo?.displayTitle}...` "
                                  class="flex-1 resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-xs text-[var(--text-1)] outline-none transition placeholder:text-[var(--text-2)] focus:border-sky-400/60 disabled:opacity-50 disabled:cursor-not-allowed sm:px-4 sm:py-3 sm:text-sm"
                                  @keydown.ctrl.enter="replyToConversation" />

                        <div class="flex flex-col gap-2 xl:w-auto">
                            <button @click="replyToConversation"
                                    :disabled="replyState === 'sending' || !activeConvo || !canReply"
                                    class="send-btn flex-1 min-w-[88px] rounded-2xl px-4 py-2.5 text-xs font-black text-white shadow-md transition disabled:opacity-40 disabled:cursor-not-allowed sm:min-w-[110px] sm:px-5 sm:py-3 sm:text-sm"
                                    :class="replyState === 'sending' ? 'send-btn-sending' : ''">
                                <span class="inline-flex items-center justify-center gap-1.5">
                                    <span v-if="replyState === 'sending'" class="send-dot-loader" aria-hidden="true"></span>
                                    <span>{{ replyState === 'sending' ? 'Kirim cepat' : replyState === 'sent' ? '✓ Masuk antrean' : replyState === 'error' ? '✕ Gagal' : '↑ Kirim' }}</span>
                                </span>
                            </button>
                            <p class="text-center text-[9px] text-[var(--text-2)]">Ctrl+Enter</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>



        <!-- ░░ Tiket: Pengaduan & Konsultasi ░░ -->
        <section class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] shadow-[var(--shadow)] overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[var(--border)] px-6 py-4">
                <h2 class="text-lg font-black text-[var(--text-1)]">Tiket Masuk <span class="ml-2 text-sm font-normal text-[var(--text-2)]">Pengaduan &amp; Konsultasi</span></h2>
                <div class="flex flex-wrap items-center gap-2">
                    <span v-for="s in [
                        { label: 'Open', val: latestTicketStats.open, cls: 'bg-amber-100 text-amber-700' },
                        { label: 'Replied', val: latestTicketStats.replied, cls: 'bg-blue-100 text-blue-700' },
                        { label: 'Terkirim', val: latestTicketStats.sent, cls: 'bg-emerald-100 text-emerald-700' },
                        { label: 'Closed', val: latestTicketStats.closed, cls: 'bg-slate-100 text-slate-500' },
                    ]" :key="s.label" class="rounded-full px-2.5 py-0.5 text-xs font-bold" :class="s.cls">
                        {{ s.label }}: {{ s.val ?? 0 }}
                    </span>
                    <button @click="loadTickets" :disabled="ticketLoading"
                            class="rounded-xl border border-[var(--border)] px-3 py-1.5 text-xs font-bold text-[var(--text-2)] hover:border-sky-400/50 transition disabled:opacity-40">
                        {{ ticketLoading ? '...' : '↻ Muat' }}
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="flex flex-wrap gap-3 border-b border-[var(--border)] bg-[var(--surface-2)] px-6 py-3">
                <select v-model="ticketFilter.type" @change="loadTickets"
                        class="rounded-xl border border-[var(--border)] bg-[var(--surface-1)] px-3 py-1.5 text-xs font-semibold text-[var(--text-1)] outline-none">
                    <option value="">Semua Tipe</option>
                    <option value="pengaduan">📢 Pengaduan</option>
                    <option value="konsultasi">💬 Konsultasi</option>
                    <option value="umum">📩 Umum</option>
                </select>
                <select v-model="ticketFilter.status" @change="loadTickets"
                        class="rounded-xl border border-[var(--border)] bg-[var(--surface-1)] px-3 py-1.5 text-xs font-semibold text-[var(--text-1)] outline-none">
                    <option value="">Semua Status</option>
                    <option value="open">Open</option>
                    <option value="replied">Replied</option>
                    <option value="sent">Terkirim</option>
                    <option value="closed">Closed</option>
                </select>
            </div>

            <div class="grid xl:grid-cols-[clamp(260px,30%,360px),minmax(0,1fr)]">
                <!-- Ticket list -->
                <div class="border-r border-[var(--border)] max-h-[480px] overflow-y-auto divide-y divide-[var(--border)]">
                    <button v-for="t in tickets" :key="t.id"
                            @click="selectTicket(t.id)"
                            class="w-full px-4 py-3.5 text-left transition"
                            :class="activeTicketId === t.id ? 'bg-sky-500/10 border-l-2 border-sky-400' : 'hover:bg-[var(--surface-2)] border-l-2 border-transparent'">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-black">{{ ticketTypeLabel(t.type) }}</span>
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" :class="ticketStatusClass(t.status)">{{ t.status }}</span>
                        </div>
                        <p v-if="!normalizeTicketType(t.type)" class="mt-1 text-[10px] font-bold text-rose-500">Belum diklasifikasikan</p>
                        <p class="mt-1 text-xs font-mono text-[var(--text-2)] truncate">{{ t.remoteNumber }}</p>
                        <p class="mt-1 text-xs text-[var(--text-1)] line-clamp-2">{{ t.message }}</p>
                        <p class="mt-1.5 text-[10px] text-[var(--text-2)]">{{ t.createdAt }}</p>
                    </button>
                    <div v-if="tickets.length === 0" class="px-5 py-10 text-center">
                        <p class="text-2xl mb-2">🎉</p>
                        <p class="text-sm text-[var(--text-2)]">Tidak ada tiket{{ ticketFilter.status ? ` dengan status "${ticketFilter.status}"` : '' }}.</p>
                    </div>
                </div>

                <!-- Ticket detail -->
                <div class="p-6">
                    <div v-if="!activeTicket" class="grid min-h-[280px] place-items-center text-center">
                        <div>
                            <p class="text-4xl mb-3">📋</p>
                            <p class="text-sm text-[var(--text-2)]">Pilih tiket di sebelah kiri untuk melihat detail.</p>
                        </div>
                    </div>

                    <div v-else class="space-y-5">
                        <!-- Meta -->
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-black">{{ ticketTypeLabel(activeTicket.type) }}</span>
                                    <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="ticketStatusClass(activeTicket.status)">{{ activeTicket.status }}</span>
                                </div>
                                <p class="mt-1 text-xs font-mono text-[var(--text-2)]">{{ activeTicket.remoteNumber }}</p>
                                <p class="mt-0.5 text-xs text-[var(--text-2)]">Masuk: {{ activeTicket.createdAt }}</p>
                                <p v-if="activeTicket.assignee" class="mt-0.5 text-xs text-sky-600 font-semibold">Dihandle: {{ activeTicket.assignee }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <template v-if="activeTicket.status === 'open' || activeTicket.status === 'replied'">
                                    <select v-model="ticketTransferTo"
                                            class="rounded-xl border border-[var(--border)] bg-[var(--surface-2)] px-2.5 py-1.5 text-xs font-semibold text-[var(--text-1)] outline-none">
                                        <option value="">Pindah ke...</option>
                                        <option value="pengaduan">Pengaduan</option>
                                        <option value="konsultasi">Konsultasi</option>
                                        <option value="umum">Umum</option>
                                    </select>
                                    <button @click="transferTicketAction" :disabled="!ticketTransferTo"
                                            class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition disabled:opacity-40">→ Pindah</button>
                                </template>
                                <button v-if="activeTicket.status !== 'closed'" @click="closeTicketAction"
                                        class="rounded-xl border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-100 transition">✓ Tutup</button>
                            </div>
                        </div>

                        <!-- Original message -->
                        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-2)] mb-2">Pesan dari Pengguna</p>
                            <p class="text-sm text-[var(--text-1)] whitespace-pre-wrap leading-relaxed">{{ activeTicket.message }}</p>
                        </div>

                        <div v-if="canClassifyTicket(activeTicket)" class="rounded-2xl border border-sky-200 bg-sky-50/60 p-4">
                            <p class="mb-2 text-[10px] font-bold uppercase tracking-widest text-sky-700">Klasifikasi Tiket</p>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="opt in ticketTypeOptions"
                                        :key="opt.value"
                                        @click="classifyTicketType(opt.value)"
                                        class="rounded-xl border px-3 py-1.5 text-xs font-bold transition"
                                        :class="normalizeTicketType(activeTicket.type) === opt.value
                                            ? 'border-sky-300 bg-sky-600 text-white'
                                            : 'border-sky-200 bg-white text-sky-700 hover:bg-sky-100'">
                                    {{ opt.label }}
                                </button>
                            </div>
                            <p class="mt-2 text-[11px] text-sky-700/80">
                                Tipe saat ini:
                                <strong>{{ normalizeTicketType(activeTicket.type) ? ticketTypeLabel(activeTicket.type) : 'Belum ditentukan' }}</strong>
                            </p>
                        </div>

                        <!-- Reply area -->
                        <div v-if="activeTicket.status !== 'closed' && activeTicket.status !== 'sent'" class="space-y-3">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-[var(--text-2)]">Balasan Operator</p>
                            <textarea v-model="ticketReplyText" rows="4" placeholder="Ketik balasan untuk pengguna..."
                                      class="w-full resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-sm text-[var(--text-1)] outline-none transition placeholder:text-[var(--text-2)] focus:border-sky-400/60"></textarea>
                            <div class="flex flex-wrap gap-2">
                                <button @click="saveTicketReply" :disabled="ticketReplyState === 'saving'"
                                        class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-5 py-2.5 text-sm font-bold text-[var(--text-1)] hover:bg-[var(--surface-1)] transition disabled:opacity-40">
                                    {{ { saving: '⏳ Menyimpan...', saved: '✓ Tersimpan' }[ticketReplyState] || '💾 Simpan' }}
                                </button>
                                <button @click="sendTicketReply" :disabled="ticketReplyState === 'sending'"
                                        class="rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 px-5 py-2.5 text-sm font-black text-white shadow-md hover:brightness-110 transition disabled:opacity-40">
                                    {{ { sending: '⏳ Mengirim...', sent: '✓ Terkirim!' }[ticketReplyState] || '📤 Kirim ke WA' }}
                                </button>
                            </div>
                        </div>

                        <!-- Sent reply preview -->
                        <div v-if="activeTicket.reply" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-600 mb-2">Balasan Terkirim{{ activeTicket.sentAt ? ` — ${activeTicket.sentAt}` : '' }}</p>
                            <p class="text-sm text-emerald-900 whitespace-pre-wrap leading-relaxed">{{ activeTicket.reply }}</p>
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
                        {{ sendState === 'sending' ? 'Kirim cepat...' : sendState === 'sent' ? '✓ Masuk antrean' : 'Kirim Pesan' }}
                    </button>
                </div>
            </article>

            <!-- Device Status + QR -->
            <article class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-black text-[var(--text-1)]">Status Device WA</h2>
                    <div class="flex gap-2">
                        <button v-if="isAdmin" @click="runAction('restart', 'Restart')" :disabled="isBusy"
                                class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700 hover:bg-amber-100 transition disabled:opacity-40">Restart</button>
                        <button v-if="isAdmin" @click="runAction('reconnect', 'Reconnect')" :disabled="isBusy"
                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition disabled:opacity-40">Reconnect</button>
                        <button v-if="isAdmin" @click="runAction('disconnect', 'Disconnect')" :disabled="isBusy"
                                class="rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition disabled:opacity-40">Disconnect</button>
                        <button v-if="isAdmin" @click="softResetStateAction" :disabled="isBusy"
                                class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 transition disabled:opacity-40" title="Tandai semua chat belum dibalas menjadi sudah, reset status inbox ke open">Reset State</button>
                        <button v-if="isAdmin" @click="syncContactsAction" :disabled="isBusy"
                                class="rounded-xl border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-bold text-cyan-700 hover:bg-cyan-100 transition disabled:opacity-40" title="Scan kontak WA untuk memperbarui mapping LID ke nomor HP">Sync Kontak</button>
                    </div>
                </div>

                <!-- Status connected info -->
                <div v-if="isConnected" class="mb-4 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                    <div class="flex-shrink-0 text-2xl">✅</div>
                    <div>
                        <p class="text-sm font-bold text-emerald-800">WhatsApp Terhubung</p>
                        <p v-if="connectedInfo?.connectedAt" class="mt-0.5 text-xs text-emerald-700">
                            Terhubung sejak: {{ new Date(connectedInfo.connectedAt).toLocaleString('id-ID', { timeZone: 'Asia/Jakarta', day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' }) }} WIB
                        </p>
                        <p v-if="connectedInfo?.sessionId" class="mt-0.5 font-mono text-[10px] text-emerald-600">
                            Session: {{ connectedInfo.sessionId }}
                        </p>
                        <p class="mt-2 text-xs text-emerald-700">Inbox aktif. Pesan masuk akan diterima secara real-time.</p>
                    </div>
                </div>

                <!-- QR Frame -->
                <div class="relative min-h-[240px] rounded-2xl border border-dashed border-[var(--border)] bg-[var(--surface-2)] p-4 flex flex-col items-center justify-center">

                    <!-- Tampilkan QR jika ada -->
                    <template v-if="qrDataUrl && !isConnected">
                        <p class="mb-3 text-xs font-bold uppercase tracking-widest text-amber-600">📱 Scan QR Code dengan WhatsApp</p>
                        <img :src="qrDataUrl" alt="QR WA Caraka"
                             class="w-full max-w-[220px] rounded-xl bg-white p-2 shadow-md border border-slate-200" />
                        <p class="mt-3 text-[10px] text-[var(--text-2)] text-center">
                            Buka WhatsApp → Perangkat Tertaut → Tautkan Perangkat → Scan
                        </p>
                        <button @click="generateQr" :disabled="qrLoading"
                                class="mt-3 rounded-xl border border-slate-300 px-3 py-1.5 text-[10px] font-bold text-slate-600 hover:bg-slate-100 transition disabled:opacity-40">
                            🔄 Perbarui QR
                        </button>
                    </template>

                    <!-- Device terhubung, tidak perlu QR -->
                    <template v-else-if="isConnected">
                        <div class="text-center">
                            <div class="mb-3 h-16 w-16 mx-auto rounded-full bg-emerald-100 flex items-center justify-center text-3xl">✅</div>
                            <p class="text-sm font-bold text-emerald-600">Device Aktif & Terhubung</p>
                            <p class="mt-1 text-xs text-[var(--text-2)]">Tidak perlu scan QR.</p>
                        </div>
                    </template>

                    <!-- QR sedang dimuat / polling -->
                    <template v-else-if="qrPollingRef">
                        <div class="text-center">
                            <div class="mb-4 h-16 w-16 mx-auto rounded-full bg-amber-100 flex items-center justify-center">
                                <svg class="animate-spin h-8 w-8 text-amber-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-amber-600">Menunggu QR dari runtime...</p>
                            <p class="mt-1 text-xs text-[var(--text-2)]">Mungkin memerlukan 10–30 detik.</p>
                            <button @click="stopQrPolling" class="mt-3 text-xs text-slate-400 hover:text-slate-600 transition">Batalkan</button>
                        </div>
                    </template>

                    <!-- Default: tombol generate QR -->
                    <template v-else>
                        <div class="text-center">
                            <div class="mb-3 h-16 w-16 mx-auto rounded-2xl bg-slate-100 flex items-center justify-center text-3xl">📲</div>
                            <p class="text-sm font-semibold text-[var(--text-1)]">Device Belum Terhubung</p>
                            <p class="mt-1 text-xs text-[var(--text-2)] max-w-[200px] mx-auto">
                                Klik tombol di bawah untuk generate QR Code baru.
                            </p>
                            <button @click="generateQr" :disabled="qrLoading"
                                    class="mt-4 w-full max-w-[180px] rounded-2xl bg-gradient-to-br from-sky-500 to-cyan-600 py-2.5 text-sm font-black text-white shadow-md hover:brightness-110 transition disabled:opacity-50">
                                {{ qrLoading ? '⏳ Memuat...' : '📱 Generate QR Code' }}
                            </button>
                        </div>
                    </template>
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

<style scoped>
.bubble-outbound {
    position: relative;
    background: #d9fdd3;
    border: 1px solid #b7ebb0;
    box-shadow: 0 1px 0 rgba(11, 20, 26, 0.08), 0 1px 2px rgba(11, 20, 26, 0.08);
}

.bubble-outbound::after {
    content: '';
    position: absolute;
    right: -7px;
    top: 10px;
    width: 10px;
    height: 14px;
    background: #d9fdd3;
    border-right: 1px solid #b7ebb0;
    border-top: 1px solid #b7ebb0;
    clip-path: polygon(0 0, 100% 20%, 0 100%);
}

.bubble-inbound {
    position: relative;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    box-shadow: 0 1px 0 rgba(11, 20, 26, 0.08), 0 1px 2px rgba(11, 20, 26, 0.08);
}

.bubble-inbound::before {
    content: '';
    position: absolute;
    left: -7px;
    top: 10px;
    width: 10px;
    height: 14px;
    background: #ffffff;
    border-left: 1px solid #e5e7eb;
    border-top: 1px solid #e5e7eb;
    clip-path: polygon(100% 0, 0 20%, 100% 100%);
}

.bubble-sending {
    filter: saturate(0.88) brightness(0.96);
}

.bubble-failed {
    background: #ffe4e6;
    border-color: #fecdd3;
    box-shadow: 0 1px 0 rgba(225, 29, 72, 0.08), 0 1px 2px rgba(225, 29, 72, 0.12);
}

.bubble-pop-in {
    animation: bubblePopIn 0.2s ease-out;
}

.send-btn {
    background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
    transform: translateZ(0);
}

.thread-surface {
    background-color: #efeae2;
    background-image:
        radial-gradient(circle at 25% 20%, rgba(255, 255, 255, 0.55) 0, rgba(255, 255, 255, 0) 40%),
        radial-gradient(circle at 80% 0%, rgba(255, 255, 255, 0.35) 0, rgba(255, 255, 255, 0) 38%),
        linear-gradient(0deg, rgba(0, 0, 0, 0.025) 1px, transparent 1px),
        linear-gradient(90deg, rgba(0, 0, 0, 0.02) 1px, transparent 1px);
    background-size: auto, auto, 24px 24px, 24px 24px;
}

.avatar-tone-1 {
    background: linear-gradient(145deg, #0284c7, #0ea5e9);
}

.avatar-tone-2 {
    background: linear-gradient(145deg, #7c3aed, #a855f7);
}

.avatar-tone-3 {
    background: linear-gradient(145deg, #059669, #10b981);
}

.avatar-tone-4 {
    background: linear-gradient(145deg, #ea580c, #f97316);
}

.avatar-tone-5 {
    background: linear-gradient(145deg, #e11d48, #f43f5e);
}

.avatar-tone-6 {
    background: linear-gradient(145deg, #334155, #475569);
}

.member-color-self {
    color: #075e54;
}

.member-color-1 {
    color: #0ea5e9;
}

.member-color-2 {
    color: #d946ef;
}

.member-color-3 {
    color: #16a34a;
}

.member-color-4 {
    color: #ea580c;
}

.member-color-5 {
    color: #7c3aed;
}

.member-color-6 {
    color: #db2777;
}

.member-color-7 {
    color: #2563eb;
}

.member-color-8 {
    color: #0f766e;
}

.send-btn:hover {
    transform: translateY(-1px);
    filter: brightness(1.07);
}

.send-btn:active {
    transform: scale(0.975);
}

.send-btn-sending {
    animation: sendBtnPulse 0.9s ease-in-out infinite;
}

.send-dot-loader {
    width: 0.9rem;
    height: 0.9rem;
    border-radius: 999px;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #ffffff;
    animation: spin 0.8s linear infinite;
}

@keyframes bubblePopIn {
    0% {
        opacity: 0;
        transform: translateY(8px) scale(0.96);
    }
    100% {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

@keyframes sendBtnPulse {
    0%,
    100% {
        filter: saturate(1) brightness(1);
    }
    50% {
        filter: saturate(1.05) brightness(1.08);
    }
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
