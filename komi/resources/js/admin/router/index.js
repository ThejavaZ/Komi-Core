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
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

export default router;
