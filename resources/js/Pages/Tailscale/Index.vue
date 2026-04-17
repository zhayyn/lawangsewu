<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    appMeta:       { type: Object, default: () => ({}) },
    navGroups:     { type: Array,  default: () => [] },
    devices:       { type: Object, default: () => ({}) },
    subnet:        { type: String, default: '' },
    tailscaleUp:   { type: String, default: '' },
    tailscaleInfo: { type: Object, default: () => ({}) },
});

// ── State ─────────────────────────────────────────────────
const networkData  = ref(null);
const pingData     = ref(null);
const cliInfo      = ref(props.tailscaleInfo);
const isRefreshing = ref(false);
const isPinging    = ref(false);
const activeTab    = ref('network');
const lastChecked  = ref('');
const expandedDevice = ref(null);
let autoRefreshTimer = null;

// ── Computed ───────────────────────────────────────────────
const summary = computed(() => networkData.value?.summary ?? { total: Object.keys(props.devices).length, reachable: 0, unreachable: 0 });
const deviceList = computed(() => networkData.value?.devices ?? []);
const tsOnline = computed(() => cliInfo.value?.available ?? false);
const tsSelf   = computed(() => cliInfo.value?.self ?? null);
const tsPeers  = computed(() => cliInfo.value?.peers ?? []);

const tabs = [
    { key: 'network',  label: 'Network Monitor',  icon: 'monitor' },
    { key: 'services', label: 'Quick Access',      icon: 'link' },
    { key: 'tailscale',label: 'Tailscale CLI',     icon: 'shield' },
    { key: 'subnet',   label: 'Subnet & Routes',   icon: 'route' },
];

// ── Actions ────────────────────────────────────────────────
async function refreshNetwork() {
    isRefreshing.value = true;
    try {
        const r = await fetch(route('lawangsewu.tailscale.network-status'));
        const d = await r.json();
        networkData.value = d;
        lastChecked.value = d.checked_at ?? '';
    } catch(e) { console.error(e); }
    finally { isRefreshing.value = false; }
}

async function doPing() {
    isPinging.value = true;
    try {
        const r = await fetch(route('lawangsewu.tailscale.ping-all'));
        pingData.value = (await r.json()).pings ?? null;
    } catch(e) { console.error(e); }
    finally { isPinging.value = false; }
}

async function refreshCli() {
    try {
        const r = await fetch(route('lawangsewu.tailscale.cli-info'));
        cliInfo.value = (await r.json()).info ?? cliInfo.value;
    } catch(e) { console.error(e); }
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text);
}

function getLatency(key) {
    if (!pingData.value || !pingData.value[key]) return null;
    return pingData.value[key].latency;
}

function latencyColor(ms) {
    if (ms === null) return 'text-red-400';
    if (ms < 2)  return 'text-emerald-400';
    if (ms < 10) return 'text-yellow-400';
    return 'text-orange-400';
}

function roleColor(role) {
    return { web: 'bg-sky-500/20 text-sky-400 border-sky-500/30', database: 'bg-violet-500/20 text-violet-400 border-violet-500/30', legacy: 'bg-amber-500/20 text-amber-400 border-amber-500/30' }[role] ?? 'bg-slate-500/20 text-slate-400';
}

function protocolColor(p) {
    return { HTTPS: 'text-emerald-400', HTTP: 'text-sky-400', SSH: 'text-amber-400', TCP: 'text-slate-400', MySQL: 'text-violet-400', RDP: 'text-rose-400' }[p] ?? 'text-slate-400';
}

onMounted(() => {
    refreshNetwork();
    doPing();
    refreshCli();
    autoRefreshTimer = setInterval(refreshNetwork, 30000);
});
onUnmounted(() => clearInterval(autoRefreshTimer));
</script>

