import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/analysis.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],

    // 新增 Docker 專用伺服器設定
    server: {
        host: '0.0.0.0', // 允許外部 (宿主機) 連線
        hmr: {
            host: 'localhost', // 告訴瀏覽器熱更新要連回 localhost
        },
        watch: {
            usePolling: true, // 在某些 Docker 環境下需要這個才能偵測檔案變更
        },
    },

});
