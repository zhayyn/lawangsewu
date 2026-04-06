<script setup>
import ThemeToggle from '@/Components/lawangsewu/ThemeToggle.vue';
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

const shellTheme = computed(() => (isDark.value ? 'theme-dark' : 'theme-light'));
const userName = computed(() => page.props.auth?.user?.alias || page.props.auth?.user?.name || 'Operator PTIP');
const userRole = computed(() => page.props.auth?.user?.email ?? 'Prototype internal mode');
const isSuperAdmin = computed(() => Boolean(page.props.auth?.isSuperAdmin));
const displayNavGroups = computed(() => {
    const accessibleGroups = props.navGroups
        .map(group => ({
            ...group,
            items: group.items.filter(item => Boolean(item.href)),
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
                    label: 'Kelola User',
                    short: 'US',
                    routeKey: 'admin-users',
                    href: route('admin.users.index'),
                    badge: 'Admin',
                },
            ],
        },
    ];
});
const primaryNav = computed(() => {
    return displayNavGroups.value
        .flatMap((group) => group.items)
        .filter((item) => ['dashboard', 'cctv', 'chat', 'satellite.pendopo', 'admin-users'].includes(item.routeKey));
});

const linkClasses = (item) => [
    'group flex items-center gap-3 rounded-2xl border px-3 py-3 transition duration-200',
    item.routeKey === props.currentRoute
        ? 'border-[var(--accent-border)] bg-[var(--accent-soft)] text-[var(--text-1)]'
        : 'border-transparent text-[var(--text-2)] hover:border-[var(--border)] hover:bg-[var(--surface-2)] hover:text-[var(--text-1)]',
].join(' ');

function toggleTheme() {
    isDark.value = !isDark.value;
}

function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
}

watch(isDark, (value) => {
    localStorage.setItem('lawangsewu-theme', value ? 'dark' : 'light');
});

