<script setup>
import Hls from 'hls.js';
import { computed, nextTick, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    src: { type: String, default: '' },
    title: { type: String, default: 'CCTV stream' },
    controls: { type: Boolean, default: false },
    mediaClass: { type: String, default: '' },
    iframeClass: { type: String, default: '' },
});

const emit = defineEmits(['loaded', 'error']);

const videoRef = ref(null);
let hls = null;

const isHls = computed(() => /\.m3u8(?:\?|$)/i.test(props.src || ''));

function destroyHls() {
    if (hls) {
        hls.destroy();
        hls = null;
    }

    if (videoRef.value) {
        videoRef.value.removeAttribute('src');
        videoRef.value.load();
    }
}

async function attachHls() {
    destroyHls();

    if (!props.src || !isHls.value) {
        return;
    }

    await nextTick();

    const video = videoRef.value;
    if (!video) {
        return;
    }

    video.muted = true;
    video.playsInline = true;

    if (video.canPlayType('application/vnd.apple.mpegurl')) {
        video.src = props.src;
        video.play?.().catch(() => {});
        return;
    }

    if (!Hls.isSupported()) {
        emit('error');
        return;
    }

    hls = new Hls({
        enableWorker: false,
        lowLatencyMode: true,
        backBufferLength: 30,
        maxBufferLength: 20,
    });

    hls.on(Hls.Events.MANIFEST_PARSED, () => {
        emit('loaded');
        video.play?.().catch(() => {});
    });

    hls.on(Hls.Events.ERROR, (_event, data) => {
        if (data?.fatal) {
            emit('error');
        }
    });

    hls.loadSource(props.src);
    hls.attachMedia(video);
}

watch(() => props.src, attachHls, { immediate: true });

onBeforeUnmount(() => {
    destroyHls();
});
</script>

<template>
    <video
        v-if="isHls"
        ref="videoRef"
        :title="title"
        :controls="controls"
        autoplay
        muted
        playsinline
        preload="metadata"
        :class="mediaClass"
        @loadedmetadata="$emit('loaded')"
        @error="$emit('error')"
    />

    <iframe
        v-else
        :src="src"
        :title="title"
        :class="iframeClass || mediaClass"
        loading="lazy"
        allow="autoplay; fullscreen; picture-in-picture"
        referrerpolicy="no-referrer-when-downgrade"
        sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-presentation"
        @load="$emit('loaded')"
        @error="$emit('error')"
    />
</template>
