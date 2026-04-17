<script setup>
import { computed } from 'vue';

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    own: {
        type: Boolean,
        default: false,
    },
});

// 50 distinct bubble palettes: [bg, border, text, name-color]
// Designed for readability on both light and dark backgrounds
const PALETTES = [
    ['#1e3a5f', '#2e5687', '#c8dcf0', '#7eb8e8'],  // 0 navy blue
    ['#1a3d2b', '#2a5c3f', '#b8e8cc', '#5dba7a'],  // 1 forest green
    ['#3d1a2b', '#5c2a3e', '#ecc8d4', '#e07a9a'],  // 2 rose
    ['#2d1f4a', '#442f6e', '#d4c8f0', '#a07ae0'],  // 3 purple
    ['#3d2a1a', '#6e4a2a', '#f0d4b8', '#e09a5a'],  // 4 amber
    ['#1a3d3d', '#2a5c5c', '#b8e8e8', '#5dbaba'],  // 5 teal
    ['#3d1a1a', '#6e2a2a', '#f0b8b8', '#e05a5a'],  // 6 crimson
    ['#1a2d3d', '#2a445c', '#b8ccf0', '#5a7ae0'],  // 7 steel blue
    ['#2a1a3d', '#3e2a5c', '#d4b8f0', '#a05ae0'],  // 8 violet
    ['#1a3d1a', '#2a5c2a', '#b8e8b8', '#5aba5a'],  // 9 green
    ['#3d3d1a', '#5c5c2a', '#e8e8b8', '#baba5a'],  // 10 yellow-green
    ['#3a1f1a', '#5c302a', '#f0c8b8', '#e07850'],  // 11 terracotta
    ['#1a1a3d', '#2a2a5c', '#b8b8f0', '#7070e0'],  // 12 indigo
    ['#1d3a2f', '#2c5c47', '#b8e8d4', '#5abaa0'],  // 13 mint
    ['#3a2a1a', '#5c432a', '#f0d8b8', '#e0a860'],  // 14 gold
    ['#2a1a2d', '#3e2a44', '#d8b8e8', '#b07aca'],  // 15 lavender
    ['#1d3340', '#2c4f60', '#b8d4e8', '#60a0c8'],  // 16 sky blue
    ['#3a1a20', '#5c2a32', '#f0b8c0', '#e06070'],  // 17 coral
    ['#1a2a20', '#2a3d2e', '#b8d4c0', '#70a880'],  // 18 sage
    ['#3a2010', '#5c3218', '#f0c0a0', '#e07840'],  // 19 rust
    ['#1a3050', '#2a4870', '#b8c8e8', '#6090d8'],  // 20 cobalt
    ['#302010', '#4c3218', '#e0c8a0', '#c89050'],  // 21 bronze
    ['#201040', '#301860', '#c8b0e8', '#9060d8'],  // 22 deep purple
    ['#103020', '#184830', '#a0d0b0', '#50a870'],  // 23 pine
    ['#401010', '#601818', '#f0a0a0', '#d84848'],  // 24 deep red
    ['#104040', '#186060', '#a0d8d8', '#48b0b0'],  // 25 cyan
    ['#303018', '#4c4c24', '#e0e0a0', '#b0b048'],  // 26 olive
    ['#280840', '#3c1260', '#d0a8f0', '#a840e8'],  // 27 electric purple
    ['#083828', '#0c5040', '#90d8b8', '#30c890'],  // 28 emerald
    ['#400820', '#601230', '#f0a8c0', '#d840780'],  // 29 hot pink
    ['#082840', '#0c3860', '#90b8e0', '#2878d0'],  // 30 ocean
    ['#383818', '#565622', '#e0e098', '#b0b030'],  // 31 lime
    ['#200820', '#300c30', '#d0a8d0', '#a030a0'],  // 32 plum
    ['#082020', '#0c3030', '#90c8c8', '#289898'],  // 33 aqua
    ['#382008', '#54300c', '#e8c090', '#c07828'],  // 34 caramel
    ['#081830', '#0c2448', '#90a8d0', '#2058b8'],  // 35 midnight
    ['#300808', '#480c0c', '#e8a0a0', '#c02828'],  // 36 blood
    ['#083010', '#0c4818', '#90d098', '#289840'],  // 37 jungle
    ['#181830', '#242448', '#9898d0', '#3030b0'],  // 38 dusk
    ['#301808', '#48240c', '#e8b890', '#c05818'],  // 39 copper
    ['#081030', '#0c1848', '#8898d0', '#1838b8'],  // 40 royal blue
    ['#280808', '#3c0c0c', '#d89090', '#b02020'],  // 41 maroon
    ['#102810', '#183c18', '#98c898', '#308030'],  // 42 army green
    ['#202820', '#302c30', '#c0c8c0', '#708870'],  // 43 gunmetal
    ['#081828', '#0c2438', '#8898b8', '#2048a0'],  // 44 slate
    ['#180808', '#280c0c', '#c09090', '#985050'],  // 45 wine
    ['#082808', '#0c3c0c', '#90c090', '#288028'],  // 46 vivid green
    ['#201010', '#301818', '#d0a8a8', '#a85858'],  // 47 dusty rose
    ['#282808', '#3c3c0c', '#d0d090', '#989820'],  // 48 khaki
    ['#080828', '#0c0c3c', '#9090d0', '#1818b0'],  // 49 midnight blue
];

