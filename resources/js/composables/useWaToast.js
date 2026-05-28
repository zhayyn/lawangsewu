/**
 * useWaToast — Composable Toast Notification untuk WaCaraka
 *
 * Menggantikan inline toast state yang sebelumnya ada langsung di Index.vue.
 * Dapat diimpor oleh komponen WaCaraka mana pun yang butuh notifikasi.
 *
 * Usage:
 *   const { toast, showToast, dismissToast, toastIcons } = useWaToast();
 */
import { ref } from 'vue';

export function useWaToast() {
    const toast = ref({
        isOpen:  false,
        type:    'error',   // 'success' | 'error' | 'warning' | 'info'
        title:   '',
        message: '',
        timer:   null,
    });

    const toastIcons = {
        success: '✦',
        error:   '✕',
        warning: '⚠',
        info:    'ℹ',
    };

    const toastTitles = {
        success: 'Berhasil',
        error:   'Gagal',
        warning: 'Perhatian',
        info:    'Info',
    };

    const dismissToast = () => {
        if (toast.value.timer) clearTimeout(toast.value.timer);
        toast.value.isOpen = false;
    };

    /**
     * @param {'success'|'error'|'warning'|'info'} type
     * @param {string} message
     * @param {string|null} title  — opsional, default dari toastTitles
     * @param {number} duration    — ms, default 4500
     */
    const showToast = (type, message, title = null, duration = 4500) => {
        if (toast.value.timer) clearTimeout(toast.value.timer);
        toast.value = {
            isOpen:  true,
            type,
            title:   title || toastTitles[type] || 'Notifikasi',
            message,
            timer:   setTimeout(() => dismissToast(), duration),
        };
    };

    return {
        toast,
        toastIcons,
        toastTitles,
        showToast,
        dismissToast,
    };
}
