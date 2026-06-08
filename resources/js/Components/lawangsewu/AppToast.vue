<script setup>
/**
 * AppToast — Komponen notifikasi toast universal Lawangsewu.
 *
 * Gunakan via useToast() composable:
 *   const toast = useToast();
 *   toast.success('Berhasil!', 'Data tersimpan.');
 *   toast.error('Gagal!', 'Koneksi terputus.');
 *   toast.info('Info', 'Sedang memproses...');
 *   toast.warn('Perhatian', 'Sisa kuota hampir habis.');
 */
import { ref, computed, defineExpose } from 'vue';

// ─── Toast state ──────────────────────────────────────────────────────────────
const toasts = ref([]);
let nextId = 1;

const DURATION = {
    success : 4000,
    error   : 6000,
    warn    : 5000,
    info    : 4000,
};

const ICONS = {
    success: `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>`,
    error  : `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>`,
    warn   : `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>`,
    info   : `<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>`,
};

// ─── Internal helpers ─────────────────────────────────────────────────────────
const dismiss = (id) => {
    const toast = toasts.value.find(t => t.id === id);
    if (!toast || toast.leaving) return;
    toast.leaving = true;
    setTimeout(() => {
        toasts.value = toasts.value.filter(t => t.id !== id);
    }, 420);
};

const add = (type, title, message = '') => {
    const id = nextId++;
    const duration = DURATION[type] ?? 4000;

    toasts.value.push({ id, type, title, message, leaving: false, duration });

    setTimeout(() => dismiss(id), duration);
    return id;
};

// ─── Public API (exposed untuk useToast) ─────────────────────────────────────
const success = (title, message) => add('success', title, message);
const error   = (title, message) => add('error',   title, message);
const warn    = (title, message) => add('warn',    title, message);
const info    = (title, message) => add('info',    title, message);

defineExpose({ success, error, warn, info, dismiss });
</script>

<template>
    <!-- Portal ke pojok kanan atas, di atas segalanya -->
    <Teleport to="body">
        <div
            class="toast-stack"
            role="region"
            aria-label="Notifikasi"
            aria-live="polite"
        >
            <TransitionGroup
                tag="div"
                class="toast-stack__inner"
                enter-active-class="toast-enter-active"
                enter-from-class="toast-enter-from"
                enter-to-class="toast-enter-to"
                leave-active-class="toast-leave-active"
                leave-from-class="toast-leave-from"
                leave-to-class="toast-leave-to"
                move-class="toast-move"
            >
                <div
                    v-for="t in toasts"
                    :key="t.id"
                    class="toast"
                    :class="[`toast--${t.type}`, { 'toast--leaving': t.leaving }]"
                    role="alert"
                    @click="dismiss(t.id)"
                >
                    <!-- Icon -->
                    <span class="toast__icon" v-html="ICONS[t.type]" />

                    <!-- Content -->
                    <div class="toast__body">
                        <p class="toast__title">{{ t.title }}</p>
                        <p v-if="t.message" class="toast__msg">{{ t.message }}</p>
                    </div>

                    <!-- Close -->
                    <button class="toast__close" @click.stop="dismiss(t.id)" aria-label="Tutup">
                        <svg viewBox="0 0 20 20" fill="currentColor" class="w-3.5 h-3.5">
                            <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                        </svg>
                    </button>

                    <!-- Progress bar (depletes over duration) -->
                    <div
                        class="toast__bar"
                        :style="`animation-duration: ${t.duration}ms`"
                    />
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
/* ── Container ── */
.toast-stack {
    position: fixed;
    top: 1.25rem;
    right: 1.25rem;
    z-index: 99990;
    pointer-events: none;
    width: min(24rem, calc(100vw - 2rem));
}

.toast-stack__inner {
    display: flex;
    flex-direction: column;
    gap: 0.6rem;
}

