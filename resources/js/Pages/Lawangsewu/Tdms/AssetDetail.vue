<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    appMeta:   { type: Object, default: () => ({}) },
    navGroups: { type: Array,  default: () => [] },
    asset:     { type: Object, default: () => ({}) },
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' }) : '—';

const statusMeta = {
    active: { label: 'Aktif', cls: 'bg-emerald-100 text-emerald-700 border-emerald-200' },
    maintenance: { label: 'Dalam Servis', cls: 'bg-amber-100 text-amber-700 border-amber-200' },
    broken: { label: 'Rusak', cls: 'bg-rose-100 text-rose-700 border-rose-200' },
    retired: { label: 'Pensiun', cls: 'bg-slate-100 text-slate-600 border-slate-200' },
};
const badgeCls = (status) => `inline-flex items-center rounded-full px-2.5 py-1 text-xs font-bold border ${statusMeta[status]?.cls || ''}`;

const specEntries = computed(() => Object.entries(props.asset?.specifications || {}));
</script>

<template>
    <LawangsewuLayout :app-meta="appMeta" :nav-groups="navGroups">
        <Head :title="`TDMS — ${asset.assetCode}`" />

        <div class="mx-auto max-w-3xl space-y-6 px-4 py-8 sm:px-6">

            <!-- Header -->
            <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-4xl">{{ asset.category?.icon || '📦' }}</span>
                            <span :class="badgeCls(asset.status)">{{ statusMeta[asset.status]?.label }}</span>
                        </div>
                        <h1 class="text-2xl font-black text-slate-900">{{ asset.name }}</h1>
                        <p class="mt-0.5 font-mono text-sm font-bold text-slate-500">{{ asset.assetCode }}</p>
                        <p class="mt-1 text-sm text-slate-500">{{ asset.category?.name }} · {{ [asset.brand, asset.model].filter(Boolean).join(' ') || '—' }}</p>
                    </div>
                    <!-- QR placeholder -->
                    <div class="flex flex-col items-center gap-1 rounded-2xl border border-dashed border-slate-300 p-4 text-center">
                        <div class="text-5xl">📱</div>
                        <p class="text-[11px] font-bold text-slate-500">Scan untuk<br/>detail aset</p>
                    </div>
                </div>
            </div>

            <!-- Detail Info -->
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Informasi Perangkat</h2>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between"><dt class="text-slate-500">Serial Number</dt><dd class="font-mono font-semibold text-slate-800">{{ asset.serialNumber || '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Lokasi</dt><dd class="font-semibold text-slate-800">{{ asset.location || '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Pemegang</dt><dd class="font-semibold text-slate-800">{{ asset.assignedTo || '—' }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Tgl Perolehan</dt><dd class="font-semibold text-slate-800">{{ formatDate(asset.purchaseDate) }}</dd></div>
                    </dl>
                </div>
                <div v-if="specEntries.length > 0" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="mb-3 text-xs font-bold uppercase tracking-wide text-slate-500">Spesifikasi</h2>
                    <dl class="space-y-2 text-sm">
                        <div v-for="[k, v] in specEntries" :key="k" class="flex justify-between gap-2">
                            <dt class="capitalize text-slate-500">{{ k }}</dt>
                            <dd class="font-semibold text-slate-800 text-right">{{ v }}</dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Catatan -->
            <div v-if="asset.notes" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-bold mb-1">📝 Catatan</p>
                <p class="whitespace-pre-wrap">{{ asset.notes }}</p>
            </div>
        </div>
    </LawangsewuLayout>
</template>
