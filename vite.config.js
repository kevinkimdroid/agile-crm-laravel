import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: false,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            // Debounce full-page reload so navigation (e.g. Dashboard) is not cancelled mid-click.
            refresh: [
                {
                    paths: ['resources/views/**', 'routes/**', 'app/**'],
                    config: { delay: 800 },
                },
            ],
        }),
    ],
});