/* ── Toast card ── */
.toast {
    pointer-events: auto;
    position: relative;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.85rem 1rem 1.1rem;
    border-radius: 1rem;
    cursor: pointer;
    overflow: hidden;
    box-shadow:
        0 4px 24px rgba(0,0,0,0.18),
        0 1px 4px rgba(0,0,0,0.12),
        inset 0 1px 0 rgba(255,255,255,0.12);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid transparent;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.toast:hover {
    transform: translateX(-3px);
}
.toast:active {
    transform: scale(0.98);
}

/* ── Variants ── */
.toast--success {
    background: linear-gradient(135deg, rgba(6,78,59,0.92), rgba(6,95,70,0.88));
    border-color: rgba(52,211,153,0.3);
    color: #d1fae5;
}
.toast--success .toast__icon { color: #34d399; }
.toast--success .toast__bar  { background: linear-gradient(90deg, #34d399, #6ee7b7); }

.toast--error {
    background: linear-gradient(135deg, rgba(127,29,29,0.92), rgba(153,27,27,0.88));
    border-color: rgba(252,165,165,0.3);
    color: #fee2e2;
}
.toast--error .toast__icon { color: #f87171; }
.toast--error .toast__bar  { background: linear-gradient(90deg, #f87171, #fca5a5); }

.toast--warn {
    background: linear-gradient(135deg, rgba(120,53,15,0.92), rgba(146,64,14,0.88));
    border-color: rgba(251,191,36,0.3);
    color: #fef3c7;
}
.toast--warn .toast__icon { color: #fbbf24; }
.toast--warn .toast__bar  { background: linear-gradient(90deg, #fbbf24, #fcd34d); }

.toast--info {
    background: linear-gradient(135deg, rgba(7,47,90,0.92), rgba(12,74,110,0.88));
    border-color: rgba(96,165,250,0.3);
    color: #dbeafe;
}
.toast--info .toast__icon { color: #60a5fa; }
.toast--info .toast__bar  { background: linear-gradient(90deg, #60a5fa, #93c5fd); }

/* ── Icon ── */
.toast__icon {
    flex-shrink: 0;
    width: 1.25rem;
    height: 1.25rem;
    margin-top: 0.05rem;
}
.toast__icon :deep(svg) {
    width: 100%;
    height: 100%;
}

/* ── Content ── */
.toast__body    { flex: 1; min-width: 0; }
.toast__title   { font-size: 0.8rem; font-weight: 700; line-height: 1.3; letter-spacing: -0.01em; }
.toast__msg     { font-size: 0.72rem; opacity: 0.8; margin-top: 0.15rem; line-height: 1.45; }

/* ── Close button ── */
.toast__close {
    flex-shrink: 0;
    opacity: 0.6;
    transition: opacity 0.15s;
    padding: 0.15rem;
    border-radius: 0.375rem;
    margin-top: -0.1rem;
}
.toast__close:hover { opacity: 1; }

/* ── Progress bar ── */
.toast__bar {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 3px;
    width: 100%;
    border-radius: 0 0 1rem 1rem;
    transform-origin: left;
    animation: bar-drain linear forwards;
}

@keyframes bar-drain {
    from { transform: scaleX(1); }
    to   { transform: scaleX(0); }
}

/* ── TransitionGroup animations ── */
.toast-enter-active  { transition: all 0.38s cubic-bezier(0.2, 0.9, 0.25, 1); }
.toast-enter-from    { opacity: 0; transform: translateX(110%) scale(0.9); }
.toast-enter-to      { opacity: 1; transform: translateX(0) scale(1); }

.toast-leave-active  { transition: all 0.38s cubic-bezier(0.4, 0, 0.2, 1); position: absolute; width: 100%; }
.toast-leave-from    { opacity: 1; transform: translateX(0) scale(1); }
.toast-leave-to      { opacity: 0; transform: translateX(110%) scale(0.9); }

.toast-move { transition: transform 0.3s ease; }
</style>
