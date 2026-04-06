<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const canvasRef = ref(null);
let rafId = null;

const particles = [];
const particleCount = 26;

function resize(canvas) {
    const ratio = Math.min(window.devicePixelRatio || 1, 1.5);
    const { clientWidth, clientHeight } = canvas;

    canvas.width = Math.floor(clientWidth * ratio);
    canvas.height = Math.floor(clientHeight * ratio);

    const ctx = canvas.getContext('2d');
    if (ctx) {
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
    }
}

function resetParticle(p, w, h) {
    p.x = Math.random() * w;
    p.y = Math.random() * h;
    p.vx = (Math.random() - 0.5) * 0.25;
    p.vy = (Math.random() - 0.5) * 0.25;
    p.r = 0.8 + Math.random() * 1.6;
    p.a = 0.1 + Math.random() * 0.28;
}

function animate() {
    const canvas = canvasRef.value;
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const w = canvas.clientWidth;
    const h = canvas.clientHeight;

    ctx.clearRect(0, 0, w, h);

    for (const p of particles) {
        p.x += p.vx;
        p.y += p.vy;

        if (p.x < -8 || p.x > w + 8 || p.y < -8 || p.y > h + 8) {
            resetParticle(p, w, h);
        }

        ctx.beginPath();
        ctx.fillStyle = `rgba(88, 166, 255, ${p.a})`;
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
    }

    rafId = requestAnimationFrame(animate);
}

onMounted(() => {
    const canvas = canvasRef.value;
    if (!canvas) return;

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    resize(canvas);

    particles.length = 0;
    for (let i = 0; i < particleCount; i += 1) {
        const p = {};
        resetParticle(p, canvas.clientWidth, canvas.clientHeight);
        particles.push(p);
    }

    window.addEventListener('resize', () => resize(canvas));
    animate();
});

onBeforeUnmount(() => {
    if (rafId) {
        cancelAnimationFrame(rafId);
    }
});
</script>

<template>
    <canvas ref="canvasRef" class="pointer-events-none absolute inset-0 h-full w-full" />
</template>
