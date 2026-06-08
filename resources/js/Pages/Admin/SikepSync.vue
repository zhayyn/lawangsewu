<script setup>
import LawangsewuLayout from '@/Layouts/LawangsewuLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    lastSynced: String,
    totalEmployees: Number,
    appMeta: { type: Object, required: true },
    navGroups: { type: Array, default: () => [] },
});

const form = useForm({
    file: null,
});

const isDragging = ref(false);
const fileInput = ref(null);
const fileName = ref('');

const onDragOver = (e) => {
    e.preventDefault();
    isDragging.value = true;
};

const onDragLeave = (e) => {
    e.preventDefault();
    isDragging.value = false;
};

const onDrop = (e) => {
    e.preventDefault();
    isDragging.value = false;
    if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        handleFileSelect(e.dataTransfer.files[0]);
    }
};

const triggerFileInput = () => {
    fileInput.value.click();
};

const onFileChange = (e) => {
    if (e.target.files && e.target.files.length > 0) {
        handleFileSelect(e.target.files[0]);
    }
};

const handleFileSelect = (file) => {
    form.file = file;
    fileName.value = file.name;
};

const removeFile = () => {
    form.file = null;
    fileName.value = '';
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    if (!form.file) return;
    
    form.post(route('admin.sikep-sync.store'), {
        preserveScroll: true,
        onSuccess: () => {
            removeFile();
        },
    });
};
</script>

<template>
    <Head title="Sinkronisasi SIKEP" />

    <LawangsewuLayout
        current-route="sikep-sync.index"
        :nav-groups="navGroups"
        :app-meta="appMeta"
    >
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Sinkronisasi Jatidiri Pegawai
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                    <div v-if="$page.props.flash?.success" class="p-4 mb-4 text-sm text-green-800 rounded-lg bg-green-50 dark:bg-gray-800 dark:text-green-400 shadow-sm border border-green-200 dark:border-green-800 flex items-center" role="alert">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        {{ $page.props.flash.success }}
                    </div>
                </transition>

                <transition enter-active-class="transition ease-out duration-300" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-200" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                    <div v-if="$page.props.errors?.error || form.errors.file" class="p-4 mb-4 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400 shadow-sm border border-red-200 dark:border-red-800 flex items-center" role="alert">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                        {{ $page.props.errors?.error || form.errors.file }}
                    </div>
                </transition>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-2xl border border-gray-100 dark:border-gray-700">
                    <div class="p-8 sm:p-12">
                        <div class="text-center mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Upload Data Jatidiri</h2>
                            <p class="text-gray-500 dark:text-gray-400">
                                Perbarui data pegawai (bezetting) dengan mengunggah file export dari portal SIKEP.
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
                            <!-- Left Column: Upload Area -->
                            <div>
                                <form @submit.prevent="submit" class="space-y-6">
                                    <div 
                                        @dragover="onDragOver" 
                                        @dragleave="onDragLeave" 
                                        @drop="onDrop"
                                        @click="!form.file && triggerFileInput()"
                                        :class="[
                                            'relative border-2 border-dashed rounded-xl p-8 text-center transition-all duration-300 ease-in-out',
                                            !form.file ? 'cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20' : '',
                                            isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20 scale-105' : 'border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50',
                                            form.file ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : ''
                                        ]"
                                    >
                                        <input 
                                            type="file" 
                                            ref="fileInput" 
                                            class="hidden" 
                                            accept=".csv,.txt,.docx,.doc" 
                                            @change="onFileChange"
                                        >

                                        <div v-if="!form.file" class="space-y-4">
                                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 mb-2">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                <span class="font-semibold text-indigo-600 dark:text-indigo-400">Klik untuk unggah</span> atau drag and drop
                                            </div>
                                            <p class="text-xs text-gray-500 dark:text-gray-500">
                                                File didukung: CSV, DOCX (Maks 10MB)
                                            </p>
                                        </div>

                                        <div v-else class="space-y-4">
                                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 dark:bg-green-900/50 text-green-600 dark:text-green-400 mb-2">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </div>
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate px-4">
                                                {{ fileName }}
                                            </div>
                                            <button 
                                                type="button" 
                                                @click.stop="removeFile" 
                                                class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 font-medium transition-colors"
                                            >
                                                Hapus File
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex justify-center">
                                        <button 
                                            :disabled="!form.file || form.processing" 
                                            type="submit" 
                                            class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-indigo-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-300 disabled:opacity-50 disabled:cursor-not-allowed transform hover:-translate-y-0.5"
                                        >
                                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            <span v-if="form.processing">Memproses Data...</span>
                                            <span v-else>Mulai Sinkronisasi</span>
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Right Column: Info & Stats -->
                            <div class="space-y-6">
                                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6 border border-gray-100 dark:border-gray-700">
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Status Database
                                    </h3>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Pegawai</div>
                                            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ totalEmployees }}</div>
                                        </div>
                                        <div class="bg-white dark:bg-gray-800 p-4 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-1">Terakhir Update</div>
                                            <div class="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                {{ lastSynced ? new Date(lastSynced).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : 'Belum pernah' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6 border border-blue-100 dark:border-blue-800">
                                    <h4 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7i2 2M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        Panduan Format File
                                    </h4>
                                    <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-2 list-disc list-inside">
                                        <li><strong>DOCX:</strong> Unduh file Export Bezetting langsung dari Portal SIKEP.</li>
                                        <li><strong>CSV:</strong> Pastikan kolom mengandung `nip`, `nama`, `jabatan`, `golongan`, dll. Pemisah bisa berupa koma atau titik koma.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </LawangsewuLayout>
</template>
