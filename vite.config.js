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
                'resources/css/reports/index.css',
                'resources/css/admin/admin-application-list.css',
                // 管理者ログイン画面のCSS
                'resources/css/admin/admin-login.css',
               // 管理者勤怠一覧画面のCSS
                'resources/css/admin/admin-attendance-list.css',
               // 管理者勤怠詳細画面のCSS
                'resources/css/admin/admin-detail.css',
               // 管理者申請詳細画面のCSS
                'resources/css/admin/admin-application-detail.css',
               // スタッフ勤怠一覧画面のCSS
                'resources/css/admin/staff-attendance-list.css',
               // スタッフ一覧画面のCSS
                'resources/css/admin/staff-list.css',
                'resources/css/user/attendance-register.css',
                'resources/css/user/user-attendance-list.css',
                'resources/css/user/register.css',
                'resources/css/user/user-login.css',
                'resources/css/user/user-detail.css',
                'resources/css/user/user-application-list.css',
                'resources/css/auth/verify-email.css',
            ],
            refresh: true,
        }),
    ],
});
