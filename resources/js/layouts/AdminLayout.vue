<template>
    <div class="min-h-screen flex" style="background:#f4f4f5">
        <!-- Sidebar -->
        <aside class="w-60 flex flex-col" style="background:#C82830">
            <!-- Logo -->
            <div class="flex items-center justify-center px-6 py-6 border-b" style="border-color:rgba(255,255,255,0.15)">
                <img src="https://madnutz.com.br/wp-content/uploads/2024/06/LOGO-MADNUTZ-SEM-FUNDO.png"
                     alt="MadNutz" class="h-12 object-contain brightness-0 invert" />
            </div>

            <nav class="flex-1 px-3 py-5 space-y-1">
                <RouterLink
                    v-for="item in menu"
                    :key="item.to"
                    :to="item.to"
                    class="flex items-center gap-3 px-4 py-3 rounded-xl font-semibold text-sm transition-all"
                    style="color:rgba(255,255,255,0.75)"
                    active-class="!text-white !bg-black/20"
                >
                    <i :class="item.icon" class="text-base w-5 text-center" />
                    <span>{{ item.label }}</span>
                </RouterLink>
            </nav>

            <!-- User / Logout -->
            <div class="px-3 pb-5 border-t pt-4" style="border-color:rgba(255,255,255,0.15)">
                <div class="px-4 mb-2 text-xs font-medium" style="color:rgba(255,255,255,0.5)">
                    {{ user?.name }}
                </div>
                <button
                    @click="handleLogout"
                    class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-semibold transition-all"
                    style="color:rgba(255,255,255,0.75)"
                    onmouseenter="this.style.background='rgba(0,0,0,0.2)';this.style.color='#fff'"
                    onmouseleave="this.style.background='';this.style.color='rgba(255,255,255,0.75)'"
                >
                    <i class="pi pi-sign-out text-base w-5 text-center" />
                    <span>Sair</span>
                </button>
            </div>
        </aside>

        <!-- Main -->
        <main class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="bg-white border-b border-gray-200 px-8 py-4 flex items-center justify-between">
                <h1 class="text-lg font-bold text-gray-800 uppercase tracking-wide" style="font-family:'Passion One',sans-serif;letter-spacing:.05em">
                    {{ pageTitle }}
                </h1>
                <span class="text-xs font-semibold uppercase tracking-widest" style="color:#C82830">MadNutz Admin</span>
            </header>

            <div class="flex-1 p-8">
                <RouterView />
            </div>
        </main>

        <Toast />
        <ConfirmDialog />
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import Toast from 'primevue/toast';
import ConfirmDialog from 'primevue/confirmdialog';
import { useAuthStore } from '../stores/auth.js';

const route  = useRoute();
const router = useRouter();
const { user, logout } = useAuthStore();

const menu = [
    { label: 'Dashboard', to: '/admin/dashboard', icon: 'pi pi-chart-bar' },
    { label: 'Produtos',  to: '/admin/produtos',  icon: 'pi pi-box' },
    { label: 'Kits',      to: '/admin/kits',      icon: 'pi pi-gift' },
    { label: 'Pedidos',   to: '/admin/pedidos',   icon: 'pi pi-shopping-cart' },
    { label: 'Cupons',    to: '/admin/cupons',    icon: 'pi pi-tag' },
];

const titles = {
    'admin.dashboard': 'Dashboard',
    'admin.products':  'Produtos',
    'admin.kits':      'Kits',
    'admin.orders':    'Pedidos',
    'admin.coupons':   'Cupons',
};
const pageTitle = computed(() => titles[route.name] ?? 'Admin');

async function handleLogout() {
    await logout();
    router.push({ name: 'admin.login' });
}
</script>
