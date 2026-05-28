/**
 * useNetworkParticles — Composable Network Constellation Particle System
 *
 * Animasi partikel konstelasi jaringan yang tampil di background
 * halaman WaCaraka saat dark mode aktif.
 *
 * Diekstrak dari Index.vue (sebelumnya ~140 baris inline).
 *
 * Usage:
 *   const { particleCanvasRef, startParticles, stopParticles } = useNetworkParticles(isDark);
 *
 *   // Di template: <canvas ref="particleCanvasRef" ... />
 *   // Di onMounted: if (isDark.value) nextTick(() => startParticles())
 *   // Di onUnmounted: stopParticles()
 */
import { ref } from 'vue';

// ── Konstanta ────────────────────────────────────────────
const NET_COUNT    = 38;
const NET_LINK_DIST = 130;
const NET_FPS_CAP  = 30;
const NET_FRAME_MS = 1000 / NET_FPS_CAP;

const NET_NODE_COLORS = [
    { r: 59,  g: 130, b: 246, w: 6 },  // blue-500
    { r: 125, g: 211, b: 252, w: 3 },  // sky-300
    { r: 34,  g: 197, b: 94,  w: 2 },  // green-500 (aksen)
    { r: 56,  g: 189, b: 248, w: 4 },  // sky-400
];

export function useNetworkParticles(isDark) {
    const particleCanvasRef = ref(null);

    let particleAnimFrame = null;
    let particleCtx       = null;
    let particleCanvas_   = null;
    let _lastFrameTime    = 0;
    let netNodes          = [];

    // ── Helpers ─────────────────────────────────────────
    const pickNodeColor = () => {
        const totalW = NET_NODE_COLORS.reduce((s, c) => s + c.w, 0);
        let rnd = Math.random() * totalW;
        for (const c of NET_NODE_COLORS) { rnd -= c.w; if (rnd <= 0) return c; }
        return NET_NODE_COLORS[0];
    };

    const createNetNode = (canvas) => {
        const color = pickNodeColor();
        return {
            x:      Math.random() * canvas.width,
            y:      Math.random() * canvas.height,
            r:      Math.random() * 1.6 + 0.7,
            vx:     (Math.random() - 0.5) * 0.28,
            vy:     (Math.random() - 0.5) * 0.28,
            color,
            opacity: Math.random() * 0.55 + 0.35,
        };
    };

    // ── Draw Loop ────────────────────────────────────────
    const drawNetwork = (ts) => {
        particleAnimFrame = requestAnimationFrame(drawNetwork);

        if (ts - _lastFrameTime < NET_FRAME_MS) return;
        _lastFrameTime = ts;

        const canvas = particleCanvas_;
        const ctx    = particleCtx;
        if (!canvas || !ctx) return;

        const W = canvas.width;
        const H = canvas.height;
        ctx.clearRect(0, 0, W, H);

        // Gerak + wrap
        for (const n of netNodes) {
            n.x += n.vx;
            n.y += n.vy;
            if (n.x < -10)  n.x = W + 10;
            if (n.x > W + 10) n.x = -10;
            if (n.y < -10)  n.y = H + 10;
            if (n.y > H + 10) n.y = -10;
        }

        // Garis koneksi
        for (let i = 0; i < netNodes.length; i++) {
            const a = netNodes[i];
            for (let j = i + 1; j < netNodes.length; j++) {
                const b    = netNodes[j];
                const dx   = a.x - b.x;
                const dy   = a.y - b.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                if (dist > NET_LINK_DIST) continue;
                const lineOpacity = (1 - dist / NET_LINK_DIST) * 0.28;
                const { r, g, b: bc } = a.color;
                ctx.beginPath();
                ctx.moveTo(a.x, a.y);
                ctx.lineTo(b.x, b.y);
                ctx.strokeStyle = `rgba(${r},${g},${bc},${lineOpacity})`;
                ctx.lineWidth   = 0.7;
                ctx.stroke();
            }
        }

        // Node (titik + glow)
        for (const n of netNodes) {
            const { r, g, b: bc } = n.color;
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${r},${g},${bc},${n.opacity})`;
            ctx.fill();
            ctx.beginPath();
            ctx.arc(n.x, n.y, n.r * 2.4, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(${r},${g},${bc},0.06)`;
            ctx.fill();
        }
    };

    // ── Public API ───────────────────────────────────────
    const startParticles = () => {
        const canvas = particleCanvasRef.value;
        if (!canvas || !isDark.value) return;
        const parent = canvas.parentElement;
        if (!parent) return;

        const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        const W   = parent.offsetWidth  || 800;
        const H   = parent.offsetHeight || 500;

        canvas.width        = W * dpr;
        canvas.height       = H * dpr;
        canvas.style.width  = `${W}px`;
        canvas.style.height = `${H}px`;

        particleCtx   = canvas.getContext('2d');
        particleCtx.scale(dpr, dpr);
        particleCanvas_ = { width: W, height: H };

        netNodes = Array.from({ length: NET_COUNT }, () => createNetNode(particleCanvas_));

        if (particleAnimFrame) cancelAnimationFrame(particleAnimFrame);
        _lastFrameTime = 0;
        particleAnimFrame = requestAnimationFrame(drawNetwork);
    };

    const stopParticles = () => {
        if (particleAnimFrame) {
            cancelAnimationFrame(particleAnimFrame);
            particleAnimFrame = null;
        }
        if (particleCtx && particleCanvasRef.value) {
            particleCtx.clearRect(0, 0, particleCanvasRef.value.width, particleCanvasRef.value.height);
        }
        particleCtx     = null;
        particleCanvas_ = null;
        netNodes        = [];
    };

    return {
        particleCanvasRef,
        startParticles,
        stopParticles,
    };
}
