import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

// Fontsource の CSS は woff2 と woff の両方を指す。woff2 だけを配信する(ビルドの大きさが半分になる)
const woff2Only = () => ({
    name: 'doinaka-woff2-only',
    enforce: 'pre',
    transform(code, id) {
        if (!id.includes('@fontsource') || !id.endsWith('.css')) {
            return null;
        }
        return code.replace(/,\s*url\([^)]*\.woff\)\s*format\('woff'\)/g, '');
    },
});

export default defineConfig({
    plugins: [
        woff2Only(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '0.0.0.0',
        port: 5173,
        strictPort: true,
        hmr: { host: 'localhost' },
        watch: {
            // Windows のフォルダを Docker にマウントしているので、変更はポーリングで検知する
            usePolling: true,
            interval: 500,
            ignored: ['**/storage/framework/views/**', '**/vendor/**', '**/node_modules/**'],
        },
    },
    build: {
        // 画像(イラスト)は数が多いので、小さくてもインラインにしない
        assetsInlineLimit: 0,
    },
});
