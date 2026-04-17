<script setup>
defineProps({
    camera: {
        type: Object,
        required: true,
    },
    selected: {
        type: Boolean,
        default: false,
    },
    previewHeight: {
        type: String,
        default: 'h-44',
    },
});

defineEmits(['select']);
</script>

<template>
    <button
        type="button"
        class="card-surface w-full overflow-hidden text-left transition-all duration-300 hover:scale-[1.01] hover:shadow-xl active:scale-[0.99] group"
        :class="selected ? 'border-blue-500 bg-blue-500/[0.02] shadow-lg shadow-blue-500/10' : 'border-[var(--border)]'"
        @click="$emit('select', camera)"
    >
        <div class="flex items-center justify-between gap-3 border-b border-[var(--border)] px-4 py-4 bg-[var(--surface-2)]/50 backdrop-blur-sm">
            <div class="min-w-0">
                <p class="text-[13px] font-black text-[var(--text-1)] tracking-tight truncate group-hover:text-blue-500 transition-colors">
                    {{ camera.name }}
                </p>
                <p class="text-[10px] font-bold text-[var(--text-3)] uppercase tracking-widest mt-0.5">
                    {{ camera.zone }} · {{ camera.updatedAt }}
                </p>
            </div>

            <div class="flex flex-col items-end gap-1">
                <span class="inline-flex items-center gap-2 rounded-lg border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[9px] font-black uppercase tracking-[0.2em] text-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.1)]">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse-slow shadow-[0_0_5px_rgba(16,185,129,0.5)]" />
                    {{ camera.status }}
                </span>
                <p class="text-[9px] font-bold uppercase tracking-widest text-[var(--text-3)] opacity-60">
                    {{ camera.resolution }}
                </p>
            </div>
        </div>

        <div class="bg-black/40" :class="previewHeight">
            <iframe
                :src="camera.iframeSrc"
                :title="camera.name"
                class="h-full w-full border-0"
                loading="lazy"
                allow="autoplay; fullscreen"
                referrerpolicy="strict-origin-when-cross-origin"
            />
        </div>
    </button>
</template>
