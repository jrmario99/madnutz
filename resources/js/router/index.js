import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth.js';
import { useCustomer } from '../stores/customer.js';
import { useSettingsStore } from '../stores/settings.js';

const routes = [
    // Public routes (wrapped in AppLayout)
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        children: [
            {
                path: '',
                name: 'home',
                component: () => import('../pages/HomePage.vue'),
            },
            {
                path: 'categoria/:slug',
                name: 'category',
                component: () => import('../pages/ProductListPage.vue'),
            },
            {
                path: 'busca',
                name: 'search',
                component: () => import('../pages/ProductListPage.vue'),
            },
            {
                path: 'produto/:slug',
                name: 'product',
                component: () => import('../pages/ProductPage.vue'),
            },
            {
                path: 'kits/:slug/sabores',
                name: 'kit.selector',
                component: () => import('../pages/KitSelectorPage.vue'),
            },
            {
                path: 'kit-personalizado',
                name: 'custom.kit',
                component: () => import('../pages/CustomKitPage.vue'),
            },
            {
                path: 'carrinho',
                name: 'cart',
                component: () => import('../pages/CartPage.vue'),
            },
            {
                path: 'checkout',
                name: 'checkout',
                component: () => import('../pages/CheckoutPage.vue'),
            },
        ],
    },
    // Área do cliente
    {
        path: '/minha-conta/login',
        name: 'customer.login',
        component: () => import('../pages/customer/LoginPage.vue'),
        meta: { customerGuest: true },
    },
    {
        path: '/minha-conta',
        component: () => import('../layouts/CustomerLayout.vue'),
        meta: { requiresCustomer: true },
        children: [
            { path: '', redirect: '/minha-conta/pedidos' },
            {
                path: 'pedidos',
                name: 'customer.orders',
                component: () => import('../pages/customer/OrdersPage.vue'),
            },
            {
                path: 'pedidos/:id',
                name: 'customer.order',
                component: () => import('../pages/customer/OrderDetailPage.vue'),
            },
            {
                path: 'favoritos',
                name: 'customer.favorites',
                component: () => import('../pages/customer/FavoritesPage.vue'),
            },
            {
                path: 'perfil',
                name: 'customer.profile',
                component: () => import('../pages/customer/ProfilePage.vue'),
            },
        ],
    },
    // Páginas de erro
    {
        path: '/erro/:code',
        name: 'error',
        component: () => import('../pages/errors/ErrorPage.vue'),
    },
    // Admin login (standalone, no layout)
    {
        path: '/admin/login',
        name: 'admin.login',
        component: () => import('../pages/admin/LoginPage.vue'),
        meta: { guest: true },
    },
    // Admin area (AdminLayout)
    {
        path: '/admin',
        component: () => import('../layouts/AdminLayout.vue'),
        meta: { requiresAdmin: true },
        children: [
            { path: '', redirect: '/admin/dashboard' },
            {
                path: 'dashboard',
                name: 'admin.dashboard',
                component: () => import('../pages/admin/DashboardPage.vue'),
            },
            {
                path: 'produtos',
                name: 'admin.products',
                component: () => import('../pages/admin/ProductsPage.vue'),
            },
            {
                path: 'kits',
                name: 'admin.kits',
                component: () => import('../pages/admin/KitsPage.vue'),
            },
            {
                path: 'pedidos',
                name: 'admin.orders',
                component: () => import('../pages/admin/OrdersPage.vue'),
            },
            {
                path: 'cupons',
                name: 'admin.coupons',
                component: () => import('../pages/admin/CouponsPage.vue'),
            },
            {
                path: 'configuracoes',
                name: 'admin.settings',
                component: () => import('../pages/admin/SettingsPage.vue'),
            },
        ],
    },
    // Catch-all → 404
    {
        path: '/:pathMatch(.*)*',
        name: 'not-found',
        component: () => import('../pages/errors/ErrorPage.vue'),
        props: { code: '404' },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior: (to) => to.hash ? { el: to.hash, top: 80, behavior: 'smooth' } : { top: 0 },
});

router.beforeEach(async (to) => {
    const auth     = useAuthStore();
    const { isLoggedIn } = useCustomer();
    const { customKitEnabled, load: loadSettings } = useSettingsStore();

    // Admin guards
    if (to.meta.requiresAdmin && !auth.isAdmin.value) {
        return { name: 'admin.login' };
    }
    if (to.meta.guest && auth.isAdmin.value) {
        return { name: 'admin.dashboard' };
    }

    // Customer guards
    if (to.meta.requiresCustomer && !isLoggedIn.value) {
        return { name: 'customer.login' };
    }
    if (to.meta.customerGuest && isLoggedIn.value) {
        return { name: 'customer.orders' };
    }

    // Block custom kit route if disabled
    if (to.name === 'custom.kit') {
        await loadSettings();
        if (!customKitEnabled.value) return { name: 'home' };
    }
});

export default router;
