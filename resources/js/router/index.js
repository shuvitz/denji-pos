import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth.store';

const LoginPage = () => import('../pages/LoginPage.vue');
const DashboardPage = () => import('../pages/DashboardPage.vue');
const ItemsPage = () => import('../pages/ItemsPage.vue');
const CategoriesPage = () => import('../pages/CategoriesPage.vue');
const ItemMovementsPage = () => import('../pages/ItemMovementsPage.vue');
const ItemVariantsPage = () => import('../pages/ItemVariantsPage.vue');
const ReferenceTypesPage = () => import('../pages/ReferenceTypesPage.vue');

// Layout
const MainLayout = () => import('../layouts/MainLayout.vue');

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: { guest: true },
    },
    {
        path: '/',
        component: MainLayout,
        meta: { auth: true },
        children: [
            {
                path: '',
                redirect: '/dashboard',
            },
            {
                path: '/dashboard',
                name: 'dashboard',
                component: DashboardPage,
            },
            {
                path: '/items',
                name: 'items',
                component: ItemsPage,
            },
            {
                path: '/categories',
                name: 'categories',
                component: CategoriesPage,
            },
            {
                path: '/item-movements',
                name: 'item-movements',
                component: ItemMovementsPage,
            },
            {
                path: '/item-variants',
                name: 'item-variants',
                component: ItemVariantsPage,
            },
            {
                path: '/reference-types',
                name: 'reference-types',
                component: ReferenceTypesPage,
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

/**
 * Navigation guard — protect auth routes, redirect guests.
 */
router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // On first navigation, try to fetch the user if we don't know auth state yet
    if (auth.user === null && !auth.isLoading) {
        try {
            await auth.fetchUser();
        } catch {
            // not authenticated — that's fine
        }
    }

    // Trying to access an auth-required route while not authenticated
    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    // Trying to access a guest-only route while authenticated
    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

export default router;
