<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import InterkomPanel from '@/Components/lawangsewu/InterkomPanel.vue';
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
    reportStats:  { type: Object, default: () => ({ operatorStats: [], generatedAt: null }) },
    ticketStats:  { type: Object, default: () => ({ total: 0, open: 0, replied: 0, sent: 0, closed: 0, pengaduan: 0, konsultasi: 0, umum: 0, todayTotal: 0 }) },
    recentTickets:{ type: Array,  default: () => [] },
});

// ─── State ────────────────────────────────────────────
const isLoading      = ref(false);
const isBusy        = ref(false);
const autoRefresh   = ref(true);
const pollRef       = ref(null);
const activeThreadRequestId = ref(0);
let nextThreadRequestId = 0;

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
const latestOperatorStats = ref(Array.isArray(props.reportStats?.operatorStats) ? props.reportStats.operatorStats : []);
const operatorStatsGeneratedAt = ref(props.reportStats?.generatedAt || null);
const conversationSearch = ref('');
const conversationFilter = ref('all');

// Inbox
const conversations         = ref([]);
const activeConvoId         = ref('');
const conversationMessages  = ref([]);
const conversationMessageCache = ref({});
const recentlyUpdatedConversationIds = ref({});
const visibleConversationCount = ref(40);
const threadLoading         = ref(false);
const threadLoadingVisible  = ref(false);
const activeConvo           = computed(() => conversations.value.find(c => c.conversationId === activeConvoId.value) || null);
const threadEl              = ref(null);
const particleCanvasRef     = ref(null);
const sidebarListEl         = ref(null);
const replyTextareaRef      = ref(null);
const mediaViewer           = ref(null);
const threadZoom            = ref(100);
const threadVisibleCount    = ref(24); // Fixed at 24 — tidak perlu dikontrol user
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
const replyProgress = ref(0);
const mediaInputRef = ref(null);
const mediaAttachment = ref(null);
const sendTo     = ref('');
const sendText   = ref('');
const sendState  = ref('idle');
const replyCooldownRef = ref(null);
const sendCooldownRef = ref(null);
const pendingReadConversationIds = new Set();
const pendingConversationFetchIds = new Set();
const deletedTempIds = new Set(); // Track deleted temp messages to prevent re-merge
const MAX_MEDIA_FILE_BYTES = Number(props.config?.maxMediaBytes || 5 * 1024 * 1024);
let threadLoadingDelayRef = null;
let inboxRefreshTimerRef = null;

// Handover
const handoverReason  = ref('');
const handoverLoading = ref(false);
const showHandoverModal = ref(false);
const handoverEnabled = ref(true);
const handoverToggling = ref(false);

// Status
const statusText = ref('Memeriksa koneksi...');
const statusTone = ref('warn');

// ─── Toast Notification System (replaces native alert) ───
const toast = ref({
    isOpen: false,
    type: 'error',    // 'success' | 'error' | 'warning' | 'info'
    title: '',
    message: '',
    timer: null,
});

const toastIcons = {
    success: '✦',
    error: '✕',
    warning: '⚠',
    info: 'ℹ',
};

const toastTitles = {
    success: 'Berhasil',
    error: 'Gagal',
    warning: 'Perhatian',
    info: 'Info',
};

const showToast = (type, message, title = null, duration = 4500) => {
    if (toast.value.timer) clearTimeout(toast.value.timer);
    toast.value = {
        isOpen: true,
        type,
        title: title || toastTitles[type] || 'Notifikasi',
        message,
        timer: setTimeout(() => dismissToast(), duration),
    };
};

const dismissToast = () => {
    if (toast.value.timer) clearTimeout(toast.value.timer);
    toast.value.isOpen = false;
};

// Dialog Konfirmasi
const confirmModal = ref({ isOpen: false, title: '', message: '', resolve: null });
const openConfirmModal = (title, message) => {
    return new Promise((resolve) => confirmModal.value = { isOpen: true, title, message, resolve });
};
const resolveConfirmModal = (val) => {
    if (confirmModal.value.resolve) confirmModal.value.resolve(val);
    confirmModal.value.isOpen = false;
};

// ─── Interkom Panel (Sidebar Kanan) ───────────────────
const showInterkom = ref(
    typeof window !== 'undefined'
        ? localStorage.getItem('wacaraka.showInterkom') !== 'false'
        : true
);
const unreadInterkom = ref(0);
const interkomRef = ref(null);

const toggleInterkom = () => {
    showInterkom.value = !showInterkom.value;
    if (typeof window !== 'undefined') {
        localStorage.setItem('wacaraka.showInterkom', String(showInterkom.value));
    }
    if (showInterkom.value) unreadInterkom.value = 0;
};

// Sinkron unread count dari komponen child
watch(() => interkomRef.value?.unread, (val) => {
    if (!showInterkom.value && typeof val === 'number') {
        unreadInterkom.value = val;
    }
});

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
const isOperator   = computed(() => props.authUser?.role === 'operator');
const operatorLiteMode = computed(() => isOperator.value && !isAdmin.value && !isSuperAdmin.value);
const conversationFetchLimit = computed(() => operatorLiteMode.value ? 45 : 70);
const isConnected  = computed(() => Boolean(runtimeHealth.value?.connected || runtimeHealth.value?.status === 'connected'));
const hasRealtime = computed(() => {
    if (typeof window === 'undefined') return false;
    // Echo object harus ada DAN WebSocket benar-benar connected
    // (bukan hanya object dibuat — bisa exist meski koneksi gagal)
    return Boolean(window.Echo) && Boolean(window.__reverbState?.connected);
});
const myId = computed(() => props.authUser?.id);

// Deteksi dark mode dari DOM (sinkron dengan LawangsewuLayout)
const isDark = ref(typeof window !== 'undefined' && localStorage.getItem('lawangsewu-theme') === 'dark');

// ─── Mobile Responsive ───────────────────────────────
// On mobile (< 640px), show either inbox or thread, not both
const isMobile = ref(false);
const mobileView = ref('inbox'); // 'inbox' or 'thread'

const checkMobile = () => {
    isMobile.value = typeof window !== 'undefined' && window.innerWidth < 640;
};

const showMobileThread = () => {
    if (isMobile.value) mobileView.value = 'thread';
};

const backToMobileInbox = () => {
    mobileView.value = 'inbox';
    activeConvoId.value = '';
    conversationMessages.value = [];
};

const threadSurfaceDarkStyle = computed(() => isDark.value ? {
    backgroundColor: '#0b1320',
    backgroundImage: [
        'radial-gradient(ellipse at 20% 0%, rgba(56,139,253,0.18) 0%, transparent 50%)',
        'radial-gradient(ellipse at 80% 100%, rgba(111,66,193,0.12) 0%, transparent 50%)',
    ].join(', '),
} : {});

// ─── Network Constellation Particle System ───────────
let particleAnimFrame  = null;
let particleCtx        = null;
let particleCanvas_    = null;    // cache canvas ref untuk RAF
let _lastFrameTime     = 0;
const NET_COUNT        = 38;      // jumlah node — cukup untuk efek, ringan
const NET_LINK_DIST    = 130;     // jarak max antar node untuk ditarik garis
const NET_FPS_CAP      = 30;      // frame per detik maksimal
const NET_FRAME_MS     = 1000 / NET_FPS_CAP;
let netNodes           = [];

// Palet warna node: biru & hijau-teal seperti di gambar referensi
const NET_NODE_COLORS = [
    { r: 59,  g: 130, b: 246, w: 6 },  // blue-500
    { r: 125, g: 211, b: 252, w: 3 },  // sky-300
    { r: 34,  g: 197, b: 94,  w: 2 },  // green-500 (aksen)
    { r: 56,  g: 189, b: 248, w: 4 },  // sky-400
];

const pickNodeColor = () => {
    const totalW = NET_NODE_COLORS.reduce((s, c) => s + c.w, 0);
    let rnd = Math.random() * totalW;
    for (const c of NET_NODE_COLORS) { rnd -= c.w; if (rnd <= 0) return c; }
    return NET_NODE_COLORS[0];
};

const createNetNode = (canvas) => {
    const color = pickNodeColor();
    return {
        x:      Math.random() * canvas.width,
        y:      Math.random() * canvas.height,
        r:      Math.random() * 1.6 + 0.7,
        vx:     (Math.random() - 0.5) * 0.28,
        vy:     (Math.random() - 0.5) * 0.28,
        color,
        opacity: Math.random() * 0.55 + 0.35,
    };
};

const drawNetwork = (ts) => {
    particleAnimFrame = requestAnimationFrame(drawNetwork);

    // Frame-rate cap
    if (ts - _lastFrameTime < NET_FRAME_MS) return;
    _lastFrameTime = ts;

    const canvas = particleCanvas_;
    const ctx    = particleCtx;
    if (!canvas || !ctx) return;

    const W = canvas.width;
    const H = canvas.height;

    ctx.clearRect(0, 0, W, H);

    // Gerak + wrap
    for (const n of netNodes) {
        n.x += n.vx;
        n.y += n.vy;
        if (n.x < -10) n.x = W + 10;
        if (n.x > W + 10) n.x = -10;
        if (n.y < -10) n.y = H + 10;
        if (n.y > H + 10) n.y = -10;
    }

    // Gambar garis koneksi
    for (let i = 0; i < netNodes.length; i++) {
        const a = netNodes[i];
        for (let j = i + 1; j < netNodes.length; j++) {
            const b    = netNodes[j];
            const dx   = a.x - b.x;
            const dy   = a.y - b.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            if (dist > NET_LINK_DIST) continue;

            // Opacity garis: makin dekat makin terang, max 0.28
            const lineOpacity = (1 - dist / NET_LINK_DIST) * 0.28;
            const { r, g, b: bc } = a.color;
            ctx.beginPath();
            ctx.moveTo(a.x, a.y);
            ctx.lineTo(b.x, b.y);
            ctx.strokeStyle = `rgba(${r},${g},${bc},${lineOpacity})`;
            ctx.lineWidth   = 0.7;
            ctx.stroke();
        }
    }

    // Gambar node (titik)
    for (const n of netNodes) {
        const { r, g, b: bc } = n.color;
        ctx.beginPath();
        ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${r},${g},${bc},${n.opacity})`;
        ctx.fill();

        // Glow ring kecil di sekitar node
        ctx.beginPath();
        ctx.arc(n.x, n.y, n.r * 2.4, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${r},${g},${bc},0.06)`;
        ctx.fill();
    }
};

const startParticles = () => {
    const canvas = particleCanvasRef.value;
    if (!canvas || !isDark.value) return;
    const parent = canvas.parentElement;
    if (!parent) return;

    // DPR-aware sizing (tajam di retina, tapi cap di 1.5x agar ringan)
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    const W   = parent.offsetWidth  || 800;
    const H   = parent.offsetHeight || 500;
    canvas.width  = W * dpr;
    canvas.height = H * dpr;
    canvas.style.width  = `${W}px`;
    canvas.style.height = `${H}px`;

    particleCtx   = canvas.getContext('2d');
    particleCtx.scale(dpr, dpr);
    particleCanvas_ = { width: W, height: H };   // pakai ukuran CSS (sudah di-scale)

    netNodes = Array.from({ length: NET_COUNT }, () => createNetNode(particleCanvas_));

    if (particleAnimFrame) cancelAnimationFrame(particleAnimFrame);
    _lastFrameTime = 0;
    particleAnimFrame = requestAnimationFrame(drawNetwork);
};

const stopParticles = () => {
    if (particleAnimFrame) { cancelAnimationFrame(particleAnimFrame); particleAnimFrame = null; }
    if (particleCtx && particleCanvasRef.value) {
        particleCtx.clearRect(0, 0, particleCanvasRef.value.width, particleCanvasRef.value.height);
    }
    particleCtx     = null;
    particleCanvas_ = null;
    netNodes        = [];
};



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

const operatorStatsUpdatedAtText = computed(() => {
    if (!operatorStatsGeneratedAt.value) return 'Belum diperbarui';

    return new Date(operatorStatsGeneratedAt.value).toLocaleString('id-ID', {
        timeZone: 'Asia/Jakarta',
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }) + ' WIB';
});

const contextMenu = ref({
    isOpen: false,
    x: 0,
    y: 0,
    convo: null,
});

const openContextMenu = (event, convo) => {
    contextMenu.value = {
        isOpen: true,
        x: event.clientX,
        y: event.clientY,
        convo: convo,
    };
};

const closeContextMenu = () => {
    contextMenu.value.isOpen = false;
};

const handleContextMenuDelete = async () => {
    const convoId = contextMenu.value.convo?.conversationId;
    closeContextMenu();
    if (!convoId) return;
    
    if (!(await openConfirmModal('Konfirmasi Hapus', 'Hapus seluruh sesi percakapan ' + convoId + ' secara permanen?'))) return;
    
    try {
        const d = await callApi('clear-conversation', {
            method: 'post',
            data: { conversation_id: convoId },
        });
        appendLog('Sesi percakapan dihapus via klik kanan', d);
        if (activeConvoId.value === convoId) {
            activeConvoId.value = '';
            conversationMessages.value = [];
        }
        await refreshAll();
    } catch (err) {
        appendLog('Gagal menghapus sesi percakapan', { error: err?.error });
        showToast('error', err?.error || 'Gagal menghapus percakapan');
    }
};

const handleContextMenuMark = () => {
    const convoId = contextMenu.value.convo?.conversationId;
    closeContextMenu();
    if (!convoId) return;
    activeConvoId.value = convoId;
    markEditorOpen.value = true;
};

onMounted(() => {
    window.addEventListener('click', closeContextMenu);
    window.addEventListener('scroll', closeContextMenu, { passive: true });

    // Mobile responsive: detect screen size
    checkMobile();
    window.addEventListener('resize', checkMobile, { passive: true });

    // Watch DOM for theme-dark class changes (sinkron dengan toggle di LawangsewuLayout)
    if (typeof MutationObserver !== 'undefined') {
        const themeObserver = new MutationObserver(() => {
            const nowDark = Boolean(document.querySelector('.theme-dark'));
            if (isDark.value !== nowDark) {
                isDark.value = nowDark;
                if (nowDark) {
                    nextTick(() => startParticles());
                } else {
                    stopParticles();
                }
            }
        });
        themeObserver.observe(document.body.parentElement || document.body, {
            attributes: true, attributeFilter: ['class'], subtree: true,
        });
        window._wacarakaThemeObserver = themeObserver;
    }

    // Mulai partikel jika dark mode aktif saat mount
    if (isDark.value) {
        nextTick(() => startParticles());
    }
});
onUnmounted(() => {
    window.removeEventListener('click', closeContextMenu);
    window.removeEventListener('scroll', closeContextMenu);
    window.removeEventListener('resize', checkMobile);
    stopParticles();
    if (window._wacarakaThemeObserver) {
        window._wacarakaThemeObserver.disconnect();
        delete window._wacarakaThemeObserver;
    }
});

