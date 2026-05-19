import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    worker: {
        format: 'es',
    },
    optimizeDeps: {
        exclude: ['pdfjs-dist'],
    },
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes('pdfjs-dist')) return 'pdfjs';
                    if (id.includes('tesseract.js')) return 'tesseract';
                    if (id.includes('pixelmatch') || id.includes('diff') || id.includes('pdf-lib')) return 'compare-pdf-libs';
                },
            },
        },
    },
});
