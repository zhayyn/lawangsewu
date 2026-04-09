<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
    cameras: { type: Array, default: () => [] },
    status: { type: String, default: null },
});

const createForm = useForm({
    name: '',
    zone: '',
    iframe_src: '',
    sort_order: 0,
    is_active: true,
    is_featured: false,
});

const cameraForms = reactive(
    props.cameras.reduce((acc, camera) => {
        acc[camera.id] = {
            name: camera.name,
            zone: camera.zone ?? '',
            iframe_src: camera.iframe_src,
            sort_order: camera.sort_order ?? 0,
            is_active: Boolean(camera.is_active),
            is_featured: Boolean(camera.is_featured),
        };

        return acc;
    }, {}),
);

const submitCreate = () => {
    createForm.post(route('admin.cctv.store'), {
        onSuccess: () => {
            createForm.reset();
            createForm.is_active = true;
            createForm.is_featured = false;
            createForm.sort_order = 0;
        },
        preserveScroll: true,
    });
};

const saveCamera = (cameraId) => {
    router.patch(route('admin.cctv.update', cameraId), cameraForms[cameraId], {
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <Head title="Kelola CCTV" />

    <LawangsewuLayout
        current-route="admin-cctv"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <div class="space-y-6">
            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-2xl shadow-black/10">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="space-y-3">
                        <span class="inline-flex rounded-full border border-cyan-500/20 bg-cyan-500/10 px-4 py-2 text-[11px] font-black uppercase tracking-[0.22em] text-cyan-400">
                            Superadmin Control
                        </span>
                        <div>
                            <h1 class="text-2xl font-black tracking-tight text-[var(--text-1)]">
                                Kelola Tampilan CCTV
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm text-[var(--text-3)]">
                                Ubah nama kamera, alamat URL sumber, zona, urutan tampil, dan status feed tanpa menyentuh database manual.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[var(--text-3)]">Total Kamera</p>
                            <p class="mt-2 text-xl font-black text-[var(--text-1)]">{{ cameras.length }}</p>
                        </div>
                        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[var(--text-3)]">Feed Aktif</p>
                            <p class="mt-2 text-xl font-black text-emerald-400">{{ cameras.filter((camera) => camera.is_active).length }}</p>
                        </div>
                        <div class="rounded-2xl border border-[var(--border)] bg-[var(--surface-2)] px-4 py-3">
                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-[var(--text-3)]">Unggulan Dashboard</p>
                            <p class="mt-2 text-xl font-black text-cyan-400">{{ cameras.filter((camera) => camera.is_featured).length }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <div
                v-if="status"
                class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm font-bold text-emerald-400"
            >
                {{ status }}
            </div>

            <section class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-2xl shadow-black/10">
                <div class="mb-6">
                    <h2 class="text-lg font-black uppercase tracking-[0.18em] text-[var(--text-1)]">
                        Tambah Kamera Baru
                    </h2>
                    <p class="mt-2 text-sm text-[var(--text-3)]">
                        Kamera baru otomatis mendapat `key` internal dari nama kamera.
                    </p>
                </div>

                <form class="grid gap-4 lg:grid-cols-6" @submit.prevent="submitCreate">
                    <div class="space-y-2 lg:col-span-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Nama Kamera</label>
                        <input v-model="createForm.name" type="text" class="input-surface w-full" placeholder="Contoh: Lobby Utama" required>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Zona</label>
                        <input v-model="createForm.zone" type="text" class="input-surface w-full" placeholder="Pelayanan">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Urutan</label>
                        <input v-model.number="createForm.sort_order" type="number" min="0" class="input-surface w-full">
                    </div>
                    <div class="flex items-center gap-4 pt-7">
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--text-2)]">
                            <input v-model="createForm.is_active" type="checkbox" class="rounded border-[var(--border)] bg-[var(--surface-2)]">
                            Aktif
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--text-2)]">
                            <input v-model="createForm.is_featured" type="checkbox" class="rounded border-[var(--border)] bg-[var(--surface-2)]">
                            Unggulan
                        </label>
                    </div>
                    <div class="space-y-2 lg:col-span-5">
                        <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">URL Sumber CCTV / Embed</label>
                        <input v-model="createForm.iframe_src" type="url" class="input-surface w-full" placeholder="https://..." required>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="github-button !w-full !bg-cyan-600 hover:!bg-cyan-700" :disabled="createForm.processing">
                            Tambahkan Kamera
                        </button>
                    </div>
                </form>

                <div v-if="Object.keys(createForm.errors).length" class="mt-4 space-y-1">
                    <p v-for="(error, key) in createForm.errors" :key="key" class="text-xs font-bold text-rose-400">
                        {{ error }}
                    </p>
                </div>
            </section>

            <section class="space-y-4">
                <div
                    v-for="camera in cameras"
                    :key="camera.id"
                    class="rounded-[2rem] border border-[var(--border)] bg-[var(--surface-1)] p-6 shadow-xl shadow-black/10"
                >
                    <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-lg font-black text-[var(--text-1)]">{{ camera.name }}</h2>
                                <span class="rounded-full border border-[var(--border)] bg-[var(--surface-2)] px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">
                                    {{ camera.key }}
                                </span>
                                <span
                                    class="rounded-full px-3 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                    :class="cameraForms[camera.id].is_active ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-400' : 'border border-rose-500/20 bg-rose-500/10 text-rose-400'"
                                >
                                    {{ cameraForms[camera.id].is_active ? 'Live' : 'Nonaktif' }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-[var(--text-3)]">
                                Terakhir diperbarui: {{ camera.updated_at || '-' }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="github-button !bg-blue-600 hover:!bg-blue-700"
                            @click="saveCamera(camera.id)"
                        >
                            Simpan Perubahan
                        </button>
                    </div>

                    <div class="grid gap-4 lg:grid-cols-6">
                        <div class="space-y-2 lg:col-span-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Nama Kamera</label>
                            <input v-model="cameraForms[camera.id].name" type="text" class="input-surface w-full" required>
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Zona</label>
                            <input v-model="cameraForms[camera.id].zone" type="text" class="input-surface w-full">
                        </div>
                        <div class="space-y-2">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">Urutan</label>
                            <input v-model.number="cameraForms[camera.id].sort_order" type="number" min="0" class="input-surface w-full">
                        </div>
                        <div class="flex items-center gap-4 pt-7 lg:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--text-2)]">
                                <input v-model="cameraForms[camera.id].is_active" type="checkbox" class="rounded border-[var(--border)] bg-[var(--surface-2)]">
                                Aktif
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--text-2)]">
                                <input v-model="cameraForms[camera.id].is_featured" type="checkbox" class="rounded border-[var(--border)] bg-[var(--surface-2)]">
                                Tampil di dashboard
                            </label>
                        </div>
                        <div class="space-y-2 lg:col-span-6">
                            <label class="text-[10px] font-black uppercase tracking-[0.18em] text-[var(--text-3)]">URL Sumber CCTV / Embed</label>
                            <input v-model="cameraForms[camera.id].iframe_src" type="url" class="input-surface w-full" required>
                        </div>
                    </div>
                </div>

                <div
                    v-if="cameras.length === 0"
                    class="rounded-[2rem] border border-dashed border-[var(--border)] bg-[var(--surface-1)] px-6 py-10 text-center text-sm font-semibold text-[var(--text-3)]"
                >
                    Belum ada kamera yang terdaftar. Tambahkan kamera pertama dari panel di atas.
                </div>
            </section>
        </div>
    </LawangsewuLayout>
</template>
