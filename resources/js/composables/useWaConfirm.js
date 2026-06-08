/**
 * useWaConfirm — Composable Confirm Dialog untuk WaCaraka
 *
 * Menggantikan inline confirmModal state di Index.vue.
 * Menggunakan Promise-based API yang sama persis dengan
 * window.confirm() tapi dengan UI yang custom.
 *
 * Usage:
 *   const { confirmModal, openConfirmModal, resolveConfirmModal } = useWaConfirm();
 *
 *   // Memanggil:
 *   const ok = await openConfirmModal('Judul', 'Pesan konfirmasi...');
 *   if (!ok) return;
 *
 *   // Di template: bind ke komponen modal konfirmasi
 *   <AppConfirmModal v-bind="confirmModal" @resolve="resolveConfirmModal" />
 */
import { ref } from 'vue';

export function useWaConfirm() {
    const confirmModal = ref({
        isOpen:  false,
        title:   '',
        message: '',
        resolve: null,
    });

    /**
     * Buka dialog konfirmasi. Returns Promise<boolean>.
     * @param {string} title
     * @param {string} message
     * @returns {Promise<boolean>}
     */
    const openConfirmModal = (title, message) => {
        return new Promise((resolve) => {
            confirmModal.value = { isOpen: true, title, message, resolve };
        });
    };

    /**
     * Resolve dialog dengan nilai true (OK) atau false (Cancel).
     * @param {boolean} val
     */
    const resolveConfirmModal = (val) => {
        if (confirmModal.value.resolve) confirmModal.value.resolve(val);
        confirmModal.value.isOpen = false;
    };

    return {
        confirmModal,
        openConfirmModal,
        resolveConfirmModal,
    };
}