<template>
    <LawangsewuLayout current-route="tailscale" :nav-groups="navGroups" :app-meta="appMeta">
        <div class="space-y-5">

            <!-- Header -->
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 shadow-[0_8px_20px_-10px_rgba(20,184,166,0.7)]">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="5" r="2"/><circle cx="5" cy="19" r="2"/><circle cx="19" cy="19" r="2"/><path d="M12 7v4M12 11l-5.5 6M12 11l5.5 6"/></svg>
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-[var(--text-1)] tracking-tight">Tailscale Network Control</h1>
                        <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest">PA Semarang • {{ subnet }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span v-if="lastChecked" class="text-[10px] text-[var(--text-3)]">Cek: {{ lastChecked }}</span>
                    <button @click="refreshNetwork(); doPing();" :disabled="isRefreshing"
                        class="flex items-center gap-1.5 rounded-xl border border-teal-500/30 bg-teal-500/10 px-3 py-1.5 text-[11px] font-bold text-teal-400 uppercase tracking-widest transition hover:bg-teal-500/20 disabled:opacity-50">
                        <svg :class="isRefreshing ? 'animate-spin' : ''" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0115-3.87M20 15a9 9 0 01-15 3.87"/></svg>
                        Refresh
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div v-for="card in [
                    { label: 'Total Server',   value: summary.total,       color: 'from-slate-500 to-slate-600',   text: 'text-slate-300' },
                    { label: 'Reachable',      value: summary.reachable,   color: 'from-emerald-500 to-teal-600', text: 'text-emerald-300' },
                    { label: 'Unreachable',    value: summary.unreachable, color: 'from-red-500 to-rose-600',     text: 'text-red-300' },
                    { label: 'Tailscale Peers',value: tsPeers.length,      color: 'from-teal-500 to-cyan-600',    text: 'text-teal-300' },
                ]" :key="card.label"
                    class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4 shadow-[0_4px_16px_-8px_rgba(0,0,0,0.4)]">
                    <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest">{{ card.label }}</p>
                    <p :class="['text-3xl font-black mt-1', card.text]">{{ card.value }}</p>
                </div>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 rounded-xl border border-[var(--border)] bg-[var(--surface-0)] p-1">
                <button v-for="tab in tabs" :key="tab.key"
                    :class="['flex-1 rounded-lg px-3 py-2 text-[11px] font-bold uppercase tracking-widest transition-all duration-200',
                        activeTab === tab.key
                            ? 'bg-teal-500/20 text-teal-400 border border-teal-500/30'
                            : 'text-[var(--text-3)] hover:text-[var(--text-2)]']"
                    @click="activeTab = tab.key">
                    {{ tab.label }}
                </button>
            </div>

            <!-- TAB: Network Monitor -->
            <div v-if="activeTab === 'network'" class="space-y-3">
                <div v-if="deviceList.length === 0" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-8 text-center">
                    <p class="text-[var(--text-3)] text-sm">Tekan Refresh untuk cek status jaringan.</p>
                </div>
                <div v-for="dev in deviceList" :key="dev.key"
                    class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] overflow-hidden shadow-[0_4px_16px_-8px_rgba(0,0,0,0.4)]">
                    <!-- Device Header -->
                    <button class="w-full flex items-center justify-between px-5 py-4 hover:bg-[var(--surface-2)] transition"
                        @click="expandedDevice = expandedDevice === dev.key ? null : dev.key">
                        <div class="flex items-center gap-3">
                            <div :class="['h-2.5 w-2.5 rounded-full flex-shrink-0', dev.reachable ? 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.8)]' : 'bg-red-500']"></div>
                            <div class="text-left">
                                <p class="text-[13px] font-bold text-[var(--text-1)]">{{ dev.label }}</p>
                                <p class="text-[11px] text-[var(--text-3)]">{{ dev.ip }} • {{ dev.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span :class="['text-[10px] font-black uppercase tracking-widest px-2.5 py-1 rounded-full border', roleColor(dev.role)]">{{ dev.role }}</span>
                            <!-- Ping latency -->
                            <span v-if="getLatency(dev.key) !== null" :class="['text-[11px] font-mono font-bold', latencyColor(getLatency(dev.key))]">
                                {{ getLatency(dev.key).toFixed(1) }}ms
                            </span>
                            <span v-else-if="isPinging" class="text-[10px] text-[var(--text-3)]">...</span>
                            <svg :class="['h-4 w-4 text-[var(--text-3)] transition-transform', expandedDevice === dev.key ? 'rotate-180' : '']" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </button>
                    <!-- Services List -->
                    <div v-if="expandedDevice === dev.key" class="border-t border-[var(--border)] divide-y divide-[var(--border)]">
                        <div v-for="svc in dev.services" :key="svc.port"
                            class="flex items-center justify-between px-5 py-3 bg-[var(--surface-0)]">
                            <div class="flex items-center gap-3">
                                <div :class="['h-1.5 w-1.5 rounded-full', svc.reachable ? 'bg-emerald-400' : 'bg-red-500']"></div>
                                <span class="text-[12px] font-semibold text-[var(--text-1)]">{{ svc.name }}</span>
                                <span :class="['text-[10px] font-bold font-mono', protocolColor(svc.protocol)]">{{ svc.protocol }}:{{ svc.port }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span :class="['text-[10px] font-black uppercase', svc.reachable ? 'text-emerald-400' : 'text-red-400']">
                                    {{ svc.reachable ? 'UP' : 'DOWN' }}
                                </span>
                                <a v-if="svc.url" :href="svc.url" target="_blank"
                                    class="flex items-center gap-1 rounded-lg border border-teal-500/30 bg-teal-500/10 px-2.5 py-1 text-[10px] font-bold text-teal-400 hover:bg-teal-500/20 transition">
                                    Buka →
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB: Quick Access -->
            <div v-if="activeTab === 'services'" class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <template v-for="(dev, key) in devices" :key="key">
                    <a v-for="svc in dev.services.filter(s => s.url)" :key="svc.port"
                        :href="svc.url" target="_blank"
                        class="group flex items-center gap-4 rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-4 transition-all hover:border-teal-500/40 hover:bg-teal-500/5 hover:-translate-y-0.5 shadow-[0_4px_16px_-8px_rgba(0,0,0,0.4)]">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500/30 to-cyan-500/30 border border-teal-500/20 flex-shrink-0">
                            <svg class="h-5 w-5 text-teal-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[13px] font-bold text-[var(--text-1)] truncate">{{ svc.name }}</p>
                            <p class="text-[11px] text-[var(--text-3)] truncate">{{ dev.ip }}:{{ svc.port }}</p>
                            <p class="text-[10px] font-mono text-teal-400/70 truncate">{{ svc.url }}</p>
                        </div>
                    </a>
                </template>
            </div>

            <!-- TAB: Tailscale CLI -->
            <div v-if="activeTab === 'tailscale'" class="space-y-4">
                <!-- Status badge -->
                <div :class="['flex items-center gap-3 rounded-2xl border p-4', tsOnline ? 'border-emerald-500/30 bg-emerald-500/10' : 'border-red-500/30 bg-red-500/10']">
                    <div :class="['h-3 w-3 rounded-full', tsOnline ? 'bg-emerald-400 animate-pulse' : 'bg-red-500']"></div>
                    <div>
                        <p :class="['text-[13px] font-bold', tsOnline ? 'text-emerald-400' : 'text-red-400']">
                            Tailscale {{ tsOnline ? 'Aktif' : 'Tidak Terdeteksi' }}
                        </p>
                        <p class="text-[11px] text-[var(--text-3)]">
                            {{ tsOnline ? 'Daemon berjalan dan terkoneksi ke Tailnet.' : 'Tailscale tidak terinstall atau tidak berjalan di server ini.' }}
                        </p>
                    </div>
                    <button @click="refreshCli" class="ml-auto text-[10px] font-bold text-[var(--text-3)] hover:text-[var(--text-1)] transition">Refresh</button>
                </div>

                <!-- Self node info -->
                <div v-if="tsSelf" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 space-y-3">
                    <h3 class="text-[11px] font-black uppercase tracking-widest text-[var(--text-3)]">🖥 Node Ini</h3>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <div v-for="field in [
                            { label: 'Hostname', value: tsSelf.hostname },
                            { label: 'OS', value: tsSelf.os },
                            { label: 'Relay', value: tsSelf.relay },
                        ]" :key="field.label" class="rounded-xl bg-[var(--surface-0)] px-3 py-2.5">
                            <p class="text-[9px] font-bold text-[var(--text-3)] uppercase tracking-wider">{{ field.label }}</p>
                            <p class="text-[12px] font-semibold text-[var(--text-1)] mt-0.5">{{ field.value || '-' }}</p>
                        </div>
                    </div>
                    <div v-if="tsSelf.tailscale_ips?.length" class="rounded-xl bg-[var(--surface-0)] px-3 py-2.5">
                        <p class="text-[9px] font-bold text-[var(--text-3)] uppercase tracking-wider mb-1">Tailscale IPs</p>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="ip in tsSelf.tailscale_ips" :key="ip"
                                class="font-mono text-[11px] text-teal-400 bg-teal-500/10 border border-teal-500/20 rounded-lg px-2 py-0.5">{{ ip }}</span>
                        </div>
                    </div>
                </div>

                <!-- Peers -->
                <div v-if="tsPeers.length" class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 space-y-3">
                    <h3 class="text-[11px] font-black uppercase tracking-widest text-[var(--text-3)]">🌐 Peers ({{ tsPeers.length }})</h3>
                    <div v-for="peer in tsPeers" :key="peer.dns_name"
                        class="flex items-center justify-between rounded-xl bg-[var(--surface-0)] px-3 py-2.5">
                        <div>
                            <p class="text-[12px] font-bold text-[var(--text-1)]">{{ peer.hostname }}</p>
                            <p class="text-[10px] text-[var(--text-3)]">{{ peer.dns_name }} • {{ peer.os }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span v-for="ip in peer.tailscale_ips" :key="ip" class="font-mono text-[10px] text-teal-400">{{ ip }}</span>
                            <span :class="['text-[10px] font-black uppercase', peer.online ? 'text-emerald-400' : 'text-slate-500']">
                                {{ peer.online ? 'Online' : 'Offline' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Command reference -->
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-5 space-y-3">
                    <h3 class="text-[11px] font-black uppercase tracking-widest text-[var(--text-3)]">⌨️ Perintah Tailscale</h3>
                    <div v-for="cmd in [
                        { label: 'Status',           cmd: 'tailscale status' },
                        { label: 'Ping node',         cmd: 'tailscale ping <hostname>' },
                        { label: 'Advertise subnet', cmd: tailscaleUp },
                        { label: 'IP lokal',          cmd: 'tailscale ip -4' },
                        { label: 'Logout',            cmd: 'tailscale logout' },
                    ]" :key="cmd.cmd"
                        class="flex items-center justify-between gap-3 rounded-xl bg-[var(--surface-0)] px-3 py-2.5">
                        <div>
                            <p class="text-[10px] text-[var(--text-3)]">{{ cmd.label }}</p>
                            <code class="text-[11px] font-mono text-teal-400">{{ cmd.cmd }}</code>
                        </div>
                        <button @click="copyToClipboard(cmd.cmd)"
                            class="text-[10px] font-bold text-[var(--text-3)] hover:text-teal-400 transition px-2 py-1 rounded-lg hover:bg-teal-500/10">
                            Copy
                        </button>
                    </div>
                </div>
            </div>

            <!-- TAB: Subnet & Routes -->
            <div v-if="activeTab === 'subnet'" class="space-y-4">
                <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-1)] p-6 space-y-4">
                    <h3 class="text-[11px] font-black uppercase tracking-widest text-[var(--text-3)]">📡 Advertised Subnet Routes</h3>
                    <div class="rounded-xl border border-teal-500/30 bg-teal-500/10 p-4">
                        <p class="text-[10px] text-teal-300/70 uppercase font-bold tracking-widest mb-1">Subnet yang di-advertise</p>
                        <p class="text-2xl font-black font-mono text-teal-400">{{ subnet }}</p>
                        <p class="text-[11px] text-[var(--text-3)] mt-1">Seluruh jaringan internal PA Semarang dapat dijangkau via Tailscale melalui subnet ini.</p>
                    </div>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div v-for="(dev, key) in devices" :key="key"
                            class="rounded-xl bg-[var(--surface-0)] p-4 border border-[var(--border)]">
                            <p class="text-[11px] font-bold text-[var(--text-1)]">{{ dev.label }}</p>
                            <p class="text-[12px] font-mono text-teal-400 mt-1">{{ dev.ip }}</p>
                            <p class="text-[10px] text-[var(--text-3)] mt-0.5">{{ dev.description }}</p>
                            <div class="flex flex-wrap gap-1 mt-2">
                                <span v-for="svc in dev.services" :key="svc.port"
                                    :class="['text-[9px] font-mono font-bold px-1.5 py-0.5 rounded border', protocolColor(svc.protocol), 'border-current/30 bg-current/10']">
                                    :{{ svc.port }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </LawangsewuLayout>
</template>
