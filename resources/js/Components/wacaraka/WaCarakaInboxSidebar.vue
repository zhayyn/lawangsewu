<script setup>
/**
 * WaCarakaInboxSidebar — Panel kiri daftar percakapan WaCaraka.
 *
 * Diekstrak dari Index.vue (sebelumnya ~230 baris inline template).
 * Menerima semua data sebagai props dan memancarkan event ke parent.
 */

defineProps({
    // Daftar percakapan yang sudah difilter & dirender
    conversations:           { type: Array,   required: true },
    filteredConversations:   { type: Array,   required: true },
    renderedConversations:   { type: Array,   required: true },
    activeConvoId:           { type: String,  default: '' },
    conversationSearch:      { type: String,  default: '' },
    conversationFilter:      { type: String,  default: 'all' },
    isLoading:               { type: Boolean, default: false },
    isBackgroundRefreshing:  { type: Boolean, default: false },
    inboxSyncText:           { type: String,  default: '' },
    isMobile:                { type: Boolean, default: false },
    mobileView:              { type: String,  default: 'inbox' },
    showInterkom:            { type: Boolean, default: false },
    maxMediaBytes:           { type: Number,  default: 15728640 },

    // Helper functions yang diperlukan template
    avatarToneClassFor:          { type: Function, required: true },
    initialsFromName:            { type: Function, required: true },
    primaryContactNumber:        { type: Function, required: true },
    conversationPreviewText:     { type: Function, required: true },
    conversationStatusLabel:     { type: Function, required: true },
    markToneClass:               { type: Function, required: true },
    inboxPreviewMedia:           { type: Function, required: true },
    hasInboxVisualPreview:       { type: Function, required: true },
    inboxPreviewLabel:           { type: Function, required: true },
    inboxMediaIcon:              { type: Function, required: true },
    humanFileSize:               { type: Function, required: true },
    isConversationRecentlyUpdated: { type: Function, required: true },
    onProfileImageError:         { type: Function, required: true },
    openMediaViewer:             { type: Function, required: true },
});

const emit = defineEmits([
    'select-conversation',       // (conversationId: string)
    'context-menu',              // (event: MouseEvent, conversation: Object)
    'prefetch-conversation',     // (conversationId: string)
    'update:conversationSearch', // v-model support
    'update:conversationFilter', // v-model support
    'load-more',                 // load 30 more conversations
    'scroll',                    // scroll event dari list el (untuk expand window)
]);
</script>

