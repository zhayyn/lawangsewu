<script setup>
/**
 * ConfirmDialog — Dialog konfirmasi elegan Lawangsewu.
 * Promise-based API, compatible dengan pattern openConfirmModal yang sudah ada.
 *
 * Cara pakai:
 *   const dialog = ref(null);
 *   const ok = await dialog.value.open({ title, message, danger: true });
 */
import { ref, shallowRef } from 'vue';

const visible  = ref(false);
const leaving  = ref(false);
const opts     = ref({ title: '', message: '', danger: false, confirmLabel: 'Ya, Lanjutkan', cancelLabel: 'Batal' });
const resolver = shallowRef(null);

let leaveTimer = null;

const close = (result) => {
    if (!visible.value) return;
    leaving.value = true;
    if (leaveTimer) clearTimeout(leaveTimer);
    leaveTimer = setTimeout(() => {
        visible.value = false;
        leaving.value = false;
        if (resolver.value) {
            resolver.value(result);
            resolver.value = null;
        }
    }, 250);
};

const open = (options = {}) => {
    opts.value = {
        title        : options.title        ?? 'Konfirmasi',
        message      : options.message      ?? 'Yakin ingin melanjutkan?',
        danger       : options.danger       ?? false,
        confirmLabel : options.confirmLabel ?? 'Ya, Lanjutkan',
        cancelLabel  : options.cancelLabel  ?? 'Batal',
    };
    visible.value = true;
    leaving.value = false;
    return new Promise((resolve) => { resolver.value = resolve; });
};

// Keyboard ESC untuk cancel
if (typeof window !== 'undefined') {
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && visible.value) close(false);
    });
}

defineExpose({ open });
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-opacity duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-250 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="visible"
                class="confirm-backdrop"
                role="dialog"
                aria-modal="true"
                :aria-labelledby="`confirm-title-${opts.title}`"
                @click.self="close(false)"
            >
                <!-- Card -->
                <div class="confirm-card" :class="{ 'confirm-card--leaving': leaving }">

                    <!-- Icon -->
                    <div class="confirm-icon" :class="opts.danger ? 'confirm-icon--danger' : 'confirm-icon--info'">
                        <!-- Danger: shield exclamation -->
                        <svg v-if="opts.danger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2L3 7v5c0 5.25 3.75 10.15 9 11.35C17.25 22.15 21 17.25 21 12V7L12 2z"/>
                            <line x1="12" y1="9" x2="12" y2="13"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                        <!-- Info: question circle -->
                        <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                            <line x1="12" y1="17" x2="12.01" y2="17"/>
                        </svg>
                    </div>

                    <!-- Text -->
                    <div class="confirm-body">
                        <h3 class="confirm-title" :id="`confirm-title-${opts.title}`">{{ opts.title }}</h3>
                        <p class="confirm-message">{{ opts.message }}</p>
                    </div>

                    <!-- Actions -->
                    <div class="confirm-actions">
                        <button
                            class="confirm-btn confirm-btn--cancel"
                            @click="close(false)"
                            autofocus
                        >
                            {{ opts.cancelLabel }}
                        </button>
                        <button
                            class="confirm-btn"
                            :class="opts.danger ? 'confirm-btn--danger' : 'confirm-btn--confirm'"
                            @click="close(true)"
                        >
                            {{ opts.confirmLabel }}
                        </button>
                    </div>

                    <!-- Bottom accent bar -->
                    <div class="confirm-bar" :class="opts.danger ? 'confirm-bar--danger' : 'confirm-bar--info'" />
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
/* ── Backdrop ── */
.confirm-backdrop {
    position: fixed;
    inset: 0;
    z-index: 99995;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.5rem;
    background: rgba(2, 6, 23, 0.55);
    backdrop-filter: blur(6px);
    -webkit-backdrop-filter: blur(6px);
}

/* ── Card ── */
.confirm-card {
    position: relative;
    overflow: hidden;
    width: min(26rem, calc(100vw - 2rem));
    background: linear-gradient(150deg, rgba(15, 23, 42, 0.96), rgba(2, 6, 23, 0.97));
    border: 1px solid rgba(148, 163, 184, 0.18);
    border-radius: 1.25rem;
    padding: 1.75rem 1.75rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    box-shadow:
        0 24px 64px rgba(0, 0, 0, 0.5),
        0 0 0 1px rgba(148, 163, 184, 0.08),
        inset 0 1px 0 rgba(255, 255, 255, 0.06);
    animation: card-in 0.28s cubic-bezier(0.2, 0.9, 0.25, 1) both;
}

.confirm-card--leaving {
    animation: card-out 0.22s cubic-bezier(0.4, 0, 0.8, 0.2) both;
}

@keyframes card-in {
    from { opacity: 0; transform: scale(0.88) translateY(24px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
@keyframes card-out {
    from { opacity: 1; transform: scale(1) translateY(0); }
    to   { opacity: 0; transform: scale(0.92) translateY(12px); }
}

/* ── Icon ── */
.confirm-icon {
    width: 3rem;
    height: 3rem;
    border-radius: 999px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.confirm-icon svg { width: 1.4rem; height: 1.4rem; }

.confirm-icon--danger {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.08);
}
.confirm-icon--info {
    background: rgba(96, 165, 250, 0.15);
    color: #60a5fa;
    box-shadow: 0 0 0 6px rgba(96, 165, 250, 0.08);
}

/* ── Text ── */
.confirm-body { text-align: center; }
.confirm-title {
    font-size: 1rem;
    font-weight: 800;
    color: #e2e8f0;
    letter-spacing: -0.01em;
    margin-bottom: 0.4rem;
}
.confirm-message {
    font-size: 0.82rem;
    color: rgba(148, 163, 184, 0.9);
    line-height: 1.55;
    white-space: pre-wrap;
}

/* ── Buttons ── */
.confirm-actions {
    display: flex;
    gap: 0.6rem;
    width: 100%;
    margin-top: 0.25rem;
}
.confirm-btn {
    flex: 1;
    padding: 0.6rem 1rem;
    border-radius: 0.75rem;
    font-size: 0.8rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
    outline: none;
    -webkit-tap-highlight-color: transparent;
}
.confirm-btn:active { transform: scale(0.97); }

.confirm-btn--cancel {
    background: rgba(51, 65, 85, 0.8);
    color: #94a3b8;
}
.confirm-btn--cancel:hover { background: rgba(71, 85, 105, 0.9); color: #cbd5e1; }

.confirm-btn--danger {
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: white;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.35);
}
.confirm-btn--danger:hover { box-shadow: 0 6px 18px rgba(239, 68, 68, 0.45); filter: brightness(1.08); }

.confirm-btn--confirm {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    box-shadow: 0 4px 14px rgba(59, 130, 246, 0.35);
}
.confirm-btn--confirm:hover { box-shadow: 0 6px 18px rgba(59, 130, 246, 0.45); filter: brightness(1.08); }

/* ── Bottom bar ── */
.confirm-bar {
    position: absolute;
    bottom: 0; left: 0;
    height: 3px;
    width: 100%;
}
.confirm-bar--danger { background: linear-gradient(90deg, #ef4444, #f87171); }
.confirm-bar--info   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
</style>