// Deterministic hash → palette index by user_id
const getPalette = (userId) => {
    const n = typeof userId === 'number' ? userId : parseInt(userId, 10) || 0;
    return PALETTES[n % PALETTES.length];
};

const palette = computed(() => {
    if (props.own) return null;
    return getPalette(props.message.user_id);
});

const hexToRgba = (hex, alpha) => {
    if (!hex?.startsWith('#')) {
        return hex;
    }

    const normalized = hex.length === 4
        ? `#${hex[1]}${hex[1]}${hex[2]}${hex[2]}${hex[3]}${hex[3]}`
        : hex;
    const value = normalized.slice(1);
    const red = Number.parseInt(value.slice(0, 2), 16);
    const green = Number.parseInt(value.slice(2, 4), 16);
    const blue = Number.parseInt(value.slice(4, 6), 16);

    return `rgba(${red}, ${green}, ${blue}, ${alpha})`;
};

const bubbleStyle = computed(() => {
    if (props.own) return {};
    const [bg, border, textColor, nameColor] = palette.value;

    return {
        '--bubble-dark-bg': bg,
        '--bubble-dark-border': border,
        '--bubble-dark-text': textColor,
        '--bubble-accent': nameColor,
        '--bubble-light-border': hexToRgba(nameColor, 0.34),
        '--bubble-light-glow': hexToRgba(nameColor, 0.18),
    };
});

const nameStyle = computed(() => {
    if (props.own) return {};
    const [, , , nameColor] = palette.value;
    return { color: nameColor };
});

const textStyle = computed(() => {
    if (props.own) return {};
    const [, , textColor] = palette.value;
    return { '--bubble-dark-text': textColor };
});
</script>

<template>
    <div class="flex" :class="own ? 'items-end justify-end' : 'items-end justify-start'">
        <div
            class="max-w-[82%] rounded-3xl border px-4 py-3"
            :class="own ? 'bg-[var(--accent-soft)] border-[var(--accent-border)]' : 'chat-bubble-soft chat-bubble-soft--incoming'"
            :style="own ? {} : bubbleStyle"
        >
            <!-- Sender row: alias (bold) + ~ realName (muted gray) -->
            <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                <span
                    class="text-[11px] font-black uppercase tracking-wide"
                    :class="own ? 'text-[var(--accent)]' : ''"
                    :style="own ? {} : nameStyle"
                >
                    {{ message.alias || message.name }}
                </span>
                <span
                    v-if="message.realName && message.alias && message.alias !== message.realName"
                    class="text-[10px] font-semibold text-[var(--text-3)] opacity-60"
                >
                    ~ {{ message.realName }}
                </span>
                <span
                    class="text-[9px] font-bold ml-auto pl-2 opacity-70"
                    :class="own ? 'text-[var(--text-3)]' : ''"
                    :style="own ? {} : textStyle"
                >
                    {{ message.time }}
                </span>
            </div>

            <p
                v-if="message.body"
                class="text-sm leading-relaxed"
                :class="own ? 'text-[var(--text-1)]' : ''"
                :style="own ? {} : textStyle"
            >
                {{ message.body }}
            </p>

            <div v-if="message.attachment" class="mt-3 space-y-2">
                <a
                    v-if="message.attachment.kind === 'image'"
                    :href="message.attachment.url"
                    target="_blank"
                    rel="noreferrer"
                    class="block overflow-hidden rounded-2xl border border-black/5"
                >
                    <img
                        :src="message.attachment.url"
                        :alt="message.attachment.original_name || 'Lampiran gambar chat'"
                        class="max-h-80 w-full object-cover"
                        loading="lazy"
                    >
                </a>

                <video
                    v-else-if="message.attachment.kind === 'video'"
                    class="w-full rounded-2xl border border-black/5 bg-black"
                    controls
                    playsinline
                    preload="metadata"
                >
                    <source :src="message.attachment.url" :type="message.attachment.mime">
                </video>

                <a
                    :href="message.attachment.url"
                    target="_blank"
                    rel="noreferrer"
                    class="inline-flex items-center gap-2 text-[11px] font-semibold underline decoration-dotted underline-offset-4"
                    :class="own ? 'text-[var(--accent)]' : 'text-[var(--text-2)]'"
                >
                    {{ message.attachment.original_name || 'Buka lampiran' }}
                </a>
            </div>
        </div>
    </div>
</template>

<style scoped>
.chat-bubble-soft--incoming {
    background-color: var(--bubble-dark-bg);
    border-color: var(--bubble-dark-border);
    color: var(--bubble-dark-text);
}

.chat-bubble-soft--incoming :deep(p),
.chat-bubble-soft--incoming :deep(span) {
    color: inherit;
}

.theme-light .chat-bubble-soft--incoming {
    background:
        linear-gradient(180deg, rgba(255, 255, 255, 0.96), rgba(246, 248, 252, 0.92));
    border-color: var(--bubble-light-border);
    color: var(--text-1);
    box-shadow:
        inset 3px 0 0 var(--bubble-accent),
        0 18px 34px -28px var(--bubble-light-glow);
}

.theme-light .chat-bubble-soft--incoming p {
    color: var(--text-1) !important;
}

.theme-light .chat-bubble-soft--incoming span {
    color: var(--text-2);
}

.theme-light .chat-bubble-soft--incoming span:first-child {
    color: var(--bubble-accent);
}

.theme-light .chat-bubble-soft--incoming span:last-child {
    color: var(--text-3);
}
</style>