<template>
    <aside
        class="flex min-w-0 flex-col rounded-[1.5rem] border border-[var(--border)] bg-[var(--surface-1)] shadow-[var(--shadow)] max-h-[calc(100vh-1rem)] xl:sticky xl:top-2 xl:rounded-[2rem]"
        :class="{ 'hidden': isMobile && mobileView !== 'inbox', 'sm:flex': true }"
    >
        <!-- Header -->
        <div class="flex items-center justify-between gap-2 border-b border-[var(--border)] px-3 py-2.5 flex-shrink-0 sm:px-4 sm:py-3 xl:px-5">
            <div>
                <h2 class="text-sm font-black text-[var(--text-1)] sm:text-base">Inbox</h2>
            </div>
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-[var(--surface-2)] px-2 py-0.5 text-[9px] font-bold text-[var(--text-2)] sm:px-2.5 sm:text-[10px]">
                    {{ filteredConversations.length }}/{{ conversations.length }}
                </span>
            </div>
        </div>

        <!-- Sync text -->
        <p class="px-3 py-1.5 text-[9px] text-[var(--text-2)] flex-shrink-0 sm:px-4 sm:text-[10px] xl:px-5">{{ inboxSyncText }}</p>

        <!-- Search + Filter -->
        <div class="grid gap-1.5 border-b border-[var(--border)] px-3 pb-2.5 sm:px-4 xl:px-5">
            <input
                :value="conversationSearch"
                @input="emit('update:conversationSearch', $event.target.value)"
                type="text"
                placeholder="Cari nama, nomor, alias, preview..."
                class="w-full rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-3 py-2 text-[11px] text-[var(--text-1)] outline-none transition placeholder:text-[var(--text-2)] focus:border-sky-400/60"
            />
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="filter in [
                        { value: 'all',       label: 'Semua' },
                        { value: 'unread',    label: 'Belum Dibaca' },
                        { value: 'unreplied', label: 'Belum Dibalas' },
                        { value: 'mine',      label: 'Milik Saya' },
                        { value: 'group',     label: 'Grup' },
                    ]"
                    :key="filter.value"
                    @click="emit('update:conversationFilter', filter.value)"
                    class="rounded-full border px-2 py-1 text-[9px] font-bold transition"
                    :class="conversationFilter === filter.value
                        ? 'border-sky-400/50 bg-sky-500/12 text-sky-600'
                        : 'border-[var(--border)] bg-[var(--surface-2)] text-[var(--text-2)] hover:border-sky-400/40 hover:text-sky-500'"
                >
                    {{ filter.label }}
                </button>
            </div>
        </div>

        <!-- Conversation List -->
        <div
            class="relative flex-1 min-h-0 overflow-y-auto divide-y divide-[var(--border)]"
            @scroll.passive="emit('scroll', $event)"
        >
            <!-- Conversation Items -->
            <button
                v-for="c in renderedConversations"
                :key="c.conversationId"
                @click="emit('select-conversation', c.conversationId)"
                @contextmenu.prevent="emit('context-menu', $event, c)"
                @mouseenter="emit('prefetch-conversation', c.conversationId)"
                @focus="emit('prefetch-conversation', c.conversationId)"
                @touchstart.passive="emit('prefetch-conversation', c.conversationId)"
                class="group w-full px-2 py-1.5 text-left transition-all duration-200 sm:px-2.5 sm:py-1.5 xl:px-3 xl:py-2"
                :class="[
                    activeConvoId === c.conversationId
                        ? 'conversation-active bg-sky-500/10 border-l-2 border-sky-400'
                        : 'hover:bg-[var(--surface-2)] border-l-2 border-transparent',
                    isConversationRecentlyUpdated(c.conversationId) ? 'conversation-fresh' : '',
                ]"
            >
                <div class="flex items-start gap-2 sm:gap-2.5">
                    <!-- Avatar -->
                    <div class="mt-0.5 h-7 w-7 flex-shrink-0 overflow-hidden rounded-full ring-1 ring-white/40 sm:h-7.5 sm:w-7.5 xl:h-8 xl:w-8">
                        <img
                            v-if="c.profilePhotoUrl"
                            :src="c.profilePhotoUrl"
                            alt="Foto profil WA"
                            class="h-full w-full object-cover"
                            referrerpolicy="no-referrer"
                            @error="onProfileImageError(c.conversationId)"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-[9px] font-black text-white sm:text-[10px] xl:text-[11px]"
                            :class="avatarToneClassFor(c.conversationId || c.remoteNumber)"
                        >
                            {{ initialsFromName(c.displayTitle) }}
                        </div>
                    </div>

                    <!-- Info -->
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p
                                class="flex min-w-0 items-baseline gap-1.5 font-bold text-[var(--text-1)] transition-all"
                                :class="!showInterkom ? 'text-[13px] sm:text-[14px] xl:text-[15px]' : 'text-[11px] sm:text-[12px] xl:text-[13px]'"
                            >
                                <span class="truncate">
                                    <span v-if="c.isGroup" class="mr-1">👥</span>{{ c.displayTitle }}
                                </span>
                                <span
                                    class="shrink-0 font-mono text-[var(--text-2)] transition-all font-normal opacity-80"
                                    :class="!showInterkom ? 'text-[10px] sm:text-[11px]' : 'text-[9px] sm:text-[9px]'"
                                >
                                    {{ primaryContactNumber(c.remoteNumber, c.resolvedNumber) }}
                                </span>
                            </p>
                            <!-- Unread badge -->
                            <span
                                v-if="c.unreadCount > 0"
                                class="unread-pill flex-shrink-0 rounded-full bg-rose-500 px-1.5 py-0.5 font-black text-white transition-all"
                                :class="!showInterkom ? 'text-[10px] sm:px-2.5 sm:text-[11px]' : 'text-[9px] sm:px-2 sm:text-[10px]'"
                            >
                                {{ c.unreadCount }}
                            </span>
                        </div>

                        <p v-if="c.remoteName && !c.isGroup"
                           class="mt-0.5 truncate text-[var(--text-2)] transition-all"
                           :class="!showInterkom ? 'text-[10px] sm:text-[11px]' : 'text-[9px] sm:text-[9px]'"
                        >
                            Nama WA: <span class="font-semibold">{{ c.remoteName }}</span>
                        </p>
                        <p v-if="c.groupName && c.isGroup"
                           class="mt-0.5 truncate text-[var(--text-2)] transition-all"
                           :class="!showInterkom ? 'text-[10px] sm:text-[11px]' : 'text-[9px] sm:text-[9px]'"
                        >
                            Nama Group: <span class="font-semibold">{{ c.groupName }}</span>
                        </p>

                        <p
                            class="mt-0.5 line-clamp-1 text-[var(--text-2)] transition-all"
                            :class="!showInterkom ? 'text-[11px] sm:text-[12px]' : 'text-[9px] sm:text-[10px]'"
                        >
                            {{ conversationPreviewText(c) }}
                        </p>

                        <!-- Media preview -->
                        <div v-if="inboxPreviewMedia(c)" class="mt-1.5 flex items-center gap-2">
                            <button
                                v-if="hasInboxVisualPreview(c)"
                                @click.stop="openMediaViewer(inboxPreviewMedia(c).url, { alt: `Preview ${inboxPreviewLabel(c)}`, fileName: inboxPreviewMedia(c).fileName })"
                                class="overflow-hidden rounded-xl border border-slate-200/80 bg-white/80 transition hover:border-sky-300/70"
                            >
                                <img
                                    :src="inboxPreviewMedia(c).url"
                                    :alt="`Preview ${inboxPreviewLabel(c)}`"
                                    loading="lazy"
                                    decoding="async"
                                    class="h-8 w-8 object-cover"
                                />
                            </button>
                            <div v-else class="flex items-center gap-1.5">
                                <div class="inline-flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white/85 text-sm">
                                    {{ inboxMediaIcon(inboxPreviewMedia(c)).icon }}
                                </div>
                                <span class="text-[10px] font-semibold text-slate-500">
                                    {{ inboxMediaIcon(inboxPreviewMedia(c)).label }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p
                                    class="truncate text-[var(--text-2)] transition-all"
                                    :class="!showInterkom ? 'text-[10px]' : 'text-[9px]'"
                                >
                                    {{ inboxPreviewMedia(c).fileName || `Lampiran ${inboxPreviewLabel(c)}` }}
                                </p>
                                <p v-if="humanFileSize(inboxPreviewMedia(c).fileSize)" class="text-[9px] text-[var(--text-2)]/70">
                                    {{ humanFileSize(inboxPreviewMedia(c).fileSize) }}
                                    <span
                                        v-if="inboxPreviewMedia(c).fileSize > maxMediaBytes"
                                        class="ml-1 font-semibold text-amber-500"
                                    >⚠ Maks {{ humanFileSize(maxMediaBytes) }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Status badges -->
                        <div class="mt-1 flex flex-wrap items-center gap-1">
                            <span
                                class="rounded-full px-1.5 py-0.5 text-[8px] font-bold sm:px-2 sm:text-[9px]"
                                :class="{
                                    'bg-emerald-500/15 text-emerald-600': c.status === 'open',
                                    'bg-amber-500/15 text-amber-600':    c.status === 'pending',
                                    'bg-slate-500/15 text-slate-500':    c.status === 'closed',
                                }"
                            >
                                {{ conversationStatusLabel(c.status) }}
                            </span>
                            <span v-if="c.customerMark"
                                  class="rounded-full border px-1.5 py-0.5 text-[8px] font-bold sm:px-2 sm:text-[9px]"
                                  :class="markToneClass(c.customerMark.tone)"
                            >
                                {{ c.customerMark.label }}
                            </span>
                            <span v-if="c.customerMark?.isPinned" class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-amber-50 shadow-sm ring-1 ring-amber-200/50">
                                <span class="text-[10px]">📌</span>
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

            <!-- Skeleton loader (manual refresh only) -->
            <div
                v-if="isLoading && !isBackgroundRefreshing && conversations.length > 0"
                class="pointer-events-none absolute inset-0 z-10 overflow-hidden bg-[var(--surface-1)]/60 backdrop-blur-[2px]"
            >
                <div class="space-y-1 px-3 pt-2">
                    <div v-for="i in 6" :key="i" class="flex items-center gap-2.5 rounded-2xl px-2 py-2.5">
                        <div class="thread-skeleton h-8 w-8 flex-shrink-0 rounded-full"></div>
                        <div class="flex-1 space-y-1.5">
                            <div class="thread-skeleton h-2.5 rounded-full" :style="{ width: (55 + i * 7) % 80 + '%' }"></div>
                            <div class="thread-skeleton h-2 rounded-full"   :style="{ width: (30 + i * 9) % 65 + '%' }"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Background sync dot -->
            <transition name="fade-dot">
                <div
                    v-if="isBackgroundRefreshing"
                    class="pointer-events-none absolute right-2 top-2 z-20 flex items-center gap-1 rounded-full bg-[var(--surface-2)]/80 px-2 py-0.5 text-[9px] font-bold text-[var(--text-2)] shadow backdrop-blur-sm"
                >
                    <span class="inline-block h-1.5 w-1.5 animate-pulse rounded-full bg-sky-400"></span>
                    sync
                </div>
            </transition>

            <!-- First load spinner -->
            <div v-if="isLoading && conversations.length === 0" class="flex flex-col items-center justify-center px-5 py-16">
                <div class="hourglass-loader opacity-80"></div>
                <p class="mt-6 text-[13px] font-bold text-[var(--text-1)]">Sedang memuat data...</p>
                <p class="mt-1 text-[11px] text-[var(--text-2)] text-center leading-relaxed">
                    Sabar ya masnya dan mbaknya.. 😏<br/>
                    <span class="opacity-70">Sistem sedang bekerja keras buat kamu.</span>
                </p>
            </div>
            <div v-else-if="filteredConversations.length === 0" class="px-5 py-10 text-center">
                <p class="text-sm text-[var(--text-2)]">Belum ada percakapan.</p>
                <p class="mt-1 text-xs text-[var(--text-2)]">Pesan akan muncul saat runtime mengirim webhook atau pull inbox berhasil.</p>
            </div>

            <!-- Load more -->
            <div v-else-if="renderedConversations.length < filteredConversations.length" class="px-4 py-4 text-center">
                <button
                    @click="emit('load-more')"
                    class="rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-1.5 text-[10px] font-bold text-[var(--text-2)] transition hover:border-sky-400/40 hover:text-sky-500"
                >
                    Muat {{ Math.min(30, filteredConversations.length - renderedConversations.length) }} chat lagi
                </button>
            </div>
        </div>
    </aside>
</template>