const filteredConversations = computed(() => {
    const keyword = conversationSearch.value.trim().toLowerCase();
    const filter = conversationFilter.value;

    // Patterns for automated/bot conversations to auto-hide from inbox
    const healthCheckPatterns = [
        'engine-health-check',
        'tokenless-route-check',
        'health-check',
        'health_check',
        'status@broadcast',
    ];

    return conversations.value.filter((conversation) => {
        // Auto-hide health-check/bot conversations (unless explicitly searched)
        if (!keyword) {
            const title = (conversation.displayTitle || conversation.remoteName || '').toLowerCase();
            const preview = (conversation.lastMessagePreview || '').toLowerCase();
            const remoteNum = (conversation.remoteNumber || '').toLowerCase();
            if (healthCheckPatterns.some(p => title.includes(p) || preview.includes(p) || remoteNum.includes(p))) {
                return false;
            }
        }

        if (filter === 'unread' && Number(conversation.unreadCount || 0) <= 0) {
            return false;
        }

        if (filter === 'mine' && conversation.owner?.id !== myId.value) {
            return false;
        }

        if (filter === 'group' && !conversation.isGroup) {
            return false;
        }

        if (keyword === '') {
            return true;
        }

        const haystack = [
            conversation.displayTitle,
            conversation.remoteName,
            conversation.remoteNumber,
            conversation.groupName,
            conversation.lastMessagePreview,
            conversation.customerMark?.label,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();

        return haystack.includes(keyword);
    });
});

const renderedConversations = computed(() => (
    filteredConversations.value.slice(0, visibleConversationCount.value)
));

const activeConversationIndex = computed(() => (
    filteredConversations.value.findIndex((conversation) => conversation.conversationId === activeConvoId.value)
));

const currentOperatorStat = computed(() => latestOperatorStats.value.find((item) => item.id === myId.value) || null);

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

const mediaViewerOpen = computed(() => Boolean(mediaViewer.value?.src));

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

const conversationStatusLabel = (status) => ({
    pending: 'belum dibaca',
    open: 'open',
    closed: 'closed',
}[status] || status || '-');

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
    let json = {};
    try {
        json = await res.json();
    } catch {
        json = {};
    }

    if (!res.ok) throw normalizeApiError(res.status, json?.error);
    return json;
};

const normalizeApiError = (status, rawError = null) => {
    if (typeof rawError === 'string' && rawError.trim() !== '') {
        return { status, error: rawError };
    }

    if (status === 413) {
        return {
            status,
            error: `Ukuran lampiran terlalu besar untuk server. Coba file di bawah ${Math.max(1, Math.round(MAX_MEDIA_FILE_BYTES / (1024 * 1024)))} MB.`,
        };
    }

    if (status === 419) {
        return { status, error: 'Sesi login sudah berubah. Muat ulang halaman lalu coba kirim lagi.' };
    }

    if (status === 422) {
        return { status, error: 'Data lampiran tidak valid atau melebihi batas kirim.' };
    }

    if (status >= 500) {
        return { status, error: 'Server gagal memproses permintaan. Coba lagi beberapa saat.' };
    }

    return { status, error: 'Terjadi kesalahan.' };
};

const callApiWithXhr = (action, options = {}) => new Promise((resolve, reject) => {
    const method = String(options.method || 'get').toUpperCase();
    const url = route('lawangsewu.wacaraka.api', { action });
    const query = options.params ? '?' + new URLSearchParams(options.params).toString() : '';
    const xhr = new XMLHttpRequest();
    xhr.open(method, url + query, true);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    if (csrfToken) xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);

    if (options.data) {
        xhr.setRequestHeader('Content-Type', 'application/json');
    }

    xhr.onreadystatechange = () => {
        if (xhr.readyState !== XMLHttpRequest.DONE) return;

        let json = {};
        try {
            json = xhr.responseText ? JSON.parse(xhr.responseText) : {};
        } catch {
            json = {};
        }

        if (xhr.status >= 200 && xhr.status < 300) {
            resolve(json);
            return;
        }

        reject(normalizeApiError(xhr.status, json?.error));
    };

    xhr.onerror = () => reject({ status: 0, error: 'Jaringan terputus saat mengirim permintaan.' });

    if (typeof options.onUploadProgress === 'function' && xhr.upload) {
        xhr.upload.onprogress = (event) => {
            if (!event.lengthComputable) return;
            options.onUploadProgress(Math.max(0, Math.min(100, Math.round((event.loaded / event.total) * 100))));
        };
    }

    xhr.send(options.data ? JSON.stringify(options.data) : null);
});

const appendLog = (title, payload = null) => {
    const stamp = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    requestLog.value.unshift({ stamp, title, payload });
    if (requestLog.value.length > 30) requestLog.value = requestLog.value.slice(0, 30);
};

const scheduleReplyStateReset = (delay = 1200) => {
    if (replyCooldownRef.value) clearTimeout(replyCooldownRef.value);
    replyCooldownRef.value = setTimeout(() => {
        replyState.value = 'idle';
        replyProgress.value = 0;
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

const detectMediaKindFromFile = (file) => {
    const normalized = String(file?.type || '').toLowerCase();
    const fileName = String(file?.name || '').toLowerCase();
    if (normalized === 'image/webp' || fileName.endsWith('.webp')) return 'sticker';
    if (normalized.startsWith('image/')) return 'image';
    if (normalized.startsWith('video/')) return 'video';
    if (normalized.startsWith('audio/')) return 'audio';
    return 'document';
};

const inferMimeTypeFromFile = (file) => {
    const normalized = String(file?.type || '').trim().toLowerCase();
    if (normalized) return normalized;

    const fileName = String(file?.name || '').toLowerCase();
    const extension = fileName.includes('.') ? fileName.split('.').pop() : '';

    return {
        jpg: 'image/jpeg',
        jpeg: 'image/jpeg',
        png: 'image/png',
        gif: 'image/gif',
        webp: 'image/webp',
        heic: 'image/heic',
        heif: 'image/heif',
        mp4: 'video/mp4',
        mov: 'video/quicktime',
        avi: 'video/x-msvideo',
        mp3: 'audio/mpeg',
        ogg: 'audio/ogg',
        oga: 'audio/ogg',
        wav: 'audio/wav',
        m4a: 'audio/mp4',
        pdf: 'application/pdf',
        rtf: 'application/rtf',
        doc: 'application/msword',
        docx: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        xls: 'application/vnd.ms-excel',
        xlsx: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ppt: 'application/vnd.ms-powerpoint',
        pptx: 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        csv: 'text/csv',
        txt: 'text/plain',
        json: 'application/json',
        xml: 'application/xml',
        zip: 'application/zip',
        rar: 'application/vnd.rar',
        '7z': 'application/x-7z-compressed',
    }[extension] || 'application/octet-stream';
};

const dataUrlByteLength = (dataUrl) => {
    const value = String(dataUrl || '');
    if (!value.startsWith('data:')) return 0;

    const parts = value.split(',', 2);
    if (parts.length !== 2) return 0;

    const payload = (parts[1] || '').replace(/\s+/g, '');
    if (!payload) return 0;

    let padding = 0;
    if (payload.endsWith('==')) padding = 2;
    else if (payload.endsWith('=')) padding = 1;

    return Math.max(0, Math.floor((payload.length * 3) / 4) - padding);
};

const compressImageDataUrl = async (dataUrl, mimeType = 'image/jpeg', maxBytes = MAX_MEDIA_FILE_BYTES) => {
    const sourceUrl = String(dataUrl || '');
    if (!sourceUrl.startsWith('data:image/')) return sourceUrl;

    const image = await new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = () => reject(new Error('Gagal membaca gambar'));
        img.src = sourceUrl;
    });

    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');
    if (!ctx) return sourceUrl;

    let width = image.naturalWidth || image.width;
    let height = image.naturalHeight || image.height;
    let quality = 0.86;
    const outputMime = mimeType === 'image/png' ? 'image/jpeg' : (mimeType || 'image/jpeg');

    const applySize = () => {
        canvas.width = Math.max(1, Math.round(width));
        canvas.height = Math.max(1, Math.round(height));
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.drawImage(image, 0, 0, canvas.width, canvas.height);
        return canvas.toDataURL(outputMime, quality);
    };

    if (Math.max(width, height) > 1920) {
        const ratio = 1920 / Math.max(width, height);
        width *= ratio;
        height *= ratio;
    }

    let result = applySize();
    let attempts = 0;

    while (dataUrlByteLength(result) > maxBytes && attempts < 6) {
        attempts += 1;
        quality = Math.max(0.45, quality - 0.08);
        width *= 0.9;
        height *= 0.9;
        result = applySize();
    }

    return result;
};

const clearMediaAttachment = () => {
    mediaAttachment.value = null;
    if (mediaInputRef.value) {
        mediaInputRef.value.value = '';
    }
};

const noteConversationActivity = (convoId) => {
    if (!convoId || convoId === activeConvoId.value) return;

    recentlyUpdatedConversationIds.value = {
        ...recentlyUpdatedConversationIds.value,
        [convoId]: Date.now(),
    };

    window.setTimeout(() => {
        const current = recentlyUpdatedConversationIds.value[convoId];
        if (!current) return;
        if (Date.now() - current < 2200) return;

        const next = { ...recentlyUpdatedConversationIds.value };
        delete next[convoId];
        recentlyUpdatedConversationIds.value = next;
    }, 2400);
};

const isConversationRecentlyUpdated = (convoId) => Boolean(recentlyUpdatedConversationIds.value[convoId]);

const resetVisibleConversationWindow = () => {
    visibleConversationCount.value = 40;
};

const maybeExpandConversationWindow = () => {
    const el = sidebarListEl.value;
    if (!el) return;
    if (visibleConversationCount.value >= filteredConversations.value.length) return;

    const distance = el.scrollHeight - el.scrollTop - el.clientHeight;
    if (distance <= 180) {
        visibleConversationCount.value = Math.min(
            filteredConversations.value.length,
            visibleConversationCount.value + 30,
        );
    }
};

const attachmentPreviewUrl = computed(() => {
    if (!mediaAttachment.value) return '';
    if (!['image', 'sticker', 'video', 'audio'].includes(mediaAttachment.value.kind)) return '';
    return mediaAttachment.value.dataUrl || '';
});

const isPreviewableAttachment = computed(() => Boolean(attachmentPreviewUrl.value));

const setThreadLoadingState = (value) => {
    threadLoading.value = value;

    if (threadLoadingDelayRef) {
        clearTimeout(threadLoadingDelayRef);
        threadLoadingDelayRef = null;
    }

    if (value) {
        threadLoadingDelayRef = setTimeout(() => {
            if (threadLoading.value) {
                threadLoadingVisible.value = true;
            }
            threadLoadingDelayRef = null;
        }, 120);
        return;
    }

    threadLoadingVisible.value = false;
};

const composerRows = () => {
    const textarea = replyTextareaRef.value;
    if (!textarea) return;

    textarea.style.height = 'auto';
    const nextHeight = Math.min(textarea.scrollHeight, 180);
    textarea.style.height = `${Math.max(76, nextHeight)}px`;
};

const isThreadNearBottom = (threshold = 120) => {
    const el = threadEl.value;
    if (!el) return true;

    const distance = el.scrollHeight - el.scrollTop - el.clientHeight;
    return distance <= threshold;
};

const scrollThreadToBottom = (behavior = 'auto') => {
    const el = threadEl.value;
    if (!el) return;

    el.scrollTo({
        top: el.scrollHeight,
        behavior,
    });
};

const handleReplyKeydown = (event) => {
    if (event.key !== 'Enter') return;
    if (event.shiftKey) return;

    event.preventDefault();

    if (replyState.value === 'sending' || !activeConvo.value || !canReply.value) {
        return;
    }

    replyToConversation();
};

const focusConversationSearch = () => {
    const input = typeof document !== 'undefined'
        ? document.querySelector('input[placeholder="Cari nama, nomor, alias, preview..."]')
        : null;
    input?.focus();
    input?.select?.();
};

const selectAdjacentConversation = (delta) => {
    const list = filteredConversations.value;
    if (!list.length) return;

    const currentIndex = activeConversationIndex.value;
    const baseIndex = currentIndex === -1 ? 0 : currentIndex;
    const nextIndex = Math.max(0, Math.min(list.length - 1, baseIndex + delta));
    const target = list[nextIndex];
    if (!target) return;

    selectConversation(target.conversationId);
};

const handleGlobalShortcuts = (event) => {
    const target = event.target;
    const tagName = String(target?.tagName || '').toLowerCase();
    const isTypingField = tagName === 'input' || tagName === 'textarea' || target?.isContentEditable;

    if (event.key === 'Escape' && mediaViewerOpen.value) {
        event.preventDefault();
        mediaViewer.value = null;
        return;
    }

    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        focusConversationSearch();
        return;
    }

    if (isTypingField) return;
    if (event.altKey || event.ctrlKey || event.metaKey) return;

    if (event.key.toLowerCase() === 'j') {
        event.preventDefault();
        selectAdjacentConversation(1);
        return;
    }

    if (event.key.toLowerCase() === 'k') {
        event.preventDefault();
        selectAdjacentConversation(-1);
    }
};

const pickMediaFile = () => {
    if (!activeConvo.value || !canReply.value || replyState.value === 'sending') return;
    mediaInputRef.value?.click();
};

