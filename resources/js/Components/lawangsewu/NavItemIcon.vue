<script setup>
import { computed } from 'vue';

const props = defineProps({
    routeKey: {
        type: String,
        required: true,
    },
    active: {
        type: Boolean,
        default: false,
    },
    compact: {
        type: Boolean,
        default: false,
    },
    dark: {
        type: Boolean,
        default: true,
    },
});

const iconType = computed(() => ({
    dashboard: 'dashboard',
    guestbook: 'book',
    'satellite.pendopo': 'building',
    ptsp: 'ticket',
    sidang: 'document',
    pilar: 'columns',
    cctv: 'camera',
    chat: 'chat',
    sipp: 'database',
    wacaraka: 'phone',
    'wacaraka.admin': 'phone',
    kepegawaian: 'users',
    ptip: 'server',
    keuangan: 'wallet',
    ai: 'sparkles',
    appearance: 'palette',
    preferences: 'sliders',
    'admin-pendopo': 'building',
    'admin-system-monitor': 'server',
    'admin-cctv': 'camera',
    'admin-users': 'users',
    'admin-laporan': 'laporan',
    'tailscale': 'network',
}[props.routeKey] || 'dashboard'));

const gradientClass = computed(() => ({
    dashboard: 'from-sky-500 via-cyan-400 to-blue-600',
    book: 'from-amber-400 via-orange-400 to-rose-500',
    building: 'from-emerald-400 via-teal-400 to-cyan-500',
    ticket: 'from-fuchsia-500 via-pink-400 to-rose-500',
    document: 'from-indigo-500 via-violet-400 to-fuchsia-500',
    columns: 'from-yellow-300 via-amber-400 to-orange-500',
    camera: 'from-cyan-400 via-sky-500 to-blue-600',
    chat: 'from-green-400 via-emerald-500 to-teal-500',
    database: 'from-violet-500 via-purple-400 to-indigo-600',
    phone: 'from-lime-400 via-green-500 to-emerald-600',
    users: 'from-pink-500 via-rose-400 to-orange-400',
    server: 'from-slate-400 via-slate-500 to-sky-500',
    wallet: 'from-yellow-300 via-lime-400 to-emerald-500',
    sparkles: 'from-fuchsia-400 via-violet-400 to-sky-400',
    palette: 'from-rose-400 via-fuchsia-400 to-violet-500',
    sliders: 'from-blue-400 via-cyan-400 to-teal-400',
    network: 'from-teal-400 via-cyan-500 to-sky-500',
    laporan: 'from-violet-500 via-purple-400 to-fuchsia-500',
}[iconType.value] || 'from-sky-500 via-cyan-400 to-blue-600'));

const tintClass = computed(() => ({
    dashboard: 'text-sky-600',
    book: 'text-orange-600',
    building: 'text-emerald-600',
    ticket: 'text-fuchsia-600',
    document: 'text-violet-600',
    columns: 'text-amber-600',
    camera: 'text-cyan-600',
    chat: 'text-emerald-600',
    database: 'text-indigo-600',
    phone: 'text-green-600',
    users: 'text-rose-600',
    server: 'text-slate-600',
    wallet: 'text-lime-600',
    sparkles: 'text-fuchsia-600',
    palette: 'text-pink-600',
    sliders: 'text-blue-600',
    network: 'text-teal-600',
    laporan: 'text-violet-600',
}[iconType.value] || 'text-sky-600'));

const wrapperClasses = computed(() => {
    const sizeClass = props.compact ? 'h-6 w-6 rounded-md' : 'h-6 w-6 rounded-[0.75rem]';

    if (props.dark) {
        return [
            'relative isolate flex shrink-0 items-center justify-center overflow-hidden border transition-all duration-300',
            sizeClass,
            props.active
                ? 'border-white/20 shadow-[0_12px_28px_-18px_rgba(56,189,248,0.85)]'
                : 'border-white/10 shadow-[0_10px_24px_-18px_rgba(15,23,42,0.9)] group-hover:-translate-y-0.5 group-hover:scale-[1.06] group-hover:border-white/20',
        ];
    }

    return [
        'relative isolate flex shrink-0 items-center justify-center overflow-hidden border transition-all duration-300',
        sizeClass,
        props.active
            ? 'border-slate-300/80 bg-white shadow-[0_10px_24px_-20px_rgba(15,23,42,0.35)]'
            : 'border-slate-200/80 bg-white/82 group-hover:-translate-y-0.5 group-hover:border-slate-300 group-hover:shadow-[0_12px_24px_-20px_rgba(15,23,42,0.25)]',
    ];
});

