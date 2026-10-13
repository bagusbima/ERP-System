import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../pages/Dashboard.vue'),
                meta: { requiresCompany: true },
            },
            {
                path: 'master-data',
                name: 'master-data',
                component: () => import('../pages/MasterData.vue'),
                meta: { requiresCompany: true, permission: 'master.view' },
            },
            {
                path: 'inventory',
                name: 'inventory',
                component: () => import('../pages/Inventory.vue'),
                meta: { requiresCompany: true, permission: 'inventory.view' },
            },
            {
                path: 'purchasing',
                name: 'purchasing',
                component: () => import('../pages/Purchasing.vue'),
                meta: { requiresCompany: true, permission: 'purchasing.view' },
            },
            {
                path: 'companies',
                name: 'companies',
                component: () => import('../pages/Companies.vue'),
            },
            {
                path: 'users',
                name: 'users',
                component: () => import('../pages/Users.vue'),
                meta: { requiresCompany: true, permission: 'users.view' },
            },
            {
                path: 'roles',
                name: 'roles',
                component: () => import('../pages/Roles.vue'),
                meta: { requiresCompany: true, permission: 'users.view' },
            },
        ],
    },
    {
        path: '/:pathMatch(.*)*',
        redirect: '/',
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: () => ({ top: 0 }),
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    await auth.restore();

    if (to.meta.guest) {
        return auth.isAuthenticated ? { name: 'dashboard' } : true;
    }

    if (! auth.isAuthenticated) {
        return { name: 'login', query: { redirect: to.fullPath } };
    }

    if (to.meta.requiresCompany && ! auth.company) {
        return { name: 'companies' };
    }

    if (to.meta.permission && ! auth.can(to.meta.permission)) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
