import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0', // Allow external access
        port: 5173, // Ensure it matches Vite's default port
        strictPort: true,
        hmr: {
            host: 'localhost', // Ensure Hot Module Replacement works
            protocol: 'ws', // WebSocket for local dev
        },
    },
});