const onMediaFileChange = async (event) => {
    const file = event?.target?.files?.[0];
    if (!file) return;

    if (file.size > MAX_MEDIA_FILE_BYTES) {
        replyState.value = 'error';
        appendLog('File terlalu besar', { maxMb: Math.round(MAX_MEDIA_FILE_BYTES / (1024 * 1024)), fileSizeMb: (file.size / (1024 * 1024)).toFixed(2) });
        scheduleReplyStateReset(2200);
        clearMediaAttachment();
        return;
    }

    try {
        const initialDataUrl = await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result || ''));
            reader.onerror = () => reject(new Error('Gagal membaca file'));
            reader.readAsDataURL(file);
        });

        const kind = detectMediaKindFromFile(file);
        const mime = inferMimeTypeFromFile(file);
        const finalDataUrl = kind === 'image'
            ? await compressImageDataUrl(initialDataUrl, mime, MAX_MEDIA_FILE_BYTES)
            : initialDataUrl;
        const finalByteLength = dataUrlByteLength(finalDataUrl);

        if (finalByteLength > MAX_MEDIA_FILE_BYTES) {
            replyState.value = 'error';
            appendLog('File masih terlalu besar setelah diproses', {
                maxMb: Math.round(MAX_MEDIA_FILE_BYTES / (1024 * 1024)),
                finalSizeMb: (finalByteLength / (1024 * 1024)).toFixed(2),
            });
            scheduleReplyStateReset(2200);
            clearMediaAttachment();
            return;
        }

        mediaAttachment.value = {
            name: file.name,
            size: finalByteLength || file.size,
            mime,
            kind,
            dataUrl: finalDataUrl,
        };
    } catch {
        replyState.value = 'error';
        appendLog('Gagal memproses lampiran media');
        scheduleReplyStateReset(2200);
        clearMediaAttachment();
    }
};

const sortConversations = (list) => {
    return [...list].sort((a, b) => {
        const pinnedA = Number(Boolean(a?.customerMark?.isPinned));
        const pinnedB = Number(Boolean(b?.customerMark?.isPinned));
        if (pinnedA !== pinnedB) return pinnedB - pinnedA;

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
        const existing = conversations.value[idx];
        // Gabungkan dengan hati-hati: jangan timpa field penting dengan null/undefined
        // dari data parsial (misal: pesan realtime yang tidak membawa semua field).
        const safeMerge = {
            ...existing,
            ...normalized,
            // Jaga field-field display kritis agar tidak hilang saat update parsial
            customerMark: normalized.customerMark ?? existing.customerMark ?? null,
            remoteName:   normalized.remoteName   || existing.remoteName   || '',
            remoteNumber: normalized.remoteNumber || existing.remoteNumber || '',
            groupName:    normalized.groupName    || existing.groupName    || null,
            profilePhotoUrl: normalized.profilePhotoUrl || existing.profilePhotoUrl || null,
            owner:        normalized.owner        ?? existing.owner        ?? null,
            ownerPresence: normalized.ownerPresence || existing.ownerPresence || null,
            pendingHandover: normalized.pendingHandover ?? existing.pendingHandover ?? null,
        };
        conversations.value.splice(idx, 1);
        conversations.value.unshift(normalizeConversation(safeMerge));
    }

    conversations.value = sortConversations(conversations.value);
    noteConversationActivity(normalized.conversationId);
};

const applyConversationReadState = (convoId) => {
    if (!convoId) return;

    mergeConversation({
        conversationId: convoId,
        unreadCount: 0,
    });
};

const markConversationRead = async (convoId, { optimistic = true } = {}) => {
    if (!convoId) return;

    if (optimistic) {
        applyConversationReadState(convoId);
    }

    if (pendingReadConversationIds.has(convoId)) {
        return;
    }

    pendingReadConversationIds.add(convoId);

    try {
        await callApi('mark-read', { method: 'post', data: { conversation_id: convoId } });
    } catch {
        // Polling and realtime will reconcile if the backend state lags.
    } finally {
        pendingReadConversationIds.delete(convoId);
    }
};

const upsertConversationMessage = (incoming) => {
    if (!incoming) return;
    const shouldStickToBottom = isThreadNearBottom();

    const normalizedIncoming = normalizeMessage(incoming);

    const existingById = normalizedIncoming.id
        ? conversationMessages.value.findIndex((item) => item.id === normalizedIncoming.id)
        : -1;

    if (existingById !== -1) {
        conversationMessages.value[existingById] = {
            ...conversationMessages.value[existingById],
            ...normalizedIncoming,
        };
        persistConversationCache(normalizedIncoming.conversationId || activeConvoId.value, conversationMessages.value);
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
        if (shouldStickToBottom) {
            scrollThreadToBottom('smooth');
        }
    });
    persistConversationCache(normalizedIncoming.conversationId || activeConvoId.value, conversationMessages.value);
};

const pushTempOutboundMessage = (text, media = null) => {
    const tempId = `temp-${Date.now()}-${Math.floor(Math.random() * 10000)}`;
    const safeMedia = media && typeof media === 'object' ? media : null;
    conversationMessages.value.push(normalizeMessage({
        _tempId: tempId,
        direction: 'outbound',
        operator: props.authUser?.alias || props.authUser?.name || 'Operator',
        senderName: props.authUser?.alias || props.authUser?.name || 'Anda',
        senderKey: String(props.authUser?.id || 'self'),
        sentAt: 'sekarang',
        text,
        type: safeMedia?.kind || 'text',
        metadata: safeMedia ? {
            media: {
                kind: safeMedia.kind,
                mimetype: safeMedia.mime,
                fileName: safeMedia.name,
                caption: text,
                dataUrl: safeMedia.dataUrl,
                byteLength: safeMedia.size,
            },
        } : {},
        status: 'sending',
    }));

    nextTick(() => {
        scrollThreadToBottom('smooth');
    });
    persistConversationCache(activeConvoId.value, conversationMessages.value);

    return tempId;
};

const persistConversationCache = (convoId, messages) => {
    if (!convoId) return;

    conversationMessageCache.value = {
        ...conversationMessageCache.value,
        [convoId]: Array.isArray(messages) ? [...messages] : [],
    };
};

const hydrateConversationFromCache = (convoId) => {
    if (!convoId) return false;

    const cached = conversationMessageCache.value[convoId];
    if (!Array.isArray(cached)) return false;

    conversationMessages.value = [...cached];
    return true;
};

const markTempMessageStatus = (tempId, status) => {
    const idx = conversationMessages.value.findIndex((msg) => msg._tempId === tempId);
    if (idx === -1) return;
    conversationMessages.value[idx] = {
        ...conversationMessages.value[idx],
        status,
    };
    persistConversationCache(activeConvoId.value, conversationMessages.value);
};