const glowClasses = computed(() => {
    if (!props.dark) {
        return [];
    }

    return [
        'absolute inset-0 bg-gradient-to-br',
        gradientClass.value,
        props.active ? 'opacity-100' : 'opacity-90 group-hover:opacity-100',
    ];
});

const shellClasses = computed(() => {
    if (props.dark) {
        return ['absolute inset-[1px] bg-slate-950/20 backdrop-blur-[2px]', props.compact ? 'rounded-[7px]' : 'rounded-[0.8rem]'];
    }

    return ['absolute inset-0 bg-gradient-to-b from-white/95 to-slate-50/90', props.compact ? 'rounded-lg' : 'rounded-[0.9rem]'];
});

const iconClasses = computed(() => {
    if (props.dark) {
        return [
            'relative z-10 h-3 w-3 text-white transition-all duration-300 drop-shadow-[0_0_10px_rgba(255,255,255,0.35)]',
            props.active ? 'scale-100' : 'group-hover:scale-105',
        ];
    }

    return [
        'relative z-10 h-3 w-3 transition-all duration-300',
        tintClass.value,
        props.active ? 'scale-100' : 'group-hover:scale-105',
    ];
});
</script>

<template>
    <div :class="wrapperClasses" aria-hidden="true">
        <div v-if="dark" :class="glowClasses" />
        <div :class="shellClasses" />

        <svg
            v-if="iconType === 'dashboard'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.65"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <rect x="3.5" y="3.5" width="7" height="7" rx="1.5" />
            <rect x="13.5" y="3.5" width="7" height="4.5" rx="1.5" />
            <rect x="13.5" y="11.5" width="7" height="9" rx="1.5" />
            <rect x="3.5" y="13.5" width="7" height="7" rx="1.5" />
        </svg>

        <svg
            v-else-if="iconType === 'book'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M5 5.5A2.5 2.5 0 0 1 7.5 3H19v16H7.5A2.5 2.5 0 0 0 5 21.5z" />
            <path d="M5 5.5v16" />
            <path d="M9 7h6" />
            <path d="M9 11h6" />
        </svg>

        <svg
            v-else-if="iconType === 'building'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M3 10.5L12 4l9 6.5" />
            <path d="M5 10.5h14" />
            <path d="M6.5 10.5v8.5" />
            <path d="M10 10.5v8.5" />
            <path d="M14 10.5v8.5" />
            <path d="M17.5 10.5v8.5" />
            <path d="M4.5 19h15" />
        </svg>

        <svg
            v-else-if="iconType === 'ticket'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M4 8.5A2.5 2.5 0 0 0 6.5 6H19a1 1 0 0 1 1 1v3a2.5 2.5 0 0 0 0 5v3a1 1 0 0 1-1 1H6.5A2.5 2.5 0 0 0 4 16.5z" />
            <path d="M10 8v8" stroke-dasharray="1.6 2" />
        </svg>

        <svg
            v-else-if="iconType === 'document'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M8 3.5h6l4 4V20a1 1 0 0 1-1 1H8a2 2 0 0 1-2-2V5.5a2 2 0 0 1 2-2z" />
            <path d="M14 3.5V8h4" />
            <path d="M9 12h6" />
            <path d="M9 16h5" />
        </svg>

        <svg
            v-else-if="iconType === 'columns'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M4 6h16" />
            <path d="M6 6v11" />
            <path d="M10 6v11" />
            <path d="M14 6v11" />
            <path d="M18 6v11" />
            <path d="M3 19h18" />
            <path d="M12 3l8 3H4z" />
        </svg>

        <svg
            v-else-if="iconType === 'camera'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M4 8.5h11a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2H4z" />
            <path d="M17 11l3.5-2v8L17 15" />
            <circle cx="9.5" cy="13" r="2.5" />
        </svg>

        <svg
            v-else-if="iconType === 'chat'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M5 6.5h14a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H11l-4.5 3v-3H5a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2z" />
            <path d="M8 11h8" />
            <path d="M8 14h5" />
        </svg>

        <svg
            v-else-if="iconType === 'database'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <ellipse cx="12" cy="6" rx="6.5" ry="2.5" />
            <path d="M5.5 6v5c0 1.4 2.9 2.5 6.5 2.5s6.5-1.1 6.5-2.5V6" />
            <path d="M5.5 11v5c0 1.4 2.9 2.5 6.5 2.5s6.5-1.1 6.5-2.5v-5" />
        </svg>

        <svg
            v-else-if="iconType === 'phone'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <rect x="7" y="3.5" width="10" height="17" rx="2.5" />
            <path d="M10 6.5h4" />
            <path d="M11.5 17h1" />
            <path d="M18.5 8.5a3 3 0 0 1 0 4" />
        </svg>

        <svg
            v-else-if="iconType === 'users'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <circle cx="9" cy="8" r="3" />
            <circle cx="16.5" cy="9.5" r="2.5" />
            <path d="M4.5 18a4.5 4.5 0 0 1 9 0" />
            <path d="M14 18a3.5 3.5 0 0 1 6 0" />
        </svg>

        <svg
            v-else-if="iconType === 'server'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <rect x="4" y="4.5" width="16" height="5" rx="1.5" />
            <rect x="4" y="14.5" width="16" height="5" rx="1.5" />
            <path d="M8 7h.01" />
            <path d="M8 17h.01" />
            <path d="M12 7h5" />
            <path d="M12 17h5" />
        </svg>

        <svg
            v-else-if="iconType === 'wallet'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M5 7.5A2.5 2.5 0 0 1 7.5 5H18a1 1 0 0 1 1 1v2H8a3 3 0 0 0 0 6h11v3a1 1 0 0 1-1 1H7.5A2.5 2.5 0 0 1 5 15.5z" />
            <path d="M19 8H8a3 3 0 0 0 0 6h11z" />
            <circle cx="15.5" cy="11" r="0.8" fill="currentColor" stroke="none" />
        </svg>

        <svg
            v-else-if="iconType === 'sparkles'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8z" />
            <path d="M19 3v4" />
            <path d="M21 5h-4" />
        </svg>

        <svg
            v-else-if="iconType === 'palette'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M12 4a8 8 0 1 0 0 16h1.5a2.5 2.5 0 0 0 0-5H12a2 2 0 0 1 0-4h1.5A3.5 3.5 0 0 0 17 7.5 3.5 3.5 0 0 0 13.5 4z" />
            <circle cx="7.5" cy="10" r="0.9" fill="currentColor" stroke="none" />
            <circle cx="10.5" cy="7.5" r="0.9" fill="currentColor" stroke="none" />
            <circle cx="14.5" cy="7.5" r="0.9" fill="currentColor" stroke="none" />
        </svg>

        <svg
            v-else-if="iconType === 'network'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <circle cx="12" cy="5" r="2" />
            <circle cx="5" cy="19" r="2" />
            <circle cx="19" cy="19" r="2" />
            <path d="M12 7v4" />
            <path d="M12 11l-5.5 6" />
            <path d="M12 11l5.5 6" />
        </svg>

        <svg
            v-else-if="iconType === 'laporan'"
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <!-- Document with bar chart inside -->
            <path d="M8 3.5h6l4 4V20a1 1 0 0 1-1 1H7a2 2 0 0 1-2-2V5.5a2 2 0 0 1 2-2z" />
            <path d="M14 3.5V8h4" />
            <path d="M9 17v-3" />
            <path d="M12 17v-5" />
            <path d="M15 17v-2" />
        </svg>

        <svg
            v-else
            :class="iconClasses"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.55"
            stroke-linecap="round"
            stroke-linejoin="round"
        >
            <path d="M5 6h14" />
            <path d="M5 12h14" />
            <path d="M5 18h14" />
            <circle cx="9" cy="6" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="15" cy="12" r="1.5" fill="currentColor" stroke="none" />
            <circle cx="11" cy="18" r="1.5" fill="currentColor" stroke="none" />
        </svg>
    </div>
</template>