onMounted(() => {
    const storedTheme = localStorage.getItem('lawangsewu-theme');

    if (storedTheme) {
        isDark.value = storedTheme === 'dark';
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
                    class="hidden border-r border-[var(--border)] bg-[var(--surface-0)]/85 backdrop-blur xl:flex xl:flex-col"
                    :class="isSidebarCollapsed ? 'xl:w-24' : 'xl:w-[19rem]'"
                >
                    <div class="flex items-center justify-between gap-3 px-6 py-8">
                        <div class="flex items-center gap-3">
                            <div class="relative group">
                                <div class="absolute -inset-1 bg-gradient-to-r from-blue-600 to-indigo-600 rounded-xl blur opacity-25 group-hover:opacity-50 transition duration-1000 group-hover:duration-200"></div>
                                <div class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-black text-sm font-black text-white">
                                    LS
                                </div>
                            </div>
                            <div v-if="!isSidebarCollapsed" class="leading-tight">
                                <p class="text-sm font-bold text-[var(--text-1)] tracking-tight">
                                    {{ appMeta.name }}
                                </p>
                                <p class="text-[10px] text-[var(--text-3)] font-medium uppercase tracking-wider">
                                    {{ appMeta.tagline }}
                                </p>
                            </div>
                        </div>

                        <!-- Futuristic Burger Toggle -->
                        <button
                            type="button"
                            class="relative flex h-9 w-9 items-center justify-center rounded-xl border border-[var(--border)] bg-[var(--surface-1)] hover:bg-[var(--surface-2)] transition-all group"
                            @click="isSidebarCollapsed = !isSidebarCollapsed"
                        >
                            <div class="flex flex-col gap-1.5 w-4">
                                <span :class="['h-0.5 bg-[var(--text-2)] transition-all duration-300 rounded-full', isSidebarCollapsed ? 'w-full' : 'w-full group-hover:w-2']"></span>
                                <span :class="['h-0.5 bg-[var(--text-2)] transition-all duration-300 rounded-full', isSidebarCollapsed ? 'w-2' : 'w-full']"></span>
                                <span :class="['h-0.5 bg-[var(--text-2)] transition-all duration-300 rounded-full', isSidebarCollapsed ? 'w-full' : 'w-3 group-hover:w-full']"></span>
                            </div>
                        </button>
                    </div>

                    <div class="flex-1 space-y-8 overflow-y-auto px-4 pb-6 scrollbar-none">
                        <section
                            v-for="group in displayNavGroups"
                            :key="group.label"
                            class="space-y-2"
                        >
                            <p
                                v-if="!isSidebarCollapsed"
                                class="px-4 text-[9px] font-black uppercase tracking-[0.3em] text-[var(--text-3)] opacity-60"
                            >
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
                                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300 relative',
                                            item.routeKey === props.currentRoute
                                                ? 'bg-blue-600/5 text-blue-500 font-semibold'
                                                : 'text-[var(--text-2)] hover:bg-[var(--surface-2)]'
                                        ]"
                                    >
                                        <!-- Active Indicator Dot -->
                                        <div v-if="item.routeKey === props.currentRoute" class="absolute left-0 w-1 h-5 bg-blue-500 rounded-r-full"></div>

                                        <div :class="[
                                            'flex h-8 w-8 items-center justify-center rounded-lg text-[10px] font-bold transition-all duration-300',
                                            item.routeKey === props.currentRoute
                                                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20 rotate-0'
                                                : 'bg-[var(--surface-2)] text-[var(--text-3)] group-hover:text-[var(--text-1)] group-hover:scale-110'
                                        ]">
                                            {{ item.short }}
                                        </div>
                                        
                                        <div v-if="!isSidebarCollapsed" class="flex-1 min-w-0">
                                            <p class="text-[13px] tracking-tight truncate">{{ item.label }}</p>
                                        </div>

                                        <span
                                            v-if="item.badge && !isSidebarCollapsed"
                                            :class="[
                                                'px-2 py-0.5 text-[8px] font-black uppercase tracking-widest rounded-full border',
                                                item.badge === 'LIVE' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-[var(--surface-3)] border-[var(--border)] text-[var(--text-3)]'
                                            ]"
                                        >
                                            {{ item.badge }}
                                        </span>
                                    </Link>

                                    <div
                                        v-else
                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 opacity-40 cursor-not-allowed group transition-all"
                                    >
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--surface-2)] text-[var(--text-3)] text-[10px] font-bold">
                                            {{ item.short }}
                                        </div>
                                        <div v-if="!isSidebarCollapsed" class="flex-1 min-w-0">
                                            <p class="text-[13px] tracking-tight truncate">{{ item.label }}</p>
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
                                class="secondary-button xl:hidden"
                                @click="toggleSidebar"
                            >
                                Menu
                            </button>

                            <div class="hidden min-w-0 flex-1 md:block">
                                <input
                                    type="text"
                                    class="input-surface w-full"
                                    placeholder="Cari modul, kamera, atau alias internal..."
                                >
                            </div>

                            <div class="hidden items-center gap-2 lg:flex">
                                <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-[var(--surface-2)] border border-[var(--border)] overflow-hidden transition-all hover:border-[var(--accent-border)]">
                                    <span class="text-[10px] font-black uppercase tracking-widest text-[var(--text-3)]">Ecosystem</span>
                                    <svg class="w-2.5 h-2.5 text-[var(--text-3)]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                    <span class="text-[10px] font-black uppercase tracking-widest text-blue-500 whitespace-nowrap">
                                        {{ props.currentRoute === 'dashboard' ? 'Overview' : props.currentRoute.replace('lawangsewu.', '').replace('.', ' > ').toUpperCase() }}
                                    </span>
                                </div>
                                <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-3 py-2 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-500">
                                    Semua sistem normal
                                </span>
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
                                        {{ isSuperAdmin ? 'Superadmin' : 'Operator' }}
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

                    <main class="mx-auto w-full max-w-[1800px] flex-1 px-4 py-5 pb-24 sm:px-6 xl:px-8 xl:pb-10">
                        <slot />
                    </main>
                </div>
            </div>

            <div
                v-if="isSidebarOpen"
                class="fixed inset-0 z-50 bg-black/50 xl:hidden"
                @click="isSidebarOpen = false"
            >
                <aside
                    class="h-full w-[19rem] border-r border-[var(--border)] bg-[var(--surface-0)] px-4 py-5"
                    @click.stop
                >
                    <div class="mb-6 flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-[var(--text-1)]">
                                {{ appMeta.name }}
                            </p>
                            <p class="text-xs text-[var(--text-2)]">
                                {{ appMeta.tagline }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="ghost-button"
                            @click="isSidebarOpen = false"
                        >
                            Close
                        </button>
                    </div>

                    <div class="space-y-5 overflow-y-auto">
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

                    <div class="space-y-6 overflow-y-auto scrollbar-none pb-10">
                        <section
                            v-for="group in displayNavGroups"
                            :key="group.label"
                            class="space-y-3"
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
                                            'group flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300 relative',
                                            item.routeKey === props.currentRoute
                                                ? 'bg-blue-600/5 text-blue-500 font-semibold'
                                                : 'text-[var(--text-2)] hover:bg-[var(--surface-2)]'
                                        ]"
                                        @click="isSidebarOpen = false"
                                    >
                                        <div :class="[
                                            'flex h-8 w-8 items-center justify-center rounded-lg text-[10px] font-bold transition-all duration-300',
                                            item.routeKey === props.currentRoute
                                                ? 'bg-blue-600 text-white shadow-lg shadow-blue-600/20'
                                                : 'bg-[var(--surface-2)] text-[var(--text-3)]'
                                        ]">
                                            {{ item.short }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[13px] tracking-tight truncate">{{ item.label }}</p>
                                        </div>
                                        <span
                                            v-if="item.badge"
                                            :class="[
                                                'px-2 py-0.5 text-[8px] font-black uppercase tracking-widest rounded-full border',
                                                item.badge === 'LIVE' ? 'bg-emerald-500/10 border-emerald-500/20 text-emerald-500' : 'bg-[var(--surface-3)] border-[var(--border)] text-[var(--text-3)]'
                                            ]"
                                        >
                                            {{ item.badge }}
                                        </span>
                                    </Link>

                                    <div
                                        v-else
                                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 opacity-40 cursor-not-allowed group"
                                    >
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[var(--surface-2)] text-[var(--text-3)] text-[10px] font-bold">
                                            {{ item.short }}
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-[13px] tracking-tight truncate">{{ item.label }}</p>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </section>
                    </div>
                </aside>
            </div>

            <nav class="fixed inset-x-4 bottom-4 z-40 xl:hidden">
                <div class="card-surface grid grid-cols-3 p-2">
                    <Link
                        v-for="item in primaryNav"
                        :key="item.routeKey"
                        :href="item.href"
                        class="rounded-2xl px-3 py-3 text-center text-xs font-semibold uppercase tracking-[0.2em] transition"
                        :class="item.routeKey === currentRoute ? 'bg-[var(--accent-soft)] text-[var(--text-1)]' : 'text-[var(--text-2)]'"
                    >
                        {{ item.short }}
                    </Link>
                </div>
            </nav>
        </div>
    </div>
</template>
