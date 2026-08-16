import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin-dashboard.js', 'resources/js/department-dashboard.js'],
            refresh: true,
        }),
        vue(),
    ],
    server: {
        host: '127.0.0.1',
    },
})