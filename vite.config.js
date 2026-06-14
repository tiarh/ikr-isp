import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
    build: {
        outDir: 'public/build',
        emptyOutDir: true,
        sourcemap: false,
        rollupOptions: {
            output: {
                manualChunks: {
                    react: ['react', 'react-dom'],
                    inertia: ['@inertiajs/react'],
                    charts: ['recharts'],
                    map: ['leaflet', 'react-leaflet'],
                },
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: { host: 'localhost' },
    },
});
