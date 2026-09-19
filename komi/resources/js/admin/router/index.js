import { createRouter, createWebHistory } from 'vue-router';
import AdminLayout from '../components/AdminLayout.vue';
import Dashboard from '../views/Dashboard.vue';

const routes = [
    {
        path: '/admin',
        component: AdminLayout,
        children: [
            {
                path: '',
                name: 'admin.dashboard',
                component: Dashboard,
            },
            {
                path: 'users',
                name: 'admin.users',
                component: () => import('../views/Users.vue'),
            },
            {
                path: 'users/:id',
                name: 'admin.user-detail',
                component: () => import('../views/UserDetail.vue'),
            },
            {
                path: 'posts',
                name: 'admin.posts',
                component: () => import('../views/Posts.vue'),
            },
            {
                path: 'tags',
                name: 'admin.tags',
                component: () => import('../views/Tags.vue'),
            },
            {
                path: 'communities',
                name: 'admin.communities',
                component: () => import('../views/Communities.vue'),
            },
            {
                path: 'reports',
                name: 'admin.reports',
                component: () => import('../views/Reports.vue'),
            },
            {
                path: 'moderation',
                name: 'admin.moderation',
                component: () => import('../views/ModerationQueue.vue'),
            },
            {
                path: 'appeals',
                name: 'admin.appeals',
                component: () => import('../views/Appeals.vue'),
            },
            {
                path: 'analytics',
                name: 'admin.analytics',
                component: () => import('../views/Analytics.vue'),
            },
            {
                path: 'logs',
                name: 'admin.logs',
                component: () => import('../views/AdminLog.vue'),
            },
            {
                path: 'system',
                name: 'admin.system',
                component: () => import('../views/SystemHealth.vue'),
            },
            {
                path: 'auto-mod',
                name: 'admin.auto-mod',
                component: () => import('../views/AutoMod.vue'),
            },
            {
                path: 'settings',
                name: 'admin.settings',
                component: () => import('../views/Settings.vue'),
            },
            {
                path: 'general-settings',
                name: 'admin.general-settings',
                component: () => import('../views/GeneralSettings.vue'),
            },
            {
                path: 'jobs',
                name: 'admin.jobs',
                component: () => import('../views/JobsQueue.vue'),
            },
            {
                path: 'sessions',
                name: 'admin.sessions',
                component: () => import('../views/Sessions.vue'),
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
