import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig(({ command }) => {
    const plugins = [
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ];

    // Only include wayfinder plugin in build mode
    // In dev mode, types are generated manually via 'npm run wayfinder:generate'
    // This prevents HMR errors when the plugin tries to regenerate types
    if (command === 'build') {
        plugins.splice(1, 0, wayfinder({
            formVariants: true,
            generateOnBuild: false, // Types are generated via prebuild script
        }));
    }

    return {
        plugins,
    };
});
