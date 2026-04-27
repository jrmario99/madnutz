<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useCustomer } from '../stores/customer.js';
import { useFavorites } from '../stores/favorites.js';

const router = useRouter();
const route  = useRoute();
const { customer, logout } = useCustomer();
const { load: loadFavorites, reset: resetFavorites } = useFavorites();

const mobileOpen = ref(false);

onMounted(loadFavorites);

const navItems = [
    { label: 'Pedidos',   to: '/minha-conta/pedidos',  icon: 'pi-shopping-bag' },
    { label: 'Favoritos', to: '/minha-conta/favoritos', icon: 'pi-heart' },
    { label: 'Perfil',    to: '/minha-conta/perfil',    icon: 'pi-user' },
];

function isActive(path) {
    return route.path.startsWith(path);
}

function handleLogout() {
    logout();
    resetFavorites();
    router.push('/');
}
</script>

<template>
    <div class="min-h-screen" style="background:#131313;color:#e5e2e1;">
        <!-- Glow de fundo -->
        <div class="pointer-events-none fixed inset-0"
             style="z-index:0;background:radial-gradient(ellipse 80% 40% at 50% -5%, rgba(200,40,48,0.18) 0%, transparent 60%);" />

        <!-- ── Header (barra vermelha igual ao AppLayout) ── -->
        <header class="bg-mn-red sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">

                <!-- Logo -->
                <router-link to="/" class="flex-shrink-0">
                    <img src="https://madnutz.com.br/wp-content/uploads/2026/01/MadNutz-Logo@1400x-scaled.png"
                         alt="MadNutz"
                         class="h-10 object-contain" />
                </router-link>

                <!-- Nav desktop -->
                <nav class="hidden md:flex items-center gap-1">
                    <router-link v-for="item in navItems" :key="item.to"
                                 :to="item.to"
                                 class="flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-bold uppercase tracking-widest transition-all duration-200"
                                 :style="isActive(item.to)
                                     ? 'color:#fff;background:rgba(0,0,0,0.25)'
                                     : 'color:rgba(255,255,255,0.65)'">
                        <i :class="['pi', item.icon]" style="font-size:14px;" />
                        {{ item.label }}
                    </router-link>
                </nav>

                <!-- Direita: nome + sair + hamburger -->
                <div class="flex items-center gap-3">
                    <span v-if="customer" class="hidden sm:block text-sm font-bold text-white/70 truncate max-w-[140px]">
                        {{ customer.name?.split(' ')[0] }}
                    </span>

                    <button @click="handleLogout"
                            class="hidden md:flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest px-3 py-2 rounded-lg border border-white/30 text-white/70 transition-all hover:bg-white/10 hover:text-white hover:border-white/60">
                        <i class="pi pi-sign-out" style="font-size:13px;" />
                        Sair
                    </button>

                    <!-- Hamburger mobile -->
                    <button @click="mobileOpen = !mobileOpen"
                            class="md:hidden p-2 rounded-lg text-white transition-colors">
                        <i :class="['pi', mobileOpen ? 'pi-times' : 'pi-bars']" style="font-size:20px;" />
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <Transition name="slide">
                <div v-if="mobileOpen"
                     class="md:hidden border-t border-white/20"
                     style="background:rgba(180,20,25,0.98);">
                    <nav class="max-w-6xl mx-auto px-4 py-3 flex flex-col gap-1">
                        <router-link v-for="item in navItems" :key="item.to"
                                     :to="item.to"
                                     @click="mobileOpen = false"
                                     class="flex items-center gap-3 px-4 py-3 rounded-lg text-sm font-bold uppercase tracking-widest transition-all"
                                     :style="isActive(item.to)
                                         ? 'color:#fff;background:rgba(0,0,0,0.25)'
                                         : 'color:rgba(255,255,255,0.7)'">
                            <i :class="['pi', item.icon]" style="font-size:16px;" />
                            {{ item.label }}
                        </router-link>
                        <div class="border-t border-white/20 mt-2 pt-2">
                            <button @click="handleLogout"
                                    class="flex items-center gap-2 px-4 py-3 text-sm font-bold uppercase tracking-widest text-white/60 w-full">
                                <i class="pi pi-sign-out" style="font-size:14px;" />
                                Sair da conta
                            </button>
                        </div>
                    </nav>
                </div>
            </Transition>
        </header>

        <!-- Page content -->
        <main class="relative z-10 max-w-5xl mx-auto px-4 sm:px-6 py-8">
            <RouterView />
        </main>
    </div>
</template>

<style scoped>
.slide-enter-active, .slide-leave-active { transition: opacity 0.2s ease, transform 0.2s ease; }
.slide-enter-from, .slide-leave-to       { opacity: 0; transform: translateY(-6px); }
</style>
