import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) {
                        return undefined;
                    }

                    if (id.includes('/vue/') || id.includes('@vue') || id.includes('@inertiajs')) {
                        return 'vendor-vue';
                    }

                    if (id.includes('laravel-echo') || id.includes('pusher-js')) {
                        return 'vendor-realtime';
                    }

                    if (id.includes('emoji-picker-element')) {
                        return 'vendor-chat';
                    }

                    return 'vendor-misc';
                },
            },
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
