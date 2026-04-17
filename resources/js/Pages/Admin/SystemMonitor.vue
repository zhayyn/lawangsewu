<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    snapshot: { type: Object, required: true },
});

const healthToneClass = (grade) => {
    if (grade === 'Sehat') return 'text-emerald-300 border-emerald-500/20 bg-emerald-500/10';
    if (grade === 'Waspada') return 'text-amber-300 border-amber-500/20 bg-amber-500/10';
    return 'text-rose-300 border-rose-500/20 bg-rose-500/10';
};

const backupToneClass = (status) => {
    if (status === 'fresh') return 'text-emerald-300 border-emerald-500/20 bg-emerald-500/10';
    if (status === 'stale') return 'text-amber-300 border-amber-500/20 bg-amber-500/10';
    return 'text-rose-300 border-rose-500/20 bg-rose-500/10';
};
</script>

<template>
    <Head title="Monitor Sistem" />

    <LawangsewuLayout
        current-route="admin-system-monitor"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-2xl shadow-black/10">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex rounded-full border border-sky-500/20 bg-sky-500/10 px-4 py-2 text-[11px] font-black uppercase tracking-[0.22em] text-sky-300">
                            Superadmin Monitor
                        </span>
                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-[var(--text-1)]">Monitor Sistem Lawangsewu</h1>
                            <p class="mt-2 max-w-3xl text-sm text-[var(--text-3)]">
                                Monitoring mesin, backup, performa aplikasi, dan indikator kesehatan sistem real-time ringan.
                            </p>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3 text-right">
                        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--text-3)]">Captured At</p>
                        <p class="mt-1 text-sm font-bold text-[var(--text-1)]">{{ snapshot.captured_at }}</p>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-[1.5rem] border border-cyan-500/20 bg-cyan-500/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-cyan-300">CPU Usage</p>
                    <p class="mt-3 text-3xl font-black text-cyan-200">{{ snapshot.cpu.usage_pct ?? '-' }}<span class="text-sm"> %</span></p>
                </div>
                <div class="rounded-[1.5rem] border border-amber-500/20 bg-amber-500/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-amber-300">CPU Temp</p>
                    <p class="mt-3 text-3xl font-black text-amber-200">{{ snapshot.cpu.temperature_c ?? '-' }}<span class="text-sm"> C</span></p>
                </div>
                <div class="rounded-[1.5rem] border border-fuchsia-500/20 bg-fuchsia-500/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-fuchsia-300">RAM Used</p>
                    <p class="mt-3 text-3xl font-black text-fuchsia-200">{{ snapshot.memory.used_pct ?? '-' }}<span class="text-sm"> %</span></p>
                </div>
                <div class="rounded-[1.5rem] border border-indigo-500/20 bg-indigo-500/10 p-5">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-indigo-300">Disk Used</p>
                    <p class="mt-3 text-3xl font-black text-indigo-200">{{ snapshot.disk.used_pct ?? '-' }}<span class="text-sm"> %</span></p>
                </div>
                <div class="rounded-[1.5rem] border p-5" :class="healthToneClass(snapshot.health.grade)">
                    <p class="text-[10px] font-black uppercase tracking-[0.2em]">System Health</p>
                    <p class="mt-3 text-3xl font-black">{{ snapshot.health.score_pct }}<span class="text-sm"> %</span></p>
                    <p class="mt-1 text-xs font-bold uppercase tracking-[0.15em]">{{ snapshot.health.grade }}</p>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                    <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Status Backup</h2>
                    <div class="mt-5 grid gap-3">
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Status</span>
                            <span class="rounded-full px-3 py-1 text-xs font-black uppercase tracking-[0.14em] border" :class="backupToneClass(snapshot.backup.status)">
                                {{ snapshot.backup.status }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Kondisi Backup</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.backup.condition_pct }}%</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Health Backup</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.backup.health }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Backup Terakhir</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.backup.last_backup_at || '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Umur Backup</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.backup.age_hours ?? '-' }} jam</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Ukuran File</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.backup.size_mb ?? '-' }} MB</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Nama File</span>
                            <span class="max-w-[60%] truncate font-black text-[var(--text-1)]">{{ snapshot.backup.latest_file || '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                    <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Kondisi Mesin</h2>
                    <div class="mt-5 grid gap-3">
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Hostname</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.host.hostname }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">OS / Kernel</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.host.os }} / {{ snapshot.host.kernel }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Uptime</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.host.uptime }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Load Average</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.cpu.load_1 }} / {{ snapshot.cpu.load_5 }} / {{ snapshot.cpu.load_15 }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">RAM (Used/Total)</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.memory.used_mb }} / {{ snapshot.memory.total_mb }} MB</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Disk (Used/Total)</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.disk.used_gb }} / {{ snapshot.disk.total_gb }} GB</span>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1fr_1fr]">
                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                    <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Aplikasi Lawangsewu</h2>
                    <div class="mt-5 grid gap-3">
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">CCTV Aktif</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.application.cctv_active ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">WA Hari Ini</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.application.wa_today ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Queue Jobs</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.application.jobs_queue_rows ?? '-' }}</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Failed Jobs</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.application.failed_jobs_rows ?? '-' }}</span>
                        </div>
                    </div>
                </div>

                <div class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                    <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">Pendopo Reliability</h2>
                    <div class="mt-5 grid gap-3">
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">P50 Load</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.pendopo_metrics.p50_ms ?? '-' }} ms</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">P95 Load</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.pendopo_metrics.p95_ms ?? '-' }} ms</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Timeout Rate</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.pendopo_metrics.timeout_rate_pct ?? '-' }}%</span>
                        </div>
                        <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <span class="text-[var(--text-3)]">Total Events</span>
                            <span class="font-black text-[var(--text-1)]">{{ snapshot.pendopo_metrics.total_events ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- WA Caraka DB Health -->
            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">WA Caraka · Database Health</h2>
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-[10px] font-black uppercase tracking-[0.15em]"
                        :class="snapshot.wa_caraka?.db_bloat_warning
                            ? 'border-amber-500/20 bg-amber-500/10 text-amber-300'
                            : 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300'"
                    >
                        <span class="w-1.5 h-1.5 rounded-full"
                              :class="snapshot.wa_caraka?.db_bloat_warning ? 'bg-amber-400' : 'bg-emerald-400'"></span>
                        {{ snapshot.wa_caraka?.db_bloat_warning ? 'Perlu Perhatian' : 'Normal' }}
                    </span>
                </div>
                <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-3">
                    <!-- Row: Statistik Database -->
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Total Pesan (DB)</span>
                        <span class="font-black text-[var(--text-1)]">{{ (snapshot.wa_caraka?.total_messages ?? 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Percakapan Aktif</span>
                        <span class="font-black text-[var(--text-1)]">{{ (snapshot.wa_caraka?.total_conversations ?? 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Pesan Hari Ini</span>
                        <span class="font-black text-[var(--text-1)]">{{ snapshot.wa_caraka?.today_messages ?? '-' }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Log (Legacy)</span>
                        <span class="font-black text-[var(--text-1)]">{{ (snapshot.wa_caraka?.total_logs ?? 0).toLocaleString('id-ID') }}</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Belum Dibalas</span>
                        <span class="font-black"
                              :class="(snapshot.wa_caraka?.unreplied ?? 0) > 20 ? 'text-amber-400' : 'text-[var(--text-1)]'">
                            {{ snapshot.wa_caraka?.unreplied ?? '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Estimasi Total Baris</span>
                        <span class="font-black text-[var(--text-1)]">{{ (snapshot.wa_caraka?.estimated_rows ?? 0).toLocaleString('id-ID') }}</span>
                    </div>

                    <!-- Row: Archive Stats -->
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Retensi Data</span>
                        <span class="font-black text-[var(--text-1)]">{{ snapshot.wa_caraka?.retention_days ?? 90 }} hari</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">File Arsip</span>
                        <span class="font-black text-[var(--text-1)]">{{ snapshot.wa_caraka?.archive_files ?? 0 }} file</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Ukuran Arsip</span>
                        <span class="font-black text-[var(--text-1)]">{{ snapshot.wa_caraka?.archive_size_mb ?? 0 }} MB</span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Arsip Terakhir</span>
                        <span class="font-black text-[var(--text-1)]">
                            {{ snapshot.wa_caraka?.last_archive?.archived_at ?? 'Belum pernah' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Pesan Diarsipkan</span>
                        <span class="font-black text-[var(--text-1)]">
                            {{ snapshot.wa_caraka?.last_archive ? (snapshot.wa_caraka.last_archive.messages_archived + snapshot.wa_caraka.last_archive.logs_archived) : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                        <span class="text-[var(--text-3)]">Jadwal Arsip Berikutnya</span>
                        <span class="font-black text-[var(--text-1)]">{{ snapshot.wa_caraka?.next_archive_at ?? '-' }}</span>
                    </div>
                </div>
            </section>

            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10">
                <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">System Alerts</h2>
                <div class="mt-4 grid gap-3">
                    <div
                        v-for="(alert, index) in snapshot.alerts"
                        :key="index"
                        class="rounded-2xl border px-4 py-3 text-sm font-bold"
                        :class="{
                            'border-emerald-500/20 bg-emerald-500/10 text-emerald-300': alert.level === 'ok',
                            'border-amber-500/20 bg-amber-500/10 text-amber-300': alert.level === 'warning',
                            'border-rose-500/20 bg-rose-500/10 text-rose-300': alert.level === 'danger',
                        }"
                    >
                        {{ alert.message }}
                    </div>
                </div>
            </section>
        </div>
    </LawangsewuLayout>
</template>
