<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    lastSynced: String,
    totalEmployees: Number,
});

const form = useForm({
    username: '',
    password: '',
});

const submit = () => {
    form.post(route('admin.sikep-sync.store'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Sinkronisasi SIKEP" />

    <LawangsewuLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sinkronisasi Pegawai SIKEP
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <div v-if="$page.props.flash.success" class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400" role="alert">
                  {{ $page.props.flash.success }}
                </div>

                <div v-if="$page.props.errors.error" class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400" role="alert">
                  {{ $page.props.errors.error }}
                </div>

                <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">Crawler Portal SIKEP</h2>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Masukkan username dan password SIKEP Anda. Sistem akan mengambil data bezetting terbaru dari SIKEP dan menyimpannya di database Lawangsewu.
                                Kredensial tidak akan disimpan.
                            </p>
                        </header>

                        <div class="mt-6 mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded border border-gray-200 dark:border-gray-600">
                            <p class="text-sm text-gray-700 dark:text-gray-300">
                                <strong>Total Pegawai Tersimpan:</strong> {{ totalEmployees }}<br>
                                <strong>Terakhir Sinkronisasi:</strong> {{ lastSynced ? new Date(lastSynced).toLocaleString('id-ID') : 'Belum pernah' }}
                            </p>
                        </div>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username SIKEP</label>
                                <input id="username" type="text" v-model="form.username" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300" />
                            </div>

                            <div>
                                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password SIKEP</label>
                                <input id="password" type="password" v-model="form.password" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300" />
                            </div>

                            <div class="flex items-center gap-4">
                                <button :disabled="form.processing" type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50">
                                    <span v-if="form.processing">Proses Sinkronisasi...</span>
                                    <span v-else>Mulai Sinkronisasi Data</span>
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </LawangsewuLayout>
</template>