const removeTempMessage = (tempId) => {
    if (!tempId) return;
    deletedTempIds.add(tempId); // Mark as deleted to prevent re-merge
    const nextMessages = conversationMessages.value.filter((msg) => msg._tempId !== tempId);
    conversationMessages.value = nextMessages;
    persistConversationCache(activeConvoId.value, nextMessages);
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
        // Tampilkan nomor WA jika sudah ada di peta LID
        const mapped = getMappedWaNumber(normalized);
        if (mapped) return `${mapped} (wa)`;
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
    const mediaUrl = raw.mediaUrl || media.dataUrl || media.url || media.previewDataUrl || null;
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

const inboxPreviewMedia = (conversation) => {
    const media = conversation?.lastMessageMedia;
    if (!media || typeof media !== 'object') return null;
    return media;
};

const hasInboxVisualPreview = (conversation) => Boolean(inboxPreviewMedia(conversation)?.hasVisualPreview);

const inboxPreviewLabel = (conversation) => {
    const media = inboxPreviewMedia(conversation);
    if (!media) return '';

    return ({
        image: 'gambar',
        sticker: 'stiker',
        document: 'dokumen',
        video: 'video',
        audio: 'audio',
    })[media.kind] || 'lampiran';
};

const isStickerMessage = (msg) => msg?.mediaKind === 'sticker';
const isImageMessage = (msg) => msg?.hasVisualMedia && !isStickerMessage(msg);
const isVideoMessage = (msg) => msg?.mediaKind === 'video' && Boolean(msg?.mediaUrl);
const isAudioMessage = (msg) => msg?.mediaKind === 'audio' && Boolean(msg?.mediaUrl);

const extractMessageFileName = (msg) => (
    msg?.metadata?.media?.fileName
    || msg?.metadata?.fileName
    || msg?.text
    || `file-${msg?.id || 'media'}`
);

const humanFileSize = (bytes) => {
    const value = Number(bytes || 0);
    if (!Number.isFinite(value) || value <= 0) return null;
    if (value < 1024) return `${value} B`;
    if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`;
    return `${(value / (1024 * 1024)).toFixed(1)} MB`;
};

const documentMetaText = (msg) => {
    const parts = [
        msg?.mediaMime || null,
        humanFileSize(msg?.metadata?.media?.byteLength || msg?.metadata?.media?.fileLength || msg?.metadata?.media?.size),
    ].filter(Boolean);

    return parts.join(' · ') || 'File WhatsApp';
};

const outgoingStatusClass = (msg) => ({
    sent: 'text-sky-600',
    queued: 'text-slate-500',
    sending: 'text-slate-500',
    failed: 'text-rose-500',
}[msg.status] || '');

const messageContentFallback = (msg = null) => {
    const normalized = String(msg?.type || msg?.message_type || '').toLowerCase();
    if (normalized === 'notification_template') return '[Notifikasi WhatsApp]';
    if (normalized === 'e2e_notification') return '[Notifikasi keamanan]';
    if (msg?.mediaKind) return `[${msg.mediaKind}]`;
    return '[pesan kosong]';
};

const messageTypeLabel = (type, msg = null) => {
    const normalized = String(type || '').toLowerCase();
    if (normalized === 'text') return 'text';
    if (normalized === 'image') return 'gambar';
    if (normalized === 'sticker') return 'stiker';
    if (normalized === 'document') return 'dokumen';
    if (normalized === 'video') return 'video';
    if (normalized === 'audio') return 'audio';
    if (normalized === 'notification_template') return 'notifikasi';
    if (normalized === 'e2e_notification') return 'keamanan';

    if (msg?.hasVisualMedia) {
        return msg.mediaKind === 'sticker' ? 'stiker' : 'gambar';
    }

    return normalized || 'pesan';
};

const conversationPreviewText = (conversation) => {
    if (!conversation) return '';

    const prefix = conversation.lastMessageDirection === 'outbound' ? 'Anda: ' : '';
    return `${prefix}${conversation.lastMessagePreview || 'Belum ada pesan'}`;
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
    if (!isSuperAdmin.value) { historyItems.value = []; return; }
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
        const normalizedList = (data?.conversations || []).map(normalizeConversation).map((conversation) => (
            preserveActive && activeConvoId.value && conversation.conversationId === activeConvoId.value
                ? { ...conversation, unreadCount: 0 }
                : conversation
        ));
        conversations.value = sortConversations(normalizedList);
        const persistedConvoId = typeof window !== 'undefined'
            ? localStorage.getItem('wacaraka.activeConversationId')
            : '';
        const preferredConvoId = preserveActive && activeConvoId.value ? activeConvoId.value : persistedConvoId;

        if (!preferredConvoId || !conversations.value.some(c => c.conversationId === preferredConvoId)) {
            activeConvoId.value = conversations.value[0]?.conversationId || '';
        } else {
            activeConvoId.value = preferredConvoId;
        }

        // Prefetch lebih agresif agar konversasi sudah siap saat operator klik
        conversations.value
            .slice(0, operatorLiteMode.value ? 6 : 10)
            .forEach((conversation) => prefetchConversation(conversation.conversationId));
    } catch {
        /* silent */
    }
};

const applyThreadMessages = async (messages, { preserveViewport = false } = {}) => {
    const shouldStickToBottom = preserveViewport ? isThreadNearBottom() : true;
    conversationMessages.value = messages;

    await nextTick();

    if (shouldStickToBottom) {
        scrollThreadToBottom(preserveViewport ? 'smooth' : 'auto');
    }
};

const refreshConvoMessages = async (convoId = activeConvoId.value, options = {}) => {
    const { force = false, background = false } = options;
    const convo = conversations.value.find(c => c.conversationId === convoId);
    const isVisibleThread = convoId === activeConvoId.value;
    if (!convoId || !convo) {
        if (isVisibleThread) {
            conversationMessages.value = [];
            setThreadLoadingState(false);
        }
        return;
    }

    if (!force && pendingConversationFetchIds.has(convoId)) {
        return;
    }

    const requestId = ++nextThreadRequestId;
    if (isVisibleThread) {
        activeThreadRequestId.value = requestId;
    }
    pendingConversationFetchIds.add(convoId);
    if (isVisibleThread && (!background || !hydrateConversationFromCache(convoId))) {
        setThreadLoadingState(true);
    }
    if (isVisibleThread) {
        markConversationRead(convoId);
    }

    try {
        const data = await callApi('conversation', {
            params: {
                conversation_id: convo.conversationId,
                remote_number: convo.remoteNumber,
                limit: conversationFetchLimit.value,
            },
        });
        const serverMessages = (data?.messages || []).map(normalizeMessage);

        if (!isVisibleThread) {
            persistConversationCache(convoId, serverMessages);
            return;
        }

        if (activeThreadRequestId.value !== requestId || activeConvoId.value !== convoId) return;

        // Preserve temp messages (sending/failed) that are not yet in DB
        // BUT exclude any that were explicitly deleted by the user
        const pendingTemps = conversationMessages.value.filter(
            (m) => m._tempId && ['sending', 'failed'].includes(m.status) && !deletedTempIds.has(m._tempId)
        );
        const mergedMessages = [...serverMessages, ...pendingTemps];
        await applyThreadMessages(mergedMessages, { preserveViewport: background });
        persistConversationCache(convoId, conversationMessages.value);
    } catch {
        /* silent */
    } finally {
        pendingConversationFetchIds.delete(convoId);
        if (isVisibleThread && activeThreadRequestId.value === requestId) {
            setThreadLoadingState(false);
        }
    }
};

const prefetchConversation = (convoId) => {
    if (!convoId || convoId === activeConvoId.value) return;
    if (conversationMessageCache.value[convoId]?.length) return;
    if (pendingConversationFetchIds.has(convoId)) return;

    refreshConvoMessages(convoId, { background: true });
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
        await refreshHealth();

        if (!operatorLiteMode.value) {
            await Promise.all([refreshStats(), refreshHistory(), refreshLidMappings(), refreshOperatorStats()]);
        }
        
        // Only attempt heavier calls if connected or at least has health response
        if (runtimeHealth.value?.status) {
            // Pull first, then refresh list so ordering reflects newest incoming chat immediately.
            await pullInbox();
            await Promise.all([
                refreshInboxList(),
                operatorLiteMode.value ? Promise.resolve() : refreshQr(),
                activeConvoId.value ? refreshConvoMessages(activeConvoId.value, { background: true }) : Promise.resolve(),
            ]);
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

const refreshOperatorStats = async () => {
    if (!isOperator.value || operatorLiteMode.value) return;

    try {
        const data = await callApi('operator-stats');
        latestOperatorStats.value = Array.isArray(data?.operatorStats) ? data.operatorStats : [];
        operatorStatsGeneratedAt.value = data?.generatedAt || null;
    } catch {
        latestOperatorStats.value = Array.isArray(props.reportStats?.operatorStats) ? props.reportStats.operatorStats : [];
    }
};

const refreshStatsIfNeeded = () => {
    if (operatorLiteMode.value) return;
    refreshStats();
};

const messageToConversationPatch = (rawMessage = {}) => {
    const message = normalizeMessage(rawMessage);
    const preview = String(message.text || '').trim() || messageContentFallback(message);

    return {
        conversationId: message.conversationId,
        remoteNumber: message.remoteNumber,
        remoteName: rawMessage.remoteName || rawMessage.senderName || message.senderDisplay,
        lastMessagePreview: preview,
        lastMessageType: message.mediaKind || message.type || 'text',
        lastMessageDirection: message.direction || 'inbound',
        lastActivityAt: 'baru saja',
        lastActivityTs: Math.floor(Date.now() / 1000),
        unreadCount: activeConvo.value?.conversationId === message.conversationId ? 0 : undefined,
        lastMessageMedia: message.mediaKind ? {
            kind: message.mediaKind,
            url: message.mediaUrl,
            fileName: extractMessageFileName(message),
            mimeType: message.mediaMime,
            hasVisualPreview: Boolean(message.hasVisualMedia && message.mediaUrl),
        } : null,
    };
};

const scheduleInboxRefresh = (delay = 900) => {
    if (inboxRefreshTimerRef) clearTimeout(inboxRefreshTimerRef);
    inboxRefreshTimerRef = setTimeout(async () => {
        inboxRefreshTimerRef = null;
        await refreshInboxList();
    }, delay);
};

const scheduleNextRefresh = () => {
    if (!autoRefresh.value) return;
    if (pollRef.value) clearTimeout(pollRef.value);

    // Gunakan __reverbState.connected (actual WS state) bukan isConnected (WA runtime)
    // untuk menentukan apakah realtime benar-benar aktif.
    const wsActive = typeof window !== 'undefined' && Boolean(window.__reverbState?.connected);

    // Jika realtime aktif: poll jarang (safety net 25 detik)
    // Jika tidak: poll agresif (8 detik) agar pesan dari HP cepat muncul
    const delay = wsActive
        ? (operatorLiteMode.value ? 30_000 : 25_000)
        : (operatorLiteMode.value ? 12_000 :  8_000);
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
    if (!(await openConfirmModal('Reset State', 'Semua percakapan "pending" akan ditandai selesai dan status dikembalikan ke "open". Data pesan tidak dihapus.'))) return;
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
    if (!(await openConfirmModal('Hapus Sesi', 'Hapus sesi percakapan ini dari inbox lokal?\n\nPesan, mark, dan handover untuk percakapan ini akan dihapus permanen.'))) return;

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

const clearAllConversationsAction = async () => {
    if (isBusy.value) return;
    if (!(await openConfirmModal(
        '⚠️ Hapus Semua Inbox',
        'Seluruh percakapan, pesan, mark, dan handover di inbox akan dihapus permanen.\n\nAksi ini tidak dapat dibatalkan. Lanjutkan?'
    ))) return;

    isBusy.value = true;
    try {
        const d = await callApi('clear-all-conversations', { method: 'post' });
        appendLog('Semua inbox berhasil dihapus', d);
        // Reset state lokal dulu
        conversations.value = [];
        activeConvoId.value = '';
        conversationMessages.value = [];
        conversationMessageCache.value = {};
        // Hanya refresh dari DB lokal — JANGAN panggil refreshAll()
        // karena refreshAll() memanggil pullInbox() yang akan mengisi ulang
        // percakapan dari runtime WA
        await refreshInboxList();
    } catch (err) {
        appendLog('Gagal menghapus semua inbox', { error: err?.error });
        showToast('error', err?.error || 'Gagal menghapus semua inbox.');
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
    if (!convoId) return;

    if (activeConvoId.value === convoId) {
        markEditorOpen.value = false;
        markConversationRead(convoId);
        if (!hydrateConversationFromCache(convoId)) {
            conversationMessages.value = [];
        }
        if (typeof window !== 'undefined') {
            localStorage.setItem('wacaraka.activeConversationId', convoId);
        }
        await refreshConvoMessages(convoId, { force: true });
        return;
    }

    activeConvoId.value = convoId;
    markEditorOpen.value = false;
    applyConversationReadState(convoId);
    const hasCache = hydrateConversationFromCache(convoId);
    if (!hasCache) {
        conversationMessages.value = [];
    }
    if (typeof window !== 'undefined') {
        localStorage.setItem('wacaraka.activeConversationId', convoId);
    }
    // Langsung fetch tanpa tunggu watcher — eliminasi 1 async tick delay
    _lastSelectFetchedConvoId = convoId;
    refreshConvoMessages(convoId, { background: hasCache });

    // Mobile: auto-switch to thread view
    showMobileThread();
};

const replyToConversation = async () => {
    const text = replyText.value.trim();
    const media = mediaAttachment.value;
    if (!activeConvoId.value || (!text && !media)) { replyState.value = 'error'; return; }
    replyState.value = 'sending';
    replyProgress.value = media ? 1 : 0;
    const tempId = pushTempOutboundMessage(text, media);
    replyText.value = '';
    clearMediaAttachment();
    composerRows();

    try {
        const result = media
            ? await callApiWithXhr('send-media', {
                method: 'post',
                data: {
                    conversation_id: activeConvoId.value,
                    media_kind: media.kind,
                    media_url: media.dataUrl,
                    mime_type: media.mime,
                    file_name: media.name,
                    caption: text || null,
                    ptt: media.kind === 'audio',
                },
                onUploadProgress: (progress) => {
                    replyProgress.value = Math.max(replyProgress.value, progress);
                },
            })
            : await callApi('reply', { method: 'post', data: { conversation_id: activeConvoId.value, text } });
        markTempMessageStatus(tempId, result?.queued ? 'queued' : 'sent');
        replyProgress.value = 100;
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
        appendLog(result?.queued ? 'Balasan masuk antrean kirim' : (media ? 'Media terkirim' : 'Balasan terkirim'), { convoId: activeConvoId.value, mode: 'optimistic' });
        refreshStatsIfNeeded();
        // Selalu refresh thread setelah kirim agar pesan real dari server
        // menggantikan temp bubble — tidak bergantung pada Reverb
        refreshConvoMessages(activeConvoId.value, { background: true });
        if (!hasRealtime.value) {
            refreshInboxList();
        }
        scheduleReplyStateReset();
    } catch (err) {
        markTempMessageStatus(tempId, 'failed');
        replyState.value = 'error';
        
        const errorMsg = err?.error || err?.message || 'Terjadi kesalahan sistem.';
        appendLog('Balasan gagal', { error: errorMsg });
        showToast('error', errorMsg, 'Gagal Mengirim Pesan');
        
        // Restore input jika gagal agar ketikan user tidak hilang
        if (!replyText.value && text) replyText.value = text;
        if (!mediaAttachment.value && media) mediaAttachment.value = media;
        nextTick(() => composerRows());
        
        scheduleReplyStateReset(2200);
    }
};

const retryMessage = async (msg) => {
    if (!msg || replyState.value === 'sending') return;

    const retryMedia = msg?.metadata?.media
        ? {
            kind: msg.metadata.media.kind || msg.mediaKind || 'document',
            mime: msg.metadata.media.mimetype || msg.mediaMime || 'application/octet-stream',
            name: msg.metadata.media.fileName || extractMessageFileName(msg),
            size: msg.metadata.media.byteLength || msg.metadata.media.fileLength || msg.metadata.media.size || 0,
            dataUrl: msg.metadata.media.dataUrl || msg.metadata.media.url || msg.mediaUrl || '',
        }
        : null;

    if (retryMedia && !retryMedia.dataUrl) {
        appendLog('Retry gagal', { error: 'File media asli tidak tersedia untuk dikirim ulang.' });
        return;
    }

    replyText.value = msg.text || '';
    mediaAttachment.value = retryMedia;
    composerRows();
    await nextTick();
    replyTextareaRef.value?.focus();

    if (msg._tempId) {
        removeTempMessage(msg._tempId);
    }

    await replyToConversation();
};

const openMediaViewer = (src, options = {}) => {
    const normalized = String(src || '').trim();
    if (!normalized) return;

    mediaViewer.value = {
        src: normalized,
        alt: options.alt || 'Media WhatsApp',
        fileName: options.fileName || '',
    };
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
        refreshStatsIfNeeded();
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

const reopenConvo = async () => {
    if (!activeConvoId.value) return;
    try {
        await callApi('reopen', { method: 'post', data: { conversation_id: activeConvoId.value } });
        await refreshInboxList();
    } catch { /* silent */ }
};

const deleteMessageAction = async (messageId) => {
    if (!messageId || !(await openConfirmModal('Hapus Pesan', 'Yakin ingin menghapus pesan ini? Hapus data bersifat permanen.'))) return;
    try {
        await callApi('delete-message', { method: 'post', data: { message_id: messageId } });
        appendLog('Pesan dihapus');
        await refreshConvoMessages();
        await refreshStatsIfNeeded();
    } catch (err) {
        appendLog('Gagal menghapus pesan', { error: err?.error });
        showToast('error', err?.error || 'Gagal menghapus pesan.');
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
                persistConversationCache(event.message.conversationId, conversationMessages.value);
                markConversationRead(event.message.conversationId);
            }
            mergeConversation(messageToConversationPatch(event.message));
            noteConversationActivity(event?.message?.conversationId);
            // Gunakan debounce untuk semua mode agar tidak flicker saat banyak pesan masuk berurutan
            scheduleInboxRefresh(operatorLiteMode.value ? 1200 : 1500);
            refreshStatsIfNeeded();
        })
        .listen('.wa-caraka.message.synced', (event) => {
            const message = event?.message;
            if (!message) return;

            if (!activeConvoId.value || message.conversationId === activeConvoId.value) {
                upsertConversationMessage(message);
                persistConversationCache(message.conversationId, conversationMessages.value);
            }

            mergeConversation(messageToConversationPatch(message));
            noteConversationActivity(message.conversationId);
            if (operatorLiteMode.value) {
                scheduleInboxRefresh(1800);
            }
            refreshStatsIfNeeded();
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
// Track konversasi yang sudah di-fetch langsung dari selectConversation
// agar watcher tidak double-fetch untuk konversasi yang sama.
let _lastSelectFetchedConvoId = '';

watch(activeConvoId, (id) => {
    if (id) {
        activeThreadRequestId.value = ++nextThreadRequestId;
        applyConversationReadState(id);
        const hasCache = hydrateConversationFromCache(id);
        if (!hasCache) {
            conversationMessages.value = [];
        }
        // Skip jika selectConversation sudah langsung trigger fetch untuk id ini
        if (_lastSelectFetchedConvoId === id) {
            _lastSelectFetchedConvoId = '';
        } else {
            refreshConvoMessages(id, { background: hasCache });
        }
    } else {
        activeThreadRequestId.value = ++nextThreadRequestId;
        setThreadLoadingState(false);
    }
    syncMarkFormFromActive();
});

watch(replyText, () => {
    nextTick(() => composerRows());
});

watch([conversationSearch, conversationFilter], () => {
    resetVisibleConversationWindow();
});

watch(() => activeConvo.value?.customerMark, () => {
    syncMarkFormFromActive();
}, { deep: true });

watch([threadZoom], ([zoom]) => {
    if (typeof window === 'undefined') return;
    localStorage.setItem('wacaraka.thread.zoom', String(zoom));
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
        // threadVisibleCount fixed at 24, tidak perlu restore dari localStorage
        activeConvoId.value = localStorage.getItem('wacaraka.activeConversationId') || '';
    }

    await fetchHandoverStatus();
    await refreshAll();
    if (!operatorLiteMode.value && isSuperAdmin.value) {
        await loadTickets();
    }
    setAutoRefresh(true);
    connectRealtime();
    nextTick(() => composerRows());
    window.addEventListener('keydown', handleGlobalShortcuts);
});

onUnmounted(() => {
    if (pollRef.value) clearTimeout(pollRef.value);
    if (replyCooldownRef.value) clearTimeout(replyCooldownRef.value);
    if (sendCooldownRef.value) clearTimeout(sendCooldownRef.value);
    if (threadLoadingDelayRef) clearTimeout(threadLoadingDelayRef);
    if (inboxRefreshTimerRef) clearTimeout(inboxRefreshTimerRef);
    stopQrPolling();
    window.removeEventListener('keydown', handleGlobalShortcuts);
    window.Echo?.leave('lawangsewu.wacaraka.inbox');
});
</script>

<template>
    <Head title="WA Caraka" />

    <LawangsewuLayout current-route="wacaraka" :nav-groups="navGroups" :app-meta="appMeta">

        <!-- ░░ Operator Desk Banner ░░ -->
        <section class="relative overflow-hidden rounded-[1.75rem] border border-[var(--accent-border)] bg-[radial-gradient(circle_at_top_right,rgba(56,189,248,0.15),transparent_36%),linear-gradient(145deg,rgba(5,10,23,0.96),rgba(15,23,42,0.95))] p-3.5 text-white shadow-[0_20px_60px_rgba(2,6,23,0.45)] lg:px-5 lg:py-3.5 mb-4">
            <div class="absolute -right-10 -top-10 h-52 w-52 rounded-full bg-sky-500/10 blur-3xl pointer-events-none" />
            <div class="absolute -bottom-12 left-1/3 h-44 w-44 rounded-full bg-cyan-400/8 blur-3xl pointer-events-none" />

            <div class="relative z-10 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.3em] text-sky-300/90">WA Caraka • Operator Desk</p>
                </div>
                
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
                    <!-- Toggle Interkom Sidebar -->
                    <button
                        @click="toggleInterkom"
                        class="relative rounded-full border px-3 py-1 text-[11px] font-bold transition"
                        :class="showInterkom
                            ? 'border-violet-400/50 bg-violet-400/15 text-violet-200 hover:bg-violet-400/25'
                            : 'border-white/20 bg-white/5 text-white/60 hover:bg-white/10'"
                        title="Interkom Operator"
                    >
                        💬 Interkom
                        <span
                            v-if="!showInterkom && unreadInterkom > 0"
                            class="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-rose-500 text-[8px] font-black text-white leading-none"
                        >{{ unreadInterkom > 9 ? '9+' : unreadInterkom }}</span>
                    </button>
                </div>
            </div>

            <div v-if="!operatorLiteMode" class="relative z-10 mt-2.5 grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-6">
                <article v-for="card in [
                    { label: 'Aktif', value: latestConvoStats.open, color: 'text-emerald-300' },
                    { label: 'Belum Dibalas', value: latestConvoStats.pending, color: latestConvoStats.pending === 0 ? 'text-emerald-300' : (latestConvoStats.pending > 10 ? 'text-rose-300' : 'text-amber-300') },
                    { label: 'Pesan Masuk Baru', value: latestMsgStats.unreplied, color: 'text-cyan-300' },
                    { label: 'Percakapan', value: latestConvoStats.total, color: 'text-sky-300' },
                    { label: 'Selesai', value: latestConvoStats.closed, color: 'text-emerald-300' },
                    { label: 'Pending Handover', value: latestConvoStats.pendingHandovers, color: latestConvoStats.pendingHandovers > 0 ? 'text-orange-300' : 'text-slate-400' },
                ]" :key="`operator-summary-${card.label}`" class="rounded-xl border border-white/10 bg-white/5 px-2.5 py-2 backdrop-blur transition hover:bg-white/10">
                    <p class="text-[8px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ card.label }}</p>
                    <p class="mt-0.5 text-base font-black leading-none sm:text-[17px]" :class="card.color">{{ card.value ?? 0 }}</p>
                </article>
            </div>
        </section>

        <!-- ░░ Main: Inbox + Thread + Interkom ░░ -->
        <div class="interkom-outer flex min-w-0 gap-3 sm:gap-4 xl:gap-5">

        <!-- Inbox + Thread grid -->
        <section class="min-w-0 flex-1 grid grid-cols-1 gap-3 sm:grid-cols-[minmax(230px,37%),minmax(0,1fr)] sm:gap-4 xl:grid-cols-[clamp(380px,30%,460px),minmax(0,1fr)] xl:gap-5">

            <!-- Sidebar: Conversation List -->
            <aside
                class="flex min-w-0 flex-col rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] shadow-[var(--shadow)] max-h-[calc(100vh-1rem)] xl:sticky xl:top-2 xl:rounded-[2rem]"
                :class="{ 'hidden': isMobile && mobileView !== 'inbox', 'sm:flex': true }"
            >
                <div class="flex items-center justify-between gap-2 border-b border-[var(--border)] px-3 py-2.5 flex-shrink-0 sm:px-4 sm:py-3 xl:px-5">
                    <div>
                        <h2 class="text-sm font-black text-[var(--text-1)] sm:text-base">Inbox</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="rounded-full bg-[var(--surface-2)] px-2 py-0.5 text-[9px] font-bold text-[var(--text-2)] sm:px-2.5 sm:text-[10px]">{{ filteredConversations.length }}/{{ conversations.length }}</span>
                    </div>
                </div>

                <p class="px-3 py-1.5 text-[9px] text-[var(--text-2)] flex-shrink-0 sm:px-4 sm:text-[10px] xl:px-5">{{ inboxSyncText }}</p>

                <div class="grid gap-1.5 border-b border-[var(--border)] px-3 pb-2.5 sm:px-4 xl:px-5">
                    <input
                        v-model="conversationSearch"
                        type="text"
                        placeholder="Cari nama, nomor, alias, preview..."
                        class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-[11px] text-[var(--text-1)] outline-none transition placeholder:text-[var(--text-2)] focus:border-sky-400/60"
                    />
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="filter in [
                                { value: 'all', label: 'Semua' },
                                { value: 'unread', label: 'Belum Dibaca' },
                                { value: 'mine', label: 'Milik Saya' },
                                { value: 'group', label: 'Grup' },
                            ]"
                            :key="filter.value"
                            @click="conversationFilter = filter.value"
                            class="rounded-full border px-2 py-1 text-[9px] font-bold transition"
                            :class="conversationFilter === filter.value
                                ? 'border-sky-400/50 bg-sky-500/12 text-sky-600'
                                : 'border-[var(--border)] bg-[var(--surface-2)] text-[var(--text-2)] hover:border-sky-400/40 hover:text-sky-500'"
                        >
                            {{ filter.label }}
                        </button>
                    </div>
                </div>

                <div ref="sidebarListEl" @scroll.passive="maybeExpandConversationWindow" class="flex-1 min-h-0 overflow-y-auto divide-y divide-[var(--border)]">
                    <button v-for="c in renderedConversations" :key="c.conversationId"
                            @click="selectConversation(c.conversationId)"
                            @contextmenu.prevent="openContextMenu($event, c)"
                            @mouseenter="prefetchConversation(c.conversationId)"
                            @focus="prefetchConversation(c.conversationId)"
                            @touchstart.passive="prefetchConversation(c.conversationId)"
                            class="group w-full px-2 py-1.5 text-left transition-all duration-200 sm:px-2.5 sm:py-1.5 xl:px-3 xl:py-2"
                            :class="[
                                activeConvoId === c.conversationId ? 'conversation-active bg-sky-500/10 border-l-2 border-sky-400' : 'hover:bg-[var(--surface-2)] border-l-2 border-transparent',
                                isConversationRecentlyUpdated(c.conversationId) ? 'conversation-fresh' : '',
                            ]">
                        <div class="flex items-start gap-2 sm:gap-2.5">
                            <div class="mt-0.5 h-7 w-7 flex-shrink-0 overflow-hidden rounded-full ring-1 ring-white/40 sm:h-7.5 sm:w-7.5 xl:h-8 xl:w-8">
                                <img
                                    v-if="c.profilePhotoUrl"
                                    :src="c.profilePhotoUrl"
                                    alt="Foto profil WA"
                                    class="h-full w-full object-cover"
                                    referrerpolicy="no-referrer"
                                    @error="onProfileImageError(c.conversationId)"
                                />
                                <div v-else class="flex h-full w-full items-center justify-center text-[9px] font-black text-white sm:text-[10px] xl:text-[11px]"
                                     :class="avatarToneClassFor(c.conversationId || c.remoteNumber)">
                                    {{ initialsFromName(c.displayTitle) }}
                                </div>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="truncate text-[10px] font-bold text-[var(--text-1)] sm:text-[11px] xl:text-[12px]">
                                        <span v-if="c.isGroup" class="mr-1">👥</span>{{ c.displayTitle }}
                                    </p>
                                    <!-- Unread badge -->
                                    <span v-if="c.unreadCount > 0" class="unread-pill flex-shrink-0 rounded-full bg-rose-500 px-1.5 py-0.5 text-[8px] font-black text-white sm:px-2 sm:text-[9px]">
                                        {{ c.unreadCount }}
                                    </span>
                                </div>

                                <p class="mt-0.5 text-[8px] text-[var(--text-2)] font-mono sm:text-[8px]">{{ primaryContactNumber(c.remoteNumber) }}</p>
                                <p class="mt-0.5 line-clamp-1 text-[8px] text-[var(--text-2)] sm:text-[9px]">
                                    {{ conversationPreviewText(c) }}
                                </p>
                                <div v-if="inboxPreviewMedia(c)" class="mt-1.5 flex items-center gap-2">
                                    <button v-if="hasInboxVisualPreview(c)"
                                            @click.stop="openMediaViewer(inboxPreviewMedia(c).url, { alt: `Preview ${inboxPreviewLabel(c)}`, fileName: inboxPreviewMedia(c).fileName })"
                                            class="overflow-hidden rounded-xl border border-slate-200/80 bg-white/80 transition hover:border-sky-300/70">
                                        <img :src="inboxPreviewMedia(c).url"
                                             :alt="`Preview ${inboxPreviewLabel(c)}`"
                                             loading="lazy"
                                             decoding="async"
                                             class="h-8 w-8 object-cover" />
                                    </button>
                                    <div v-else class="inline-flex items-center rounded-full border border-slate-200 bg-white/85 px-2 py-1 text-[8px] font-bold text-slate-500">
                                        {{ inboxPreviewLabel(c) }}
                                    </div>
                                    <p class="truncate text-[8px] text-[var(--text-2)]">
                                        {{ inboxPreviewMedia(c).fileName || `Lampiran ${inboxPreviewLabel(c)}` }}
                                    </p>
                                </div>
                                <p v-if="c.groupName && c.isGroup" class="mt-0.5 text-[7px] text-[var(--text-2)] sm:text-[8px]">
                                    Nama Group: <span class="font-semibold">{{ c.groupName }}</span>
                                </p>
                                <p v-if="c.remoteName && !c.isGroup" class="mt-0.5 text-[7px] text-[var(--text-2)] sm:text-[8px]">
                                    Nama WA: <span class="font-semibold">{{ c.remoteName }}</span>
                                </p>

                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <span class="rounded-full px-1.5 py-0.5 text-[8px] font-bold sm:px-2 sm:text-[9px]"
                                          :class="{
                                              'bg-emerald-500/15 text-emerald-600': c.status === 'open',
                                              'bg-amber-500/15 text-amber-600': c.status === 'pending',
                                              'bg-slate-500/15 text-slate-500': c.status === 'closed',
                                          }">
                                        {{ conversationStatusLabel(c.status) }}
                                    </span>
                                    <span v-if="c.customerMark" class="rounded-full border px-1.5 py-0.5 text-[8px] font-bold sm:px-2 sm:text-[9px]"
                                          :class="markToneClass(c.customerMark.tone)">
                                        {{ c.customerMark.label }}
                                    </span>
                                    <span v-if="c.ownership === 'mine'" class="rounded-full bg-sky-500/12 px-1.5 py-0.5 text-[8px] font-bold text-sky-600 sm:px-2 sm:text-[9px]">
                                        aktif kamu
                                    </span>
                                    <span v-if="c.owner" class="rounded-full bg-blue-500/12 px-1.5 py-0.5 text-[8px] font-semibold text-blue-600 sm:px-2 sm:text-[9px]">
                                        {{ c.owner?.alias || c.owner?.name }}
                                    </span>
                                    <span v-if="c.justClaimed" class="rounded-full bg-violet-500/12 px-1.5 py-0.5 text-[8px] font-bold text-violet-600 sm:px-2 sm:text-[9px]">
                                        baru takeover
                                    </span>
                                    <span v-if="c.ownerPresence === 'active'" class="rounded-full bg-emerald-500/12 px-1.5 py-0.5 text-[8px] font-bold text-emerald-600 sm:px-2 sm:text-[9px]">
                                        aktif sekarang
                                    </span>
                                    <span v-else-if="c.ownerPresence === 'standby'" class="rounded-full bg-sky-500/12 px-1.5 py-0.5 text-[8px] font-bold text-sky-600 sm:px-2 sm:text-[9px]">
                                        standby
                                    </span>
                                    <span v-else-if="c.ownerPresence === 'idle'" class="rounded-full bg-slate-500/12 px-1.5 py-0.5 text-[8px] font-bold text-slate-500 sm:px-2 sm:text-[9px]">
                                        idle
                                    </span>
                                    <span v-if="c.pendingHandover" class="rounded-full bg-orange-500/15 px-1.5 py-0.5 text-[8px] font-bold text-orange-600 sm:px-2 sm:text-[9px]">
                                        handover ⏳
                                    </span>
                                </div>

                                <p class="mt-1 text-[7px] text-[var(--text-2)] sm:mt-1 sm:text-[8px]">
                                    {{ c.lastActivityAt || '—' }}
                                    <span v-if="c.claimedAt" class="ml-1 text-[var(--text-2)]/80">· diklaim {{ c.claimedAt }}</span>
                                </p>
                            </div>
                        </div>
                    </button>

                    <div v-if="filteredConversations.length === 0" class="px-5 py-10 text-center">
                        <p class="text-sm text-[var(--text-2)]">Belum ada percakapan.</p>
                        <p class="mt-1 text-xs text-[var(--text-2)]">Pesan akan muncul saat runtime mengirim webhook atau pull inbox berhasil.</p>
                    </div>

                    <div v-else-if="renderedConversations.length < filteredConversations.length" class="px-4 py-4 text-center">
                        <button @click="visibleConversationCount = Math.min(filteredConversations.length, visibleConversationCount + 30)"
                                class="rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-1.5 text-[10px] font-bold text-[var(--text-2)] transition hover:border-sky-400/40 hover:text-sky-500">
                            Muat {{ Math.min(30, filteredConversations.length - renderedConversations.length) }} chat lagi
                        </button>
                    </div>
                </div>

            </aside>

            <!-- Main: Thread + Reply -->
            <div
                class="min-w-0 flex flex-col gap-3 sm:gap-4"
                :class="{ 'hidden': isMobile && mobileView !== 'thread', 'sm:flex': true }"
            >

                <!-- Thread Header -->
                <div class="rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] p-3 shadow-[var(--shadow)] sm:p-4 xl:rounded-[2rem] xl:p-5">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <!-- Mobile back button -->
                            <button
                                v-if="isMobile"
                                @click="backToMobileInbox"
                                class="mt-1 flex h-8 w-8 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface-2)] text-[var(--text-2)] transition hover:border-sky-400/50 hover:text-sky-400 flex-shrink-0"
                                title="Kembali ke Inbox"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                            </button>
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
                                    {{ conversationStatusLabel(activeConvo.status) }}
                                </span>
                                <!-- Owner -->
                                <span v-if="activeConvo.owner" class="rounded-full border border-blue-200 bg-blue-50 px-2 py-1 text-[10px] font-semibold text-blue-700 sm:px-2.5 sm:text-xs">
                                    Ditangani: {{ activeConvo.owner?.alias || activeConvo.owner?.name }}
                                    <span v-if="iMineConvo" class="ml-1 text-blue-400">(kamu)</span>
                                </span>
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

                            <!-- Close (owner, admin, or unclaimed) -->
                            <button v-if="(iMineConvo || isAdmin || activeConvo.ownership === 'unclaimed') && activeConvo.status !== 'closed'"
                                    @click="closeConvo"
                                    class="rounded-xl border border-slate-200 px-2 py-1.5 text-[10px] text-slate-600 hover:bg-slate-100 transition sm:px-3 sm:text-xs">
                                ✓ Selesaikan
                            </button>

                            <!-- Reopen (Superadmin only) -->
                            <button v-if="isSuperAdmin && activeConvo.status === 'closed'"
                                    @click="reopenConvo"
                                    class="rounded-xl border border-amber-200 bg-amber-50 px-2 py-1.5 text-[10px] text-amber-700 hover:bg-amber-100 transition sm:px-3 sm:text-xs">
                                ↻ Buka Kembali
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

                            <button v-if="isAdmin" @click="clearConversationAction"
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
                     :class="[activeConvo && !customBackgroundStyle ? 'thread-surface' : '', isDark && activeConvo && !customBackgroundStyle ? 'thread-surface-dark' : '']"
                     :style="[threadViewportStyle, activeConvo && !customBackgroundStyle ? threadSurfaceDarkStyle : {}, activeConvo && customBackgroundStyle ? customBackgroundStyle : {}]">

                    <!-- Particle Canvas (dark mode only) -->
                    <canvas
                        v-if="isDark && activeConvo && !customBackgroundStyle"
                        ref="particleCanvasRef"
                        class="particle-canvas"
                        aria-hidden="true"
                    />



                    <div v-if="!activeConvo" class="grid min-h-[280px] place-items-center text-center text-sm text-[var(--text-2)]">
                        <div>
                            <p class="text-4xl mb-3">💬</p>
                            <p>Pilih percakapan di sebelah kiri untuk memulai.</p>
                        </div>
                    </div>

                    <div v-else-if="threadLoadingVisible" class="space-y-3 py-4 sm:space-y-4">
                        <div v-for="placeholder in 4" :key="`thread-skeleton-${placeholder}`" class="flex" :class="placeholder % 2 === 0 ? 'justify-end' : 'justify-start'">
                            <div class="thread-skeleton w-[72%] rounded-3xl px-4 py-4 sm:w-[58%]"></div>
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

                                <div v-if="isImageMessage(msg) || isStickerMessage(msg)" class="space-y-2">
                                    <img
                                        :src="msg.mediaUrl"
                                        :alt="msg.mediaKind === 'sticker' ? 'Sticker WhatsApp' : 'Media WhatsApp'"
                                        loading="lazy"
                                        decoding="async"
                                        class="max-h-80 w-auto rounded-xl border border-slate-200/80 object-cover shadow-sm"
                                        :class="msg.mediaKind === 'sticker' ? 'h-28 w-28 object-contain border-0 bg-transparent shadow-none' : ''"
                                        @click="openMediaViewer(msg.mediaUrl, { alt: msg.mediaKind === 'sticker' ? 'Sticker WhatsApp' : 'Media WhatsApp', fileName: extractMessageFileName(msg) })"
                                    />
                                    <p v-if="msg.mediaCaption" class="text-xs leading-relaxed whitespace-pre-wrap sm:text-sm">{{ msg.mediaCaption }}</p>
                                </div>
                                <div v-else-if="isVideoMessage(msg)" class="space-y-2">
                                    <video :src="msg.mediaUrl" controls preload="metadata" class="max-h-80 w-full rounded-xl border border-slate-200/80 bg-slate-900 shadow-sm"></video>
                                    <p v-if="msg.mediaCaption" class="text-xs leading-relaxed whitespace-pre-wrap sm:text-sm">{{ msg.mediaCaption }}</p>
                                </div>
                                <div v-else-if="isAudioMessage(msg)" class="space-y-2">
                                    <div class="rounded-2xl border border-slate-200/80 bg-white/80 p-3">
                                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Voice / Audio</p>
                                        <audio :src="msg.mediaUrl" controls preload="metadata" class="mt-2 w-full"></audio>
                                    </div>
                                    <p v-if="msg.mediaCaption" class="text-xs leading-relaxed whitespace-pre-wrap sm:text-sm">{{ msg.mediaCaption }}</p>
                                </div>
                                <div v-else class="space-y-1.5">
                                    <p class="text-xs leading-relaxed whitespace-pre-wrap sm:text-sm">{{ msg.text || messageContentFallback(msg) }}</p>
                                    <div v-if="msg.mediaUrl && msg.mediaKind === 'document'" class="rounded-2xl border border-slate-200/80 bg-white/85 p-3">
                                        <p class="truncate text-sm font-black text-slate-800">{{ extractMessageFileName(msg) }}</p>
                                        <p class="mt-1 text-[11px] text-slate-500">{{ documentMetaText(msg) }}</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <a
                                                :href="msg.mediaUrl"
                                                :download="extractMessageFileName(msg)"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center rounded-lg border border-sky-300/60 bg-sky-50/70 px-2.5 py-1.5 text-[10px] font-bold text-sky-700 transition hover:border-sky-400 hover:bg-sky-100/80"
                                            >
                                                Buka / Unduh Dokumen
                                            </a>
                                        </div>
                                    </div>
                                    <a
                                        v-if="msg.mediaUrl && msg.mediaKind && msg.mediaKind !== 'document'"
                                        :href="msg.mediaUrl"
                                        :download="msg.metadata?.media?.fileName || ''"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="inline-flex items-center rounded-lg border border-sky-300/60 bg-sky-50/60 px-2 py-1 text-[10px] font-bold text-sky-700 transition hover:border-sky-400 hover:bg-sky-100/70"
                                    >
                                        Unduh {{ messageTypeLabel(msg.mediaKind, msg) }}
                                    </a>
                                </div>

                                <div class="mt-1.5 flex items-center justify-between gap-2 text-[10px]"
                                     :class="bubbleFooterClass(msg)">
                                    <span>{{ messageTypeLabel(msg.type, msg) }}</span>
                                    <div class="flex items-center gap-2">
                                        <button v-if="isAdmin && msg.id" @click="deleteMessageAction(msg.id)"
                                            class="text-rose-500/80 hover:text-rose-500 transition" title="Hapus pesan (Admin/Superadmin)">
                                            ✕
                                        </button>
                                        <button v-if="msg.direction === 'outbound' && msg.status === 'failed'" @click="retryMessage(msg)"
                                            class="rounded-full border border-amber-300/70 bg-amber-50/90 px-2 py-0.5 text-[9px] font-black text-amber-700 transition hover:border-amber-400 hover:bg-amber-100"
                                            title="Kirim ulang pesan ini">
                                            Retry
                                        </button>
                                        <button v-if="msg.direction === 'outbound' && msg.status === 'failed' && msg.id" @click="deleteMessageAction(msg.id)"
                                            class="rounded-full border border-rose-300/70 bg-rose-50/90 px-2 py-0.5 text-[9px] font-black text-rose-700 transition hover:border-rose-400 hover:bg-rose-100"
                                            title="Hapus pesan yang gagal ini">
                                            Hapus
                                        </button>
                                        <button v-else-if="msg.direction === 'outbound' && msg.status === 'failed' && msg._tempId && !msg.id" @click="removeTempMessage(msg._tempId)"
                                            class="rounded-full border border-rose-300/70 bg-rose-50/90 px-2 py-0.5 text-[9px] font-black text-rose-700 transition hover:border-rose-400 hover:bg-rose-100"
                                            title="Hapus pesan gagal dari daftar">
                                            Hapus
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
                    <input
                        ref="mediaInputRef"
                        type="file"
                        class="hidden"
                        accept="image/*,video/*,audio/*,.pdf,.rtf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.7z,.csv,.txt,.json,.xml,.webp,.heic,.heif"
                        @change="onMediaFileChange"
                    />
                    <div class="flex flex-col gap-2 sm:gap-3 xl:flex-row">
                        <textarea v-model="replyText"
                                  ref="replyTextareaRef"
                                  rows="3"
                                  :disabled="!activeConvo || !canReply"
                                  :placeholder="!activeConvo ? 'Pilih percakapan' : !canReply ? 'Tidak diizinkan membalas' : `Balas ke ${activeConvo?.displayTitle}...` "
                                  class="flex-1 resize-none rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2.5 text-[11px] text-[var(--text-1)] outline-none transition placeholder:text-[var(--text-2)] focus:border-sky-400/60 disabled:opacity-50 disabled:cursor-not-allowed sm:px-4 sm:py-3 sm:text-xs xl:text-sm"
                                  @keydown.ctrl.enter="replyToConversation"
                                  @keydown="handleReplyKeydown" />

                        <div class="flex flex-col gap-2 xl:w-auto">
                            <div class="flex items-center gap-2">
                                <button @click="pickMediaFile"
                                        :disabled="replyState === 'sending' || !activeConvo || !canReply"
                                        class="rounded-xl border border-[var(--border)] px-3 py-2 text-[10px] font-bold text-[var(--text-2)] transition hover:border-sky-400/50 hover:text-sky-500 disabled:opacity-40 disabled:cursor-not-allowed">
                                    + Media
                                </button>
                                <button v-if="mediaAttachment" @click="clearMediaAttachment"
                                        :disabled="replyState === 'sending'"
                                        class="rounded-xl border border-rose-300/60 px-3 py-2 text-[10px] font-bold text-rose-500 transition hover:border-rose-400 hover:text-rose-600 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Hapus
                                </button>
                            </div>

                            <div v-if="mediaAttachment" class="max-w-[260px] rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-2.5 text-[10px] text-[var(--text-2)]">
                                <div v-if="isPreviewableAttachment && mediaAttachment.kind === 'image'" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white/80">
                                    <img :src="attachmentPreviewUrl" alt="Preview lampiran" class="max-h-32 w-full object-cover" />
                                </div>
                                <div v-else-if="isPreviewableAttachment && mediaAttachment.kind === 'sticker'" class="flex justify-center rounded-xl border border-slate-200/80 bg-white/80 p-3">
                                    <img :src="attachmentPreviewUrl" alt="Preview stiker" class="h-20 w-20 object-contain" />
                                </div>
                                <div v-else-if="isPreviewableAttachment && mediaAttachment.kind === 'video'" class="overflow-hidden rounded-xl border border-slate-200/80 bg-slate-950">
                                    <video :src="attachmentPreviewUrl" class="max-h-32 w-full object-cover" muted playsinline></video>
                                </div>
                                <div v-else-if="isPreviewableAttachment && mediaAttachment.kind === 'audio'" class="rounded-xl border border-slate-200/80 bg-white/80 p-3">
                                    <audio :src="attachmentPreviewUrl" controls preload="metadata" class="w-full"></audio>
                                </div>

                                <p class="mt-2 truncate font-semibold text-[var(--text-1)]">
                                    {{ mediaAttachment.name }}
                                </p>
                                <p class="mt-0.5">
                                    {{ mediaAttachment.kind }}<span v-if="humanFileSize(mediaAttachment.size)"> · {{ humanFileSize(mediaAttachment.size) }}</span>
                                </p>
                                <p class="mt-1 text-[9px] text-[var(--text-2)]/85">
                                    Batas kirim saat ini {{ humanFileSize(MAX_MEDIA_FILE_BYTES) }}
                                </p>
                            </div>

                            <p class="text-center text-[9px] text-[var(--text-2)]">
                                {{ activeConvo ? 'Enter kirim • Shift+Enter baris baru • J/K pindah chat • Ctrl+K cari' : 'Pilih percakapan untuk mulai membalas' }}
                            </p>

                            <div v-if="replyState === 'sending' && mediaAttachment" class="w-full max-w-[260px] rounded-full bg-slate-200/80">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-sky-500 via-cyan-400 to-emerald-400 transition-all duration-200" :style="{ width: `${Math.max(4, replyProgress)}%` }"></div>
                            </div>
                            <p v-if="replyState === 'sending' && mediaAttachment" class="text-center text-[9px] font-semibold text-[var(--text-2)]">
                                Upload media {{ replyProgress }}%
                            </p>

                            <button @click="replyToConversation"
                                    :disabled="replyState === 'sending' || !activeConvo || !canReply || (!replyText.trim() && !mediaAttachment)"
                                    class="send-btn flex-1 min-w-[88px] rounded-2xl px-4 py-2.5 text-xs font-black text-white shadow-md transition disabled:opacity-40 disabled:cursor-not-allowed sm:min-w-[110px] sm:px-5 sm:py-3 sm:text-sm"
                                    :class="replyState === 'sending' ? 'send-btn-sending' : ''">
                                <span class="inline-flex items-center justify-center gap-1.5">
                                    <span v-if="replyState === 'sending'" class="send-dot-loader" aria-hidden="true"></span>
                                    <span>{{ replyState === 'sending' ? 'Kirim cepat' : replyState === 'sent' ? '✓ Masuk antrean' : replyState === 'error' ? '✕ Gagal' : (mediaAttachment ? '↑ Kirim Media' : '↑ Kirim') }}</span>
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ── Interkom Sidebar Kanan ── -->
        <transition name="interkom-slide">
            <aside
                v-if="showInterkom"
                class="interkom-sidebar flex-shrink-0 xl:sticky xl:top-2 self-start"
            >
                <div class="flex items-center justify-between px-3 py-2 border-b border-[var(--border)] bg-[var(--surface-2)]/70 rounded-t-[1.5rem] xl:rounded-t-[2rem]">
                    <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-2)]">💬 Interkom</span>
                    <button
                        @click="toggleInterkom"
                        class="h-6 w-6 flex items-center justify-center rounded-lg text-[var(--text-3)] hover:bg-[var(--surface-3)] hover:text-[var(--text-1)] transition text-sm"
                        title="Sembunyikan Interkom"
                    >✕</button>
                </div>
                <InterkomPanel
                    ref="interkomRef"
                    :visible="showInterkom"
                    class="rounded-b-[1.5rem] xl:rounded-b-[2rem] border border-t-0 border-[var(--border)] shadow-[var(--shadow)] overflow-hidden"
                    style="height: calc(100vh - 10rem); min-height: 400px;"
                />
            </aside>
        </transition>

        </div><!-- end .interkom-outer -->

        <!-- ░░ Tiket: Pengaduan & Konsultasi ░░ -->
        <section v-if="isSuperAdmin" class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] shadow-[var(--shadow)] overflow-hidden">
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
        <section v-if="!operatorLiteMode" class="mt-6 grid gap-6 lg:grid-cols-2">
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

            <article v-if="isOperator" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-black text-[var(--text-1)]">Statistik Balasan WA per User</h2>
                        <p class="mt-1 text-xs text-[var(--text-2)]">Ringkasan operator yang paling aktif membalas percakapan WhatsApp.</p>
                    </div>
                    <span class="rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-[var(--text-2)]">
                        {{ operatorStatsUpdatedAtText }}
                    </span>
                </div>

                <div v-if="currentOperatorStat" class="mt-5 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--text-2)]">Balasan Hari Ini</p>
                        <p class="mt-2 text-2xl font-black text-emerald-600">{{ currentOperatorStat.outboundToday || 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--text-2)]">Total Balasan</p>
                        <p class="mt-2 text-2xl font-black text-sky-600">{{ currentOperatorStat.outboundMessages || 0 }}</p>
                    </div>
                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] p-4">
                        <p class="text-[10px] font-bold uppercase tracking-[0.16em] text-[var(--text-2)]">Percakapan Aktif</p>
                        <p class="mt-2 text-2xl font-black text-violet-600">{{ currentOperatorStat.conversations || 0 }}</p>
                    </div>
                </div>

                <div class="mt-5 overflow-hidden rounded-3xl border border-[var(--border)]">
                    <div class="grid grid-cols-[minmax(0,1.6fr),84px,84px] gap-3 border-b border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-[10px] font-black uppercase tracking-[0.16em] text-[var(--text-2)]">
                        <span>Operator</span>
                        <span class="text-right">Hari Ini</span>
                        <span class="text-right">Total</span>
                    </div>
                    <div class="max-h-[340px] overflow-y-auto divide-y divide-[var(--border)]">
                        <div v-for="row in latestOperatorStats" :key="row.id" class="grid grid-cols-[minmax(0,1.6fr),84px,84px] gap-3 px-4 py-3" :class="row.id === myId ? 'bg-sky-500/6' : 'bg-transparent'">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-black text-[var(--text-1)]">{{ row.name }}</p>
                                <p class="mt-1 text-[11px] text-[var(--text-2)]">{{ row.conversations || 0 }} percakapan · {{ row.lastReplyAt || 'belum ada balasan' }}</p>
                            </div>
                            <p class="text-right text-sm font-black text-emerald-600">{{ row.outboundToday || 0 }}</p>
                            <p class="text-right text-sm font-black text-sky-600">{{ row.outboundMessages || 0 }}</p>
                        </div>
                        <div v-if="latestOperatorStats.length === 0" class="px-4 py-8 text-center text-sm text-[var(--text-2)]">
                            Belum ada data balasan operator.
                        </div>
                    </div>
                </div>
            </article>

            <!-- Device Status + QR -->
            <article v-else-if="isSuperAdmin" class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-black text-[var(--text-1)]">Status Device WA</h2>
                    <div class="flex flex-wrap gap-2">
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
                        <button v-if="isSuperAdmin" @click="clearAllConversationsAction" :disabled="isBusy"
                                class="rounded-xl border border-rose-400 bg-rose-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-rose-700 transition disabled:opacity-40" title="Hapus seluruh percakapan, pesan, dan handover dari inbox — tidak dapat dibatalkan">
                            🗑 Hapus Semua Inbox
                        </button>
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
        <section v-if="isSuperAdmin" class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
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
        <section v-if="isSuperAdmin" class="mt-6 rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-[var(--shadow)]">
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
            <div v-if="mediaViewerOpen"
                 class="fixed inset-0 z-[70] flex items-center justify-center bg-slate-950/78 px-4 py-6 backdrop-blur-sm"
                 @click.self="mediaViewer = null">
                <div class="relative max-h-full max-w-5xl">
                    <button @click="mediaViewer = null"
                            class="absolute right-3 top-3 z-10 rounded-full border border-white/20 bg-slate-900/70 px-3 py-1.5 text-xs font-bold text-white transition hover:bg-slate-800">
                        Tutup
                    </button>
                    <img :src="mediaViewer?.src"
                         :alt="mediaViewer?.alt || 'Media WhatsApp'"
                         class="max-h-[82vh] max-w-[92vw] rounded-3xl border border-white/10 bg-slate-950 object-contain shadow-[0_20px_60px_rgba(15,23,42,0.45)]" />
                    <div v-if="mediaViewer?.fileName" class="mt-3 text-center text-xs font-semibold text-slate-200">
                        {{ mediaViewer.fileName }}
                    </div>
                </div>
            </div>

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

        <!-- Context Menu untuk Klik Kanan di Sidebar -->
        <Teleport to="body">
            <div v-if="contextMenu.isOpen"
                 class="fixed z-[9999] bg-white rounded-xl shadow-xl shadow-slate-900/10 ring-1 ring-slate-200 w-52 overflow-hidden transform-gpu origin-top-left transition-all duration-150"
                 :style="{ top: contextMenu.y + 'px', left: contextMenu.x + 'px' }"
                 @click.stop>
                <div class="px-3 py-2 bg-slate-50 border-b border-slate-100">
                    <p class="text-[10px] font-bold text-slate-500 truncate">{{ contextMenu.convo?.displayTitle }}</p>
                </div>
                <div class="py-1 flex flex-col">
                    <button @click="handleContextMenuMark" class="flex items-center w-full px-3 py-2 text-left text-xs font-semibold text-violet-600 hover:bg-violet-50 transition">
                        <span class="mr-2">🏷</span> Atur Alias
                    </button>
                    <button @click="handleContextMenuDelete" class="flex items-center w-full px-3 py-2 text-left text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
                        <span class="mr-2">🗑</span> Hapus Percakapan
                    </button>
                </div>
            </div>
        </Teleport>

        <!-- Confirm Modal Elegan -->
        <Teleport to="body">
            <div v-if="confirmModal.isOpen" class="fixed inset-0 z-[10000] flex items-center justify-center bg-slate-900/40 px-4 backdrop-blur-sm" @click.self="resolveConfirmModal(false)">
                <div class="w-full max-w-sm rounded-3xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/5 transform transition-all scale-100">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-rose-100 text-rose-500">
                            <span class="text-xl">⚠️</span>
                        </div>
                        <h3 class="text-lg font-black text-slate-800">{{ confirmModal.title }}</h3>
                    </div>
                    <p class="mb-6 whitespace-pre-wrap text-sm font-medium text-slate-600 leading-relaxed">{{ confirmModal.message }}</p>
                    <div class="flex justify-end gap-3">
                        <button @click="resolveConfirmModal(false)" class="rounded-xl px-5 py-2.5 text-sm font-bold text-slate-600 hover:bg-slate-100 transition focus:outline-none">
                            Batal
                        </button>
                        <button @click="resolveConfirmModal(true)" class="rounded-xl bg-rose-500 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-rose-500/20 hover:bg-rose-600 transition focus:outline-none">
                            Ya, Lanjutkan
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- ░░ Quest-Reward Toast Notification ░░ -->
        <Teleport to="body">
            <Transition name="toast-quest">
                <div v-if="toast.isOpen"
                     class="fixed inset-0 z-[10001] flex items-center justify-center px-4 pointer-events-none"
                     style="perspective: 800px;"
                >
                    <div class="toast-quest-card pointer-events-auto relative overflow-hidden rounded-3xl p-[2px] shadow-2xl"
                         :class="{
                             'toast-glow-error': toast.type === 'error',
                             'toast-glow-success': toast.type === 'success',
                             'toast-glow-warning': toast.type === 'warning',
                             'toast-glow-info': toast.type === 'info',
                         }"
                    >
                        <!-- Animated gradient border -->
                        <div class="toast-border-glow absolute inset-0 rounded-3xl"></div>

                        <!-- Card content -->
                        <div class="relative z-10 flex flex-col items-center gap-4 rounded-[1.35rem] bg-slate-950/95 px-8 py-7 backdrop-blur-xl sm:px-10 sm:py-8 min-w-[280px] max-w-[380px]">

                            <!-- Glow ring behind icon -->
                            <div class="toast-icon-ring relative flex h-16 w-16 items-center justify-center rounded-full sm:h-20 sm:w-20"
                                 :class="{
                                     'bg-rose-500/20 ring-rose-500/30': toast.type === 'error',
                                     'bg-emerald-500/20 ring-emerald-500/30': toast.type === 'success',
                                     'bg-amber-500/20 ring-amber-500/30': toast.type === 'warning',
                                     'bg-sky-500/20 ring-sky-500/30': toast.type === 'info',
                                 }"
                                 style="ring-width: 2px;"
                            >
                                <!-- Pulsing particles -->
                                <div class="toast-particles absolute inset-0 rounded-full"></div>

                                <!-- Icon -->
                                <span class="toast-icon-symbol relative z-10 text-3xl font-black sm:text-4xl"
                                      :class="{
                                          'text-rose-400': toast.type === 'error',
                                          'text-emerald-400': toast.type === 'success',
                                          'text-amber-400': toast.type === 'warning',
                                          'text-sky-400': toast.type === 'info',
                                      }"
                                >
                                    <svg v-if="toast.type === 'error'" class="h-8 w-8 sm:h-10 sm:w-10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" />
                                        <path stroke-linecap="round" d="M15 9l-6 6M9 9l6 6" />
                                    </svg>
                                    <svg v-else-if="toast.type === 'success'" class="h-8 w-8 sm:h-10 sm:w-10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
                                    </svg>
                                    <svg v-else-if="toast.type === 'warning'" class="h-8 w-8 sm:h-10 sm:w-10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                    </svg>
                                    <svg v-else class="h-8 w-8 sm:h-10 sm:w-10" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10" />
                                        <path stroke-linecap="round" d="M12 16h.01M12 8v4" />
                                    </svg>
                                </span>
                            </div>

                            <!-- Title -->
                            <h3 class="text-center text-lg font-black tracking-tight sm:text-xl"
                                :class="{
                                    'text-rose-300': toast.type === 'error',
                                    'text-emerald-300': toast.type === 'success',
                                    'text-amber-300': toast.type === 'warning',
                                    'text-sky-300': toast.type === 'info',
                                }"
                            >{{ toast.title }}</h3>

                            <!-- Message -->
                            <p class="text-center text-sm font-medium leading-relaxed text-slate-300/90 max-w-[300px]">
                                {{ toast.message }}
                            </p>

                            <!-- Dismiss button -->
                            <button
                                @click="dismissToast"
                                class="mt-1 rounded-2xl px-8 py-2.5 text-sm font-bold transition-all duration-200 active:scale-95"
                                :class="{
                                    'bg-rose-500/20 text-rose-300 hover:bg-rose-500/30 ring-1 ring-rose-500/30': toast.type === 'error',
                                    'bg-emerald-500/20 text-emerald-300 hover:bg-emerald-500/30 ring-1 ring-emerald-500/30': toast.type === 'success',
                                    'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 ring-1 ring-amber-500/30': toast.type === 'warning',
                                    'bg-sky-500/20 text-sky-300 hover:bg-sky-500/30 ring-1 ring-sky-500/30': toast.type === 'info',
                                }"
                            >
                                OK
                            </button>

                            <!-- Auto-dismiss progress bar -->
                            <div class="absolute bottom-0 left-0 right-0 h-1 overflow-hidden rounded-b-3xl bg-white/5">
                                <div class="toast-progress h-full rounded-full"
                                     :class="{
                                         'bg-rose-500': toast.type === 'error',
                                         'bg-emerald-500': toast.type === 'success',
                                         'bg-amber-500': toast.type === 'warning',
                                         'bg-sky-500': toast.type === 'info',
                                     }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>


        <!-- Footer kecil -->
        <footer class="mt-4 mb-2 flex justify-center">
            <a
                href="http://192.168.88.33/"
                target="_blank"
                rel="noopener"
                class="wa-footer-link group inline-flex flex-col items-center gap-0.5 select-none"
                title="Lawangsewu — PA Semarang"
            >
                <span class="wa-footer-top flex items-center gap-1.5">
                    <span class="wa-footer-text">developed with</span>
                    <!-- Heart icon -->
                    <svg class="wa-footer-heart" viewBox="0 0 20 18" fill="currentColor" aria-hidden="true">
                        <path d="M10 17.27L8.73 16.14C3.9 11.81 0.75 9.04 0.75 5.62C0.75 2.85 2.98 0.62 5.75 0.62C7.31 0.62 8.81 1.35 10 2.55C11.19 1.35 12.69 0.62 14.25 0.62C17.02 0.62 19.25 2.85 19.25 5.62C19.25 9.04 16.1 11.81 11.27 16.15L10 17.27Z"/>
                    </svg>
                    <span class="wa-footer-brand">
                        dbprakom<sup class="wa-footer-tm">™</sup>
                    </span>
                </span>
                <span class="wa-footer-bottom">
                    WA-Caraka&nbsp;<span class="wa-footer-copy">©</span>&nbsp;2026
                </span>
            </a>
        </footer>
    </LawangsewuLayout>
</template>

<style scoped>
/* ─── Interkom Outer Wrapper ──────────────────────────── */
.interkom-outer {
    align-items: flex-start;
}

/* ─── Interkom Sidebar ────────────────────────────────── */
.interkom-sidebar {
    width: 272px;
    max-width: 272px;
    flex-shrink: 0;
}

@media (max-width: 1024px) {
    /* Di bawah lg: panel muncul sebagai overlay/fixed di bawah layar */
    .interkom-sidebar {
        position: fixed;
        bottom: 0;
        right: 0;
        width: min(320px, 100vw);
        max-width: 100vw;
        z-index: 40;
        border-radius: 1.25rem 1.25rem 0 0 !important;
    }
}

/* ─── Interkom Slide Transition ───────────────────────── */
.interkom-slide-enter-active,
.interkom-slide-leave-active {
    transition: opacity 0.25s ease, transform 0.28s cubic-bezier(0.4, 0, 0.2, 1), max-width 0.28s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}
.interkom-slide-enter-from,
.interkom-slide-leave-to {
    opacity: 0;
    transform: translateX(24px);
    max-width: 0;
}
.interkom-slide-enter-to,
.interkom-slide-leave-from {
    opacity: 1;
    transform: translateX(0);
    max-width: 272px;
}

@media (max-width: 1024px) {
    .interkom-slide-enter-from,
    .interkom-slide-leave-to {
        transform: translateY(100%);
    }
    .interkom-slide-enter-to,
    .interkom-slide-leave-from {
        transform: translateY(0);
    }
}

/* ─── Particle Canvas ─────────────────────────────── */
.particle-canvas {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    pointer-events: none;
    z-index: 0;
    border-radius: inherit;
}

.thread-surface-dark > *:not(.particle-canvas) {
    position: relative;
    z-index: 1;
}

/* ─── Light mode bubbles ───────────────────────────── */
.bubble-outbound {
    position: relative;
    background:
        linear-gradient(135deg, rgba(207, 250, 254, 0.92), rgba(220, 252, 231, 0.96)),
        #d9fdd3;
    border: 1px solid rgba(125, 211, 252, 0.5);
    box-shadow:
        0 1px 0 rgba(11, 20, 26, 0.08),
        0 6px 18px rgba(14, 165, 233, 0.08);
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
    background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.97), rgba(248, 250, 252, 0.95)),
        #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.95);
    box-shadow:
        0 1px 0 rgba(11, 20, 26, 0.08),
        0 8px 22px rgba(15, 23, 42, 0.06);
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

/* ─── Dark mode bubble overrides ───────────────────── */
/* Outbound (pesan keluar) di dark mode: navy hijau gelap */
.thread-surface-dark .bubble-outbound {
    background:
        linear-gradient(135deg, rgba(4, 54, 55, 0.96), rgba(7, 72, 60, 0.98)),
        #054640;
    border: 1px solid rgba(56, 211, 159, 0.22);
    box-shadow:
        0 1px 0 rgba(0, 0, 0, 0.3),
        0 6px 18px rgba(0, 0, 0, 0.2);
    color: #e8fff5;
}

.thread-surface-dark .bubble-outbound::after {
    background: #054640;
    border-right-color: rgba(56, 211, 159, 0.22);
    border-top-color: rgba(56, 211, 159, 0.22);
}

/* Inbound (pesan masuk) di dark mode: kaca gelap */
.thread-surface-dark .bubble-inbound {
    background:
        linear-gradient(145deg, rgba(22, 30, 48, 0.97), rgba(17, 24, 40, 0.99)),
        #161e30;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow:
        0 1px 0 rgba(0, 0, 0, 0.25),
        0 8px 22px rgba(0, 0, 0, 0.18);
    color: #f0f4ff;
}

.thread-surface-dark .bubble-inbound::before {
    background: #161e30;
    border-left-color: rgba(255, 255, 255, 0.08);
    border-top-color: rgba(255, 255, 255, 0.08);
}

/* Override text-slate-900 yang hardcoded di bubbleCardClass */
.thread-surface-dark .bubble-outbound.text-slate-900,
.thread-surface-dark .bubble-inbound.text-slate-900 {
    color: inherit !important;
}

.bubble-sending {
    filter: saturate(0.88) brightness(0.96);
}

.bubble-failed {
    background: #ffe4e6;
    border-color: #fecdd3;
    box-shadow: 0 1px 0 rgba(225, 29, 72, 0.08), 0 1px 2px rgba(225, 29, 72, 0.12);
}

/* Dark mode: bubble failed */
.thread-surface-dark .bubble-failed {
    background: rgba(127, 29, 29, 0.6);
    border-color: rgba(248, 113, 113, 0.35);
}

.bubble-pop-in {
    animation: bubblePopIn 0.2s ease-out;
}

.conversation-active {
    box-shadow:
        inset 0 1px 0 rgba(125, 211, 252, 0.12),
        inset 0 -1px 0 rgba(125, 211, 252, 0.08),
        0 10px 24px rgba(14, 165, 233, 0.06);
}

.conversation-fresh {
    animation: conversationFreshGlow 2.2s ease-out;
}

.unread-pill {
    box-shadow: 0 10px 24px rgba(244, 63, 94, 0.28);
    animation: unreadPulse 1.8s ease-in-out infinite;
}

.thread-skeleton {
    position: relative;
    overflow: hidden;
    min-height: 86px;
    border: 1px solid rgba(226, 232, 240, 0.95);
    background:
        linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(241, 245, 249, 0.9)),
        #fff;
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
}

.thread-skeleton::after {
    content: '';
    position: absolute;
    inset: 0;
    transform: translateX(-100%);
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.7), transparent);
    animation: skeletonSweep 1.25s ease-in-out infinite;
}

.send-btn {
    background: linear-gradient(135deg, #0ea5e9 0%, #06b6d4 100%);
    transform: translateZ(0);
    box-shadow: 0 14px 34px rgba(6, 182, 212, 0.24);
}

/* Light mode: background percakapan putih bersih (mirip WhatsApp Web) */
.thread-surface {
    background-color: #ffffff;
    background-image: none;
}

/* Dark mode: thread harus position:relative untuk particle canvas */
.thread-surface-dark {
    position: relative !important;
}

/* Dark mode: warna teks dalam bubble (meta, timestamp, tick, sender) */
.thread-surface-dark .bubble-outbound .text-slate-600,
.thread-surface-dark .bubble-outbound .text-slate-500,
.thread-surface-dark .bubble-outbound .text-sky-600 {
    color: rgba(134, 239, 172, 0.85) !important;
}

.thread-surface-dark .bubble-inbound .text-slate-500,
.thread-surface-dark .bubble-inbound .text-slate-600 {
    color: rgba(148, 163, 184, 0.9) !important;
}

/* Sender name (member-color) di dark mode: lebih terang */
.thread-surface-dark .member-color-self { color: #4ade80; }
.thread-surface-dark .member-color-1    { color: #60a5fa; }
.thread-surface-dark .member-color-2    { color: #e879f9; }
.thread-surface-dark .member-color-3    { color: #34d399; }
.thread-surface-dark .member-color-4    { color: #fb923c; }
.thread-surface-dark .member-color-5    { color: #a78bfa; }
.thread-surface-dark .member-color-6    { color: #f472b6; }
.thread-surface-dark .member-color-7    { color: #818cf8; }
.thread-surface-dark .member-color-8    { color: #2dd4bf; }

/* ─── Footer ─────────────────────────────────────────── */
.wa-footer-link {
    text-decoration: none;
    opacity: 0.52;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.wa-footer-link:hover {
    opacity: 0.88;
    transform: translateY(-1px);
}

.wa-footer-top {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.wa-footer-text {
    font-size: 9px;
    font-weight: 500;
    letter-spacing: 0.06em;
    color: var(--text-3);
    text-transform: lowercase;
}

.wa-footer-heart {
    width: 10px;
    height: 10px;
    color: #f43f5e;
    flex-shrink: 0;
    transition: transform 0.3s ease, color 0.3s ease;
}

.wa-footer-link:hover .wa-footer-heart {
    transform: scale(1.35);
    color: #fb7185;
    filter: drop-shadow(0 0 4px rgba(244, 63, 94, 0.55));
}

.wa-footer-brand {
    font-size: 9px;
    font-weight: 800;
    letter-spacing: 0.04em;
    color: var(--text-2);
    text-transform: lowercase;
}

.wa-footer-tm {
    font-size: 6px;
    font-weight: 700;
    vertical-align: super;
    line-height: 1;
    letter-spacing: 0;
    opacity: 0.75;
}

.wa-footer-bottom {
    font-size: 8px;
    font-weight: 500;
    letter-spacing: 0.08em;
    color: var(--text-3);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.15rem;
    position: relative;
}

.wa-footer-bottom::after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 50%;
    transform: translateX(-50%) scaleX(0);
    width: 100%;
    height: 1px;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    transition: transform 0.3s ease;
    transform-origin: center;
}

.wa-footer-link:hover .wa-footer-bottom::after {
    transform: translateX(-50%) scaleX(1);
}

.wa-footer-copy {
    font-size: 8px;
    opacity: 0.7;
}

:deep(::-webkit-scrollbar) {
    width: 10px;
    height: 10px;
}

:deep(::-webkit-scrollbar-thumb) {
    border-radius: 999px;
    background: linear-gradient(180deg, rgba(14, 165, 233, 0.45), rgba(15, 23, 42, 0.28));
    border: 2px solid transparent;
    background-clip: padding-box;
}

:deep(::-webkit-scrollbar-track) {
    background: transparent;
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

@keyframes skeletonSweep {
    100% {
        transform: translateX(100%);
    }
}

@keyframes unreadPulse {
    0%,
    100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.04);
    }
}

@keyframes conversationFreshGlow {
    0% {
        background-color: rgba(186, 230, 253, 0.38);
        box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.18);
    }
    100% {
        background-color: transparent;
        box-shadow: none;
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

/* ─── Mobile Responsive ──────────────────────────────── */
@media (max-width: 639px) {
    /* Full-width inbox on mobile */
    aside {
        max-height: calc(100vh - 8rem) !important;
        max-height: calc(100dvh - 8rem) !important;
    }

    /* Touch-friendly scrolling */
    .overflow-y-auto {
        -webkit-overflow-scrolling: touch;
    }

    /* Hide scrollbars on mobile for cleaner look */
    :deep(::-webkit-scrollbar) {
        width: 4px;
    }

    /* Thread viewport: use dynamic viewport height */
    .thread-surface,
    .thread-surface-dark {
        max-height: calc(100vh - 16rem) !important;
        max-height: calc(100dvh - 16rem) !important;
    }

    /* Bubbles: wider on mobile */
    .bubble-outbound,
    .bubble-inbound {
        max-width: 94% !important;
    }

    /* Stats grid: 2 cols instead of 4 on very small screens */
    .grid-cols-2 {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

/* ─── Quest-Reward Toast Notification ─────────────── */
/* Entrance / Exit */
.toast-quest-enter-active {
    transition: opacity 0.35s ease, transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.toast-quest-leave-active {
    transition: opacity 0.25s ease, transform 0.3s ease;
}
.toast-quest-enter-from {
    opacity: 0;
    transform: scale(0.7) translateY(30px);
}
.toast-quest-enter-to {
    opacity: 1;
    transform: scale(1) translateY(0);
}
.toast-quest-leave-to {
    opacity: 0;
    transform: scale(0.85) translateY(-15px);
}

/* Glow variants */
.toast-glow-error {
    box-shadow:
        0 0 40px rgba(244, 63, 94, 0.25),
        0 0 80px rgba(244, 63, 94, 0.12),
        0 20px 60px rgba(0, 0, 0, 0.4);
    animation: toastGlowPulse-error 2s ease-in-out infinite;
}
.toast-glow-success {
    box-shadow:
        0 0 40px rgba(16, 185, 129, 0.25),
        0 0 80px rgba(16, 185, 129, 0.12),
        0 20px 60px rgba(0, 0, 0, 0.4);
    animation: toastGlowPulse-success 2s ease-in-out infinite;
}
.toast-glow-warning {
    box-shadow:
        0 0 40px rgba(245, 158, 11, 0.25),
        0 0 80px rgba(245, 158, 11, 0.12),
        0 20px 60px rgba(0, 0, 0, 0.4);
    animation: toastGlowPulse-warning 2s ease-in-out infinite;
}
.toast-glow-info {
    box-shadow:
        0 0 40px rgba(14, 165, 233, 0.25),
        0 0 80px rgba(14, 165, 233, 0.12),
        0 20px 60px rgba(0, 0, 0, 0.4);
    animation: toastGlowPulse-info 2s ease-in-out infinite;
}

/* Animated gradient border */
.toast-border-glow {
    background: conic-gradient(
        from var(--toast-angle, 0deg),
        transparent 0%,
        rgba(255, 255, 255, 0.15) 10%,
        transparent 20%,
        rgba(255, 255, 255, 0.08) 50%,
        transparent 60%,
        rgba(255, 255, 255, 0.12) 80%,
        transparent 100%
    );
    animation: toastBorderSpin 4s linear infinite;
}

.toast-glow-error .toast-border-glow {
    background: conic-gradient(
        from var(--toast-angle, 0deg),
        transparent, rgba(244, 63, 94, 0.5), transparent, rgba(251, 113, 133, 0.3), transparent
    );
    animation: toastBorderSpin 3s linear infinite;
}
.toast-glow-success .toast-border-glow {
    background: conic-gradient(
        from var(--toast-angle, 0deg),
        transparent, rgba(16, 185, 129, 0.5), transparent, rgba(52, 211, 153, 0.3), transparent
    );
    animation: toastBorderSpin 3s linear infinite;
}
.toast-glow-warning .toast-border-glow {
    background: conic-gradient(
        from var(--toast-angle, 0deg),
        transparent, rgba(245, 158, 11, 0.5), transparent, rgba(251, 191, 36, 0.3), transparent
    );
    animation: toastBorderSpin 3s linear infinite;
}
.toast-glow-info .toast-border-glow {
    background: conic-gradient(
        from var(--toast-angle, 0deg),
        transparent, rgba(14, 165, 233, 0.5), transparent, rgba(56, 189, 248, 0.3), transparent
    );
    animation: toastBorderSpin 3s linear infinite;
}

/* Icon ring pulse */
.toast-icon-ring {
    animation: toastIconPulse 1.8s ease-in-out infinite;
}

/* Icon entrance */
.toast-icon-symbol {
    animation: toastIconBounce 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) 0.2s both;
}

/* Particles shimmer */
.toast-particles {
    background: radial-gradient(circle, rgba(255,255,255,0.08) 1px, transparent 1px);
    background-size: 8px 8px;
    animation: toastParticleShimmer 2s ease-in-out infinite;
}

/* Progress bar countdown */
.toast-progress {
    animation: toastProgressShrink 4.5s linear forwards;
}

@keyframes toastGlowPulse-error {
    0%, 100% { box-shadow: 0 0 40px rgba(244, 63, 94, 0.25), 0 0 80px rgba(244, 63, 94, 0.12), 0 20px 60px rgba(0, 0, 0, 0.4); }
    50% { box-shadow: 0 0 55px rgba(244, 63, 94, 0.35), 0 0 100px rgba(244, 63, 94, 0.18), 0 20px 60px rgba(0, 0, 0, 0.4); }
}
@keyframes toastGlowPulse-success {
    0%, 100% { box-shadow: 0 0 40px rgba(16, 185, 129, 0.25), 0 0 80px rgba(16, 185, 129, 0.12), 0 20px 60px rgba(0, 0, 0, 0.4); }
    50% { box-shadow: 0 0 55px rgba(16, 185, 129, 0.35), 0 0 100px rgba(16, 185, 129, 0.18), 0 20px 60px rgba(0, 0, 0, 0.4); }
}
@keyframes toastGlowPulse-warning {
    0%, 100% { box-shadow: 0 0 40px rgba(245, 158, 11, 0.25), 0 0 80px rgba(245, 158, 11, 0.12), 0 20px 60px rgba(0, 0, 0, 0.4); }
    50% { box-shadow: 0 0 55px rgba(245, 158, 11, 0.35), 0 0 100px rgba(245, 158, 11, 0.18), 0 20px 60px rgba(0, 0, 0, 0.4); }
}
@keyframes toastGlowPulse-info {
    0%, 100% { box-shadow: 0 0 40px rgba(14, 165, 233, 0.25), 0 0 80px rgba(14, 165, 233, 0.12), 0 20px 60px rgba(0, 0, 0, 0.4); }
    50% { box-shadow: 0 0 55px rgba(14, 165, 233, 0.35), 0 0 100px rgba(14, 165, 233, 0.18), 0 20px 60px rgba(0, 0, 0, 0.4); }
}

@property --toast-angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}

@keyframes toastBorderSpin {
    from { --toast-angle: 0deg; }
    to   { --toast-angle: 360deg; }
}

@keyframes toastIconPulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.08); opacity: 0.9; }
}

@keyframes toastIconBounce {
    0% { transform: scale(0); opacity: 0; }
    60% { transform: scale(1.15); }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes toastParticleShimmer {
    0%, 100% { opacity: 0.3; }
    50% { opacity: 0.6; }
}

@keyframes toastProgressShrink {
    0% { width: 100%; }
    100% { width: 0%; }
}
</style>

