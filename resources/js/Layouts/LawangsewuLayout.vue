<script setup>
import NavItemIcon from '@/Components/lawangsewu/NavItemIcon.vue';
import ThemeToggle from '@/Components/lawangsewu/ThemeToggle.vue';
import LoginToast from '@/Components/lawangsewu/LoginToast.vue';
import LawangsewuAtomCubeLogo from '@/Components/lawangsewu/LawangsewuAtomCubeLogo.vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';

const props = defineProps({
    currentRoute: {
        type: String,
        required: true,
    },
    navGroups: {
        type: Array,
        default: () => [],
    },
    appMeta: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const isDark = ref(true);
const isSidebarOpen = ref(false);
const isSidebarCollapsed = ref(false);
const isSidebarAnimating = ref(false);
let sidebarAnimationTimer = null;

const shellTheme = computed(() => (isDark.value ? 'dark theme-dark' : 'theme-light'));
const userName = computed(() => page.props.auth?.user?.alias || page.props.auth?.user?.name || 'Operator PTIP');
const isSuperAdmin = computed(() => Boolean(page.props.auth?.isSuperAdmin));
const isViewer = computed(() => page.props.auth?.user?.role === 'viewer');
const isOperator = computed(() => page.props.auth?.user?.role === 'operator');
const viewerRouteKeys = ['dashboard', 'cctv', 'chat', 'guestbook'];
const operatorRouteKeys = ['ptsp', 'wacaraka', 'chat'];
const safeRoute = (name, fallback = '#') => {
    try {
        return route(name);
    } catch {
        return fallback;
    }
};

const displayNavGroups = computed(() => {
    const accessibleGroups = props.navGroups
        .map(group => ({
            ...group,
            items: group.items.filter((item) => {
                if (!item.href) {
                    return false;
                }

                if (isViewer.value) {
                    return viewerRouteKeys.includes(item.routeKey);
                }

                if (isOperator.value) {
                    return operatorRouteKeys.includes(item.routeKey);
                }

                return true;
            }),
        }))
        .filter(group => group.items.length > 0);

    if (!isSuperAdmin.value) {
        return accessibleGroups;
    }

    return [
        ...accessibleGroups,
        {
            label: 'Superadmin',
            items: [
                {
                    label: 'Monitor Sistem',
                    short: 'MS',
                    routeKey: 'admin-system-monitor',
                    href: safeRoute('admin.system-monitor.index', '/admin/system-monitor'),
                    badge: 'Admin',
                },
                {
                    label: 'Laporan',
                    short: 'LR',
                    routeKey: 'admin-laporan',
                    href: safeRoute('admin.laporan.index', '/admin/laporan'),
                    badge: 'Admin',
                },
                {
                    label: 'Tailscale Network',
                    short: 'TS',
                    routeKey: 'tailscale',
                    href: safeRoute('lawangsewu.tailscale.index', '/tailscale'),
                    badge: 'Superadmin',
                },
                {
                    label: 'Kelola CCTV',
                    short: 'CC',
                    routeKey: 'admin-cctv',
                    href: safeRoute('admin.cctv.index', '/admin/cctv'),
                    badge: 'Admin',
                },
                {
                    label: 'Kelola User',
                    short: 'US',
                    routeKey: 'admin-users',
                    href: safeRoute('admin.users.index', '/admin/users'),
                    badge: 'Admin',
                },
            ],
        },
    ];
});
function shouldShowNavBadge(item) {
    return Boolean(item.badge) && !isOperator.value;
}

function getBadgeText(badge) {
    if (!badge) return '';
    const upper = badge.toUpperCase();
    if (upper === 'LIVE' || upper === 'READY') return 'Ready';
    if (upper === 'TBD') return 'TBD';
    return badge.charAt(0).toUpperCase() + badge.slice(1).toLowerCase();
}

function getBadgeStyle(badge) {
    if (!badge) return '';
    const upper = badge.toUpperCase();
    if (upper === 'LIVE' || upper === 'READY') {
        return 'border-emerald-500/50 text-emerald-600 dark:border-emerald-400/50 dark:text-emerald-400';
    }
    if (upper === 'ADMIN' || upper === 'SUPERADMIN') {
        return 'border-indigo-500/60 text-indigo-700 dark:border-indigo-400/60 dark:text-indigo-400';
    }
    if (upper === 'TBD') {
        return 'border-red-500/60 text-red-600 dark:border-red-400/60 dark:text-red-400';
    }
    return 'border-[var(--border)] text-[var(--text-3)]';
}

function toggleTheme() {
    isDark.value = !isDark.value;
}

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
}

function toggleSidebarCollapsed() {
    isSidebarAnimating.value = true;

    if (sidebarAnimationTimer) {
        clearTimeout(sidebarAnimationTimer);
    }

    isSidebarCollapsed.value = !isSidebarCollapsed.value;

    sidebarAnimationTimer = setTimeout(() => {
        isSidebarAnimating.value = false;
        sidebarAnimationTimer = null;
    }, 340);
}

watch(isDark, (value) => {
    localStorage.setItem('lawangsewu-theme', value ? 'dark' : 'light');
});

watch(isSidebarCollapsed, (value) => {
    localStorage.setItem('lawangsewu-sidebar-collapsed', value ? '1' : '0');
});

onMounted(() => {
    const storedTheme = localStorage.getItem('lawangsewu-theme');
    const storedSidebarState = localStorage.getItem('lawangsewu-sidebar-collapsed');

    if (storedTheme) {
        isDark.value = storedTheme === 'dark';
    }

    if (storedSidebarState !== null) {
        isSidebarCollapsed.value = storedSidebarState === '1';
    }

    if (window.innerWidth < 1440) {
        isSidebarCollapsed.value = false;
    }
});
</script>

<template>
    <div :class="shellTheme">
        <div
            class="min-h-screen bg-[var(--surface-0)] text-[var(--text-1)]"
            :style="{ backgroundImage: 'var(--app-gradient)' }"
        >
            <div class="flex min-h-screen">
                <aside
                    class="relative hidden overflow-visible border-r border-[var(--border)] bg-[var(--surface-0)]/85 backdrop-blur transition-[width] duration-300 ease-[cubic-bezier(.4,0,.2,1)] lg:flex lg:flex-col"
                    :class="isSidebarCollapsed ? 'lg:w-24' : 'lg:w-[19rem]'"
                >
                    <div class="flex items-center justify-between gap-3 px-6 py-8">
                        <div class="flex items-center gap-3">
                            <div class="relative group cursor-pointer flex-shrink-0 transition-transform duration-500 hover:scale-105 active:scale-95">
                                <div class="relative flex items-center justify-center overflow-visible">
                                    <LawangsewuAtomCubeLogo :size="30" />
                                </div>
                            </div>
                            <div
                                class="leading-tight overflow-hidden origin-left transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]"
                                :class="isSidebarCollapsed ? 'max-w-0 opacity-0 -translate-x-1' : 'max-w-[14rem] opacity-100 translate-x-0'"
                            >
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <p class="inline-block origin-left text-[17.5px] font-black tracking-tighter whitespace-nowrap text-blue-700 dark:text-blue-500 drop-shadow-[0_2px_1px_rgba(0,0,0,0.15)] dark:drop-shadow-[0_2px_1px_rgba(0,0,0,0.4)] group-hover:scale-110 group-hover:text-blue-500 dark:group-hover:text-blue-400 group-hover:drop-shadow-[0_0_12px_rgba(37,99,235,0.8)] dark:group-hover:drop-shadow-[0_0_15px_rgba(96,165,250,1)] transition-all duration-300">
                                        LAWANGSEWU
                                    </p>
                                    <span class="px-1.5 py-[2px] text-[7px] font-black uppercase tracking-widest rounded border border-[var(--border)] bg-[var(--surface-1)] text-[var(--text-2)] shadow-sm transition-transform duration-300 group-hover:translate-x-4">
                                        VERSI 2.1.0
                                    </span>
                                </div>
                                <div class="overflow-hidden w-[160px] mt-0.5 relative">
                                    <div class="flex animate-marquee-seamless whitespace-nowrap">
                                        <p class="text-[7.5px] text-[var(--text-3)] font-bold uppercase tracking-[0.15em] px-1.5 flex-shrink-0">
                                            ✦ Layanan Aplikasi Web Pengadilan Agama Semarang dan Workspace Utama
                                        </p>
                                        <p class="text-[7.5px] text-[var(--text-3)] font-bold uppercase tracking-[0.15em] px-1.5 flex-shrink-0">
                                            ✦ Layanan Aplikasi Web Pengadilan Agama Semarang dan Workspace Utama
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>



                    <div class="flex-1 space-y-8 overflow-y-auto px-4 pb-6 scrollbar-none">
                        <section
                            v-for="(group, groupIndex) in displayNavGroups"
                            :key="group.label"
                            class="space-y-2 pb-2"
                            :class="groupIndex > 0 ? 'border-t border-slate-200/50 dark:border-white/10 pt-2' : ''"
                        >
                            <p
                                class="px-4 text-[9px] font-black uppercase tracking-[0.3em] text-[var(--text-3)] transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]"
                                :class="isSidebarCollapsed ? 'max-h-0 opacity-0 -translate-y-1 overflow-hidden pointer-events-none' : 'max-h-5 opacity-60 translate-y-0'"
                            >
                                {{ group.label }}
                            </p>

                            <div class="space-y-1">
                                <template
                                    v-for="(item, itemIndex) in group.items"
                                    :key="item.label"
                                >
                                    <Link
                                        v-if="item.href"
                                        :href="item.href"
                                        :class="[
                                            'nav-tilt group flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-500 relative isolate overflow-hidden will-change-transform',
                                            item.routeKey === props.currentRoute
                                                ? 'bg-blue-600/5 text-slate-950 dark:text-blue-400 font-semibold shadow-[0_10px_30px_-24px_rgba(37,99,235,0.75)]'
                                                : 'text-slate-950 dark:text-[#ffffff] hover:bg-[var(--surface-2)] hover:text-slate-950 dark:hover:text-[#ffffff] hover:shadow-[0_18px_40px_-28px_rgba(15,23,42,0.5)]'
                                        ]"
                                    >
                                        <div class="pointer-events-none absolute inset-0 rounded-xl bg-[linear-gradient(120deg,transparent,rgba(255,255,255,0.08),transparent)] opacity-0 translate-x-[-120%] transition-all duration-700 group-hover:translate-x-[120%] group-hover:opacity-100"></div>
                                        <div class="pointer-events-none absolute inset-x-3 bottom-0 h-px bg-gradient-to-r from-transparent via-sky-400/40 to-transparent opacity-0 transition-opacity duration-500 group-hover:opacity-100"></div>

                                        <!-- Active Indicator Dot -->
                                        <div v-if="item.routeKey === props.currentRoute" class="absolute left-0 w-1 h-5 bg-blue-500 rounded-r-full"></div>

                                        <NavItemIcon
                                            :route-key="item.routeKey"
                                            :active="item.routeKey === props.currentRoute"
                                            :dark="isDark"
                                        />
                                        
                                        <div
                                            class="min-w-0 transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]"
                                            :class="isSidebarCollapsed ? 'w-0 opacity-0 -translate-x-1 overflow-hidden' : 'flex-1 opacity-100 translate-x-0'"
                                        >
                                            <p class="text-[13px] tracking-tight truncate text-slate-950 dark:text-inherit">{{ item.label }}</p>
                                        </div>

                                        <span
                                            v-if="shouldShowNavBadge(item)"
                                            :class="[
                                                'px-2 py-[2px] text-[10px] font-medium tracking-wide rounded-full border bg-transparent transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]',
                                                isSidebarCollapsed ? 'max-w-0 scale-90 opacity-0 pointer-events-none overflow-hidden px-0 py-0 border-transparent' : 'max-w-20 scale-100 opacity-100',
                                                getBadgeStyle(item.badge)
                                            ]"
                                        >
                                            {{ getBadgeText(item.badge) }}
                                        </span>
                                    </Link>

                                    <div
                                        v-else
                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 opacity-40 cursor-not-allowed group transition-all text-slate-950 dark:text-[#ffffff]"
                                    >
                                        <NavItemIcon :route-key="item.routeKey" :dark="isDark" />
                                        <div
                                            class="min-w-0 transition-all duration-300 ease-[cubic-bezier(.4,0,.2,1)]"
                                            :class="isSidebarCollapsed ? 'w-0 opacity-0 -translate-x-1 overflow-hidden' : 'flex-1 opacity-100 translate-x-0'"
                                        >
                                            <p class="text-[13px] tracking-tight truncate text-slate-950 dark:text-inherit">{{ item.label }}</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </div>

                </aside>

                <div class="flex min-w-0 flex-1 flex-col">
                    <header class="sticky top-0 z-30 border-b border-[var(--border)] bg-[var(--surface-0)]/85 backdrop-blur">
                        <div class="mx-auto flex max-w-[1800px] items-center gap-3 px-4 py-4 sm:px-6 xl:px-8">
                            <button
                                type="button"
                                class="group relative inline-flex h-10 w-10 items-center justify-center overflow-hidden rounded-xl border border-[var(--border)] bg-[var(--surface-1)] text-[var(--text-1)] transition-all duration-300 hover:-translate-y-0.5 hover:border-[var(--accent-border)] hover:bg-[var(--surface-2)] hover:shadow-[0_14px_32px_-24px_rgba(15,23,42,0.65)] lg:hidden"
                                @click="toggleSidebar"
                                aria-label="Buka menu samping"
                            >
                                <span class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(56,189,248,0.16),transparent_60%)] opacity-0 transition-opacity duration-300 group-hover:opacity-100"></span>
                                <span class="relative h-4 w-4">
                                    <span class="absolute left-0 top-0 h-0.5 w-4 rounded-full bg-current transition-all duration-300 group-hover:w-3"></span>
                                    <span class="absolute left-0 top-[7px] h-0.5 w-4 rounded-full bg-current transition-all duration-300 group-hover:translate-x-0.5"></span>
                                    <span class="absolute left-0 top-[14px] h-0.5 w-4 rounded-full bg-current transition-all duration-300 group-hover:w-3 group-hover:translate-x-1"></span>
                                </span>
                            </button>

                            <!-- Desktop Sidebar Toggle Button -->
                            <button
                                type="button"
                                class="group hidden h-10 w-10 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface-1)] text-[var(--text-3)] hover:border-[var(--accent-border)] hover:bg-[var(--surface-2)] hover:text-[var(--text-1)] lg:flex transition-all duration-300 shadow-sm shrink-0"
                                :title="isSidebarCollapsed ? 'Tampilkan Sidebar' : 'Sembunyikan Sidebar'"
                                @click="toggleSidebarCollapsed"
                            >
                                <svg v-if="!isSidebarCollapsed" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><path d="M9 3v18"/><path d="m16 15-3-3 3-3"/></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><path d="M9 3v18"/><path d="m14 9 3 3-3 3"/></svg>
                            </button>

                            <div v-if="!isViewer && !isOperator" class="hidden min-w-0 flex-1 md:block">
                                <input
                                    type="text"
                                    class="input-surface w-full"
                                    placeholder="Cari modul, kamera, atau alias internal..."
                                >
                            </div>

                            <div v-if="!isViewer && !isOperator" class="hidden items-center gap-4 lg:flex ml-2">
                                <!-- Elegant Breadcrumb -->
                                <Link :href="safeRoute('lawangsewu.dashboard')" class="group relative flex items-center gap-3 px-1 py-1 rounded-full bg-gradient-to-r from-[var(--surface-1)] to-[var(--surface-2)] p-1 border border-[var(--border)] shadow-sm hover:shadow-md transition-all duration-300 overflow-hidden cursor-pointer">
                                    <div class="absolute inset-0 bg-blue-500/5 opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    
                                    <div class="pl-3 pr-2 flex items-center gap-2">
                                        <!-- Animated Glowing Dot -->
                                        <div class="relative flex h-2 w-2 items-center justify-center">
                                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                            <span class="relative inline-flex h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        </div>
                                        <span class="text-[10px] font-black uppercase tracking-[0.15em] text-[var(--text-3)] group-hover:text-[var(--text-2)] transition-colors">Workspace</span>
                                    </div>

                                    <div class="flex items-center text-[var(--text-3)]/40">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>

                                    <div class="relative bg-[var(--surface-0)] rounded-full px-4 py-1.5 border border-[var(--border)] shadow-sm z-10 transition-transform duration-300 group-hover:-translate-y-0.5 overflow-hidden">
                                        <div class="absolute inset-0 bg-gradient-to-r from-blue-500/0 via-blue-500/10 to-indigo-500/0 opacity-0 group-hover:opacity-100 translate-x-[-100%] group-hover:translate-x-[100%] transition-all duration-1000 ease-in-out"></div>
                                        <span class="relative text-[10px] font-black uppercase tracking-[0.2em] bg-gradient-to-r from-blue-600 to-indigo-500 bg-clip-text text-transparent drop-shadow-sm whitespace-nowrap">
                                            {{ props.currentRoute === 'dashboard' ? 'Overview' : props.currentRoute.replace('lawangsewu.', '').replace('.', ' > ').toUpperCase() }}
                                        </span>
                                    </div>
                                </Link>
                            </div>

                            <ThemeToggle
                                :dark="isDark"
                                @toggle="toggleTheme"
                            />

                            <div class="hidden items-center gap-3 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] px-3 py-2 md:flex group hover:border-[var(--accent-border)] transition-all duration-300">
                                <div class="relative">
                                    <img 
                                        v-if="page.props.auth?.user?.avatar" 
                                        :src="page.props.auth.user.avatar" 
                                        class="h-9 w-9 rounded-xl object-cover ring-2 ring-transparent group-hover:ring-[var(--accent-soft)] transition-all"
                                        alt="Avatar"
                                    >
                                    <div v-else class="flex h-9 w-9 items-center justify-center rounded-xl bg-[var(--surface-2)] text-xs font-black text-[var(--text-3)] shadow-inner">
                                        {{ userName.split(' ').map(n => n[0]).join('').slice(0,2).toUpperCase() }}
                                    </div>
                                    <div class="absolute -bottom-1 -right-1 h-3 w-3 rounded-full border-2 border-[var(--surface-1)] bg-emerald-500 shadow-sm animate-pulse-slow"></div>
                                </div>
                                <div class="flex flex-col pr-1">
                                    <p class="text-[13px] font-black text-[var(--text-1)] tracking-tight leading-none mb-1">
                                        {{ userName }}
                                    </p>
                                    <p class="text-[10px] font-bold text-[var(--accent)] uppercase tracking-widest leading-none opacity-80">
                                        {{ isSuperAdmin ? 'Superadmin' : (isViewer ? 'Viewer' : 'Operator') }}
                                    </p>
                                </div>
                            </div>

                            <div class="hidden items-center gap-2 md:flex">
                                <Link
                                    v-if="isSuperAdmin"
                                    :href="route('admin.users.index')"
                                    class="secondary-button"
                                >
                                    Kelola User
                                </Link>

                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="github-button"
                                >
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </header>

                    <main class="mx-auto w-full max-w-[1800px] flex-1 px-4 py-5 pb-8 sm:px-6 lg:px-8 xl:pb-10">
                        <slot />
                    </main>
                </div>
            </div>

            <div
                v-if="isSidebarOpen"
                class="fixed inset-0 z-50 bg-black/50 lg:hidden"
                @click="isSidebarOpen = false"
            >
                <aside
                    class="flex h-full w-[85vw] max-w-[19rem] flex-col border-r border-[var(--border)] bg-[var(--surface-0)] px-4 py-5 shadow-[0_22px_60px_-30px_rgba(15,23,42,0.8)]"
                    @click.stop
                >
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="relative flex shrink-0 items-center justify-center overflow-visible">
                                <LawangsewuAtomCubeLogo :size="30" />
                            </div>
                            <div class="leading-tight">
                                <div class="flex items-center gap-1.5 mb-0.5">
                                    <p class="text-[17px] font-black tracking-tighter text-blue-700 dark:text-blue-500 drop-shadow-[0_2px_1px_rgba(0,0,0,0.15)] dark:drop-shadow-[0_2px_1px_rgba(0,0,0,0.4)]">
                                        LAWANGSEWU
                                    </p>
                                    <span class="px-1.5 py-[2px] text-[7px] font-black uppercase tracking-widest rounded border border-[var(--border)] bg-[var(--surface-1)] text-[var(--text-2)] shadow-sm">
                                        VERSI 2.1.0
                                    </span>
                                </div>
                                <p class="text-[9px] font-bold uppercase tracking-[0.16em] text-[var(--text-3)]">
                                    Ekosistem Digital Internal PA Semarang
                                </p>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="ghost-button"
                            @click="isSidebarOpen = false"
                        >
                            Close
                        </button>
                    </div>

                    <div class="space-y-5 border-b border-[var(--border)] pb-5">
                        <div class="space-y-2">
                            <Link
                                v-if="isSuperAdmin"
                                :href="route('admin.users.index')"
                                class="secondary-button w-full"
                                @click="isSidebarOpen = false"
                            >
                                Kelola User
                            </Link>
                            <Link
                                :href="route('logout')"
                                method="post"
                                as="button"
                                class="github-button w-full"
                                @click="isSidebarOpen = false"
                            >
                                Logout
                            </Link>
                        </div>
                    </div>

                    <div class="mt-5 flex-1 space-y-6 overflow-y-auto scrollbar-none pb-10">
                        <section
                            v-for="(group, groupIndex) in displayNavGroups"
                            :key="group.label"
                            class="space-y-3 pb-2"
                            :class="groupIndex > 0 ? 'border-t border-slate-200/50 dark:border-white/10 pt-2' : ''"
                        >
                            <p class="px-3 text-[9px] font-black uppercase tracking-[0.3em] text-[var(--text-3)] opacity-60">
                                {{ group.label }}
                            </p>

                            <div class="space-y-1">
                                <template
                                    v-for="item in group.items"
                                    :key="item.label"
                                >
                                    <Link
                                        v-if="item.href"
                                        :href="item.href"
                                        :class="[
                                            'nav-tilt-mobile group flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300 relative isolate overflow-hidden',
                                            item.routeKey === props.currentRoute
                                                ? 'bg-blue-600/5 text-slate-950 dark:text-blue-400 font-semibold shadow-[0_10px_24px_-22px_rgba(37,99,235,0.8)]'
                                                : 'text-slate-950 dark:text-[#ffffff] hover:bg-[var(--surface-2)] hover:text-slate-950 dark:hover:text-[#ffffff]'
                                        ]"
                                        @click="isSidebarOpen = false"
                                    >
                                        <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(120deg,transparent,rgba(255,255,255,0.08),transparent)] opacity-0 translate-x-[-120%] transition-all duration-700 group-hover:translate-x-[120%] group-hover:opacity-100"></div>
                                        <NavItemIcon
                                            :route-key="item.routeKey"
                                            :active="item.routeKey === props.currentRoute"
                                            :dark="isDark"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[13px] tracking-tight truncate text-slate-950 dark:text-inherit">{{ item.label }}</p>
                                        </div>
                                        <span
                                            v-if="shouldShowNavBadge(item)"
                                            :class="[
                                                'px-2 py-[2px] text-[10px] font-medium tracking-wide rounded-full border bg-transparent',
                                                getBadgeStyle(item.badge)
                                            ]"
                                        >
                                            {{ getBadgeText(item.badge) }}
                                        </span>
                                    </Link>

                                    <div
                                        v-else
                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 opacity-40 cursor-not-allowed group text-slate-950 dark:text-[#ffffff]"
                                    >
                                        <NavItemIcon :route-key="item.routeKey" :dark="isDark" />
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[13px] tracking-tight truncate text-slate-950 dark:text-inherit">{{ item.label }}</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>

        </div>

        <!-- Login success toast -->
        <LoginToast />
    </div>
</template>

<style scoped>


.nav-tilt {
    transform-style: preserve-3d;
}

@keyframes marqueeSeamless {
    0% { transform: translateX(0%); }
    100% { transform: translateX(-50%); }
}
.animate-marquee-seamless {
    animation: marqueeSeamless 15s linear infinite;
}

.nav-tilt:hover {
    transform: perspective(960px) rotateX(4deg) rotateY(-7deg) translateX(4px) translateY(-1px);
}

.nav-tilt > * {
    transform: translateZ(0);
}

.nav-tilt:hover > * {
    transform: translateZ(10px);
}

.nav-tilt-mobile:hover {
    transform: translateX(4px) scale(1.01);
}
</style>
