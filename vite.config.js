import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/sanitize.css',
                'resources/css/common.css',
                'resources/css/admin/admin-application-list.css',
                'resources/css/user/attendance-register.css',
                'resources/css/user/user-attendance-list.css',
                'resources/css/user/register.css',
                'resources/css/user/user-login.css',
                'resources/css/user/user-detail.css',
                'resources/css/user/user-application-list.css',
            ],
            refresh: true,
        }),
    ],
});