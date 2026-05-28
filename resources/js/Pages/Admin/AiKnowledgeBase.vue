<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    knowledges: Array,
    trainingPairs: {
        type: Array,
        default: () => []
    }
});

const isModalOpen = ref(false);
const editingId = ref(null);
const activeTab = ref('manual'); // 'manual' atau 'auto'

const form = useForm({
    title: '',
    keywords: '',
    content: '',
    is_active: true
});

const openModal = (kb = null) => {
    if (kb) {
        editingId.value = kb.id;
        form.title = kb.title;
        form.keywords = kb.keywords || '';
        form.content = kb.content;
        form.is_active = kb.is_active;
    } else {
        editingId.value = null;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
};

const save = () => {
    if (editingId.value) {
        form.patch(route('admin.ai-knowledge.update', editingId.value), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('admin.ai-knowledge.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const destroy = (id) => {
    if (confirm('Yakin ingin menghapus dokumen ini?')) {
        useForm({}).delete(route('admin.ai-knowledge.destroy', id));
    }
};

const teachAi = (pair) => {
    // Membuka modal dengan isi dari chat CS manusia
    editingId.value = null;
    form.reset();
    form.title = 'Hasil Belajar dari CS: ' + pair.user_prompt.substring(0, 30) + '...';
    // Gunakan beberapa kata dari pertanyaan sebagai keyword
    form.keywords = pair.user_prompt.split(' ').slice(0, 4).join(', ');
    form.content = "Pertanyaan warga: " + pair.user_prompt + "\n\nJawaban CS: " + pair.human_response;
    form.is_active = true;
    isModalOpen.value = true;
};
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                AI Knowledge Base (Pusat Pengetahuan Pandanaran)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
                    
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 mb-6">
                        <nav class="-mb-px flex space-x-8">
                            <button @click="activeTab = 'manual'" :class="[activeTab === 'manual' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap pb-4 border-b-2 font-medium text-sm']">
                                RAG Manual (Aturan Dasar)
                            </button>
                            <button @click="activeTab = 'auto'" :class="[activeTab === 'auto' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700', 'whitespace-nowrap pb-4 border-b-2 font-medium text-sm']">
                                Continuous Learning (Riwayat Chat CS)
                            </button>
                        </nav>
                    </div>

                    <!-- Tab: Manual RAG -->
                    <div v-show="activeTab === 'manual'">
                        <div class="flex justify-between items-center mb-6">
                            <p class="text-gray-600">
                                Masukkan informasi, syarat, dan aturan yang akan dijadikan 'contekan' utama oleh Ollama AI.
                            </p>
                            <PrimaryButton @click="openModal()">
                                + Tambah Knowledge
                            </PrimaryButton>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Judul</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kata Kunci</th>
                                        <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="kb in knowledges" :key="kb.id">
                                        <td class="px-6 py-4 text-sm text-gray-900">{{ kb.title }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ kb.keywords }}</td>
                                        <td class="px-6 py-4 text-sm">
                                            <span v-if="kb.is_active" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>
                                            <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="openModal(kb)" class="text-indigo-600 hover:text-indigo-900 mr-3">Edit</button>
                                            <button @click="destroy(kb.id)" class="text-red-600 hover:text-red-900">Hapus</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="knowledges.length === 0" class="text-center py-10 text-gray-500">
                                Belum ada Knowledge Base. Silakan tambahkan.
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Auto Learning -->
                    <div v-show="activeTab === 'auto'">
                        <div class="mb-6">
                            <p class="text-gray-600">
                                Data di bawah ini diekstrak otomatis dari percakapan nyata antara warga dan CS Manusia (Omnichannel). Anda bisa mengajarkan jawaban ini ke AI dengan menekan "Ajarkan ke AI".
                            </p>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pertanyaan Warga</th>
                                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggapan CS Manusia</th>
                                        <th class="px-4 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 bg-gray-50 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <tr v-for="pair in trainingPairs" :key="pair.id">
                                        <td class="px-4 py-4 text-sm text-gray-900 max-w-xs truncate" :title="pair.user_prompt">{{ pair.user_prompt }}</td>
                                        <td class="px-4 py-4 text-sm text-gray-500 max-w-xs truncate" :title="pair.human_response">{{ pair.human_response }}</td>
                                        <td class="px-4 py-4 text-sm">
                                            <span v-if="pair.status === 'learned'" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Telah Dipelajari</span>
                                            <span v-else class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button @click="teachAi(pair)" class="bg-indigo-600 text-white px-3 py-1 rounded text-xs hover:bg-indigo-700">Ajarkan ke AI</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <div v-if="trainingPairs.length === 0" class="text-center py-10 text-gray-500">
                                Belum ada riwayat percakapan CS yang terekam.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <Modal :show="isModalOpen" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    {{ editingId ? 'Edit Knowledge' : 'Tambah Knowledge Baru' }}
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Judul Informasi</label>
                        <input v-model="form.title" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: Syarat Cerai Gugat">
                        <div v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Kata Kunci (Pemisah Koma)</label>
                        <input v-model="form.keywords" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Misal: cerai, syarat cerai, istri gugat suami">
                        <p class="text-xs text-gray-500 mt-1">Sistem akan mencocokkan chat warga dengan kata kunci ini.</p>
                        <div v-if="form.errors.keywords" class="text-red-500 text-sm mt-1">{{ form.errors.keywords }}</div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Isi Informasi (Contekan untuk AI)</label>
                        <textarea v-model="form.content" rows="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Ketik informasi detail yang harus dijawab AI di sini..."></textarea>
                        <div v-if="form.errors.content" class="text-red-500 text-sm mt-1">{{ form.errors.content }}</div>
                    </div>

                    <div class="flex items-center mt-4">
                        <input v-model="form.is_active" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label class="ml-2 block text-sm text-gray-900">Aktifkan dokumen ini</label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button @click="closeModal" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 mr-3">
                        Batal
                    </button>
                    <PrimaryButton @click="save" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Simpan
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
