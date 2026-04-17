<script setup>
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

const page = usePage();
const visible = ref(false);
const leaving = ref(false);
let leaveTimer = null;
let closeTimer = null;

const loginUserName = computed(() => page.props.login_success ?? null);

const closeToast = () => {
    if (leaving.value || !visible.value) return;
    leaving.value = true;
    
    if (leaveTimer) clearTimeout(leaveTimer);
    if (closeTimer) clearTimeout(closeTimer);
    
    setTimeout(() => {
        visible.value = false;
    }, 600);
};

onMounted(() => {
    if (!loginUserName.value) return;

    // Give the page a brief moment to paint before showing splash overlay.
    setTimeout(() => {
        visible.value = true;
    }, 90);

    // Keep splash visible for 10 seconds.
    leaveTimer = setTimeout(() => {
        leaving.value = true;
    }, 10000);

    closeTimer = setTimeout(() => {
        visible.value = false;
    }, 10600);
});
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-700 ease-[cubic-bezier(0.2,0.9,0.25,1)]"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-all duration-700 ease-[cubic-bezier(0.4,0,0.2,1)]"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="visible && loginUserName"
            class="login-splash"
            role="status"
            aria-live="polite"
            @click="closeToast"
            style="cursor: pointer;"
        >
            <div class="login-splash__backdrop" />

            <div class="login-card" :class="{ 'login-card--leaving': leaving }">
                <div class="login-card__orb" aria-hidden="true">
                    <span class="login-card__ring" />
                    <span class="login-card__ring login-card__ring--delay" />
                </div>

                <div class="login-card__body">
                    <p class="login-card__tag">Pengadilan Agama Semarang</p>
                    <p class="login-card__tagline">Lawangsewu Digital Command Center</p>
                    <p class="login-card__title">
                        Selamat datang kembali, <span>{{ loginUserName }}</span>
                    </p>
                    <p class="login-card__subtitle">
                        Menyiapkan workspace dan sinkronisasi layanan untuk sesi kerja Anda.
                    </p>
                    <p class="login-card__greeting">Assalamu'alaikum warahmatullahi wabarakatuh</p>
                </div>

                <div class="login-card__bar" />
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.login-splash {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: grid;
    place-items: center;
    padding: 1.5rem;
}

.login-splash__backdrop {
    position: absolute;
    inset: 0;
    background: radial-gradient(circle at 50% 30%, rgba(56, 189, 248, 0.16), rgba(2, 6, 23, 0.78) 58%);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
}

.login-card {
    position: relative;
    z-index: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.9rem;
    padding: 2rem 2rem 1.6rem;
    border-radius: 1.45rem;
    overflow: hidden;
    min-width: min(36rem, calc(100vw - 3rem));
    max-width: 36rem;
    background: linear-gradient(145deg, rgba(15, 23, 42, 0.92), rgba(2, 6, 23, 0.94));
    border: 1px solid rgba(125, 211, 252, 0.26);
    box-shadow:
        0 30px 80px rgba(2, 6, 23, 0.62),
        0 0 0 1px rgba(125, 211, 252, 0.1),
        0 0 50px rgba(56, 189, 248, 0.2);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    transform: translateY(-120vh) scale(0.94);
    animation: card-enter 760ms cubic-bezier(0.2, 0.9, 0.25, 1) forwards;
}

.login-card--leaving {
    animation: card-leave 600ms cubic-bezier(0.4, 0, 0.2, 1) forwards;
}

.login-card__orb {
    position: relative;
    width: 3.2rem;
    height: 3.2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.42), rgba(14, 165, 233, 0.08));
}

.login-card__ring {
    position: absolute;
    inset: 0;
    border-radius: 999px;
    border: 2px solid rgba(125, 211, 252, 0.6);
    animation: ring-pulse 1.6s ease-out infinite;
}

.login-card__ring--delay {
    animation-delay: 0.8s;
}

.login-card__body {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.4rem;
    text-align: center;
}

.login-card__tag {
    font-size: 0.65rem;
    text-transform: uppercase;
    letter-spacing: 0.22em;
    font-weight: 800;
    color: rgba(186, 230, 253, 0.95);
}

.login-card__tagline {
    font-size: 0.64rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: rgba(148, 163, 184, 0.95);
}

.login-card__title {
    margin: 0;
    font-size: clamp(1rem, 2.6vw, 1.45rem);
    line-height: 1.35;
    font-weight: 800;
    color: #e2e8f0;
    letter-spacing: -0.01em;
}

.login-card__title span {
    color: #7dd3fc;
}

.login-card__subtitle {
    margin: 0;
    font-size: 0.82rem;
    color: rgba(148, 163, 184, 0.95);
    letter-spacing: 0.01em;
}

.login-card__greeting {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: rgba(186, 230, 253, 0.9);
    letter-spacing: -0.01em;
}

.login-card__bar {
    position: absolute;
    bottom: 0;
    left: 0;
    height: 4px;
    width: 100%;
    background: linear-gradient(90deg, #0ea5e9, #22d3ee, #7dd3fc);
    border-radius: 0 0 1.45rem 1.45rem;
    transform-origin: left;
    animation: bar-deplete 10s linear forwards;
}

@keyframes card-enter {
    0% {
        transform: translateY(-120vh) scale(0.85);
        opacity: 0;
        filter: blur(8px);
    }
    65% {
        transform: translateY(12px) scale(1.02);
        opacity: 1;
        filter: blur(0px);
    }
    100% {
        transform: translateY(0) scale(1);
        opacity: 1;
        filter: blur(0px);
    }
}

@keyframes card-leave {
    0% {
        transform: translateY(0) scale(1);
        opacity: 1;
        filter: blur(0px);
    }
    100% {
        transform: translateY(-40vh) scale(0.9);
        opacity: 0;
        filter: blur(12px);
    }
}

@keyframes ring-pulse {
    0% {
        transform: scale(0.55);
        opacity: 0.85;
    }
    100% {
        transform: scale(1.35);
        opacity: 0;
    }
}

@keyframes bar-deplete {
    from { transform: scaleX(1); }
    to   { transform: scaleX(0); }
}

@media (max-width: 640px) {
    .login-card {
        min-width: 100%;
        padding: 1.5rem 1.25rem 1.2rem;
    }
}
</style>
