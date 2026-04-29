<script setup>
import { ref, onMounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import { useCart } from '../stores/cart.js';
import { useFavorites } from '../stores/favorites.js';
import { useCustomer } from '../stores/customer.js';
import CustomerLoginModal from '../components/CustomerLoginModal.vue';

const router = useRouter();
const { count } = useCart();
const { load: loadFavorites } = useFavorites();
const { isLoggedIn, customer } = useCustomer();
const menuOpen    = ref(false);
const pedidosHover = ref(false);
const loginOpen   = ref(false);

onMounted(loadFavorites);

function navTo(anchor) {
    menuOpen.value = false;
    router.push({ path: '/', hash: anchor });
}
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white">

        <!-- Header -->
        <header class="bg-mn-red sticky top-0 z-50">
            <div class="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
                <!-- Logo -->
                <router-link to="/" class="flex-shrink-0">
                    <img
                        src="https://madnutz.com.br/wp-content/uploads/2026/01/MadNutz-Logo@1400x-scaled.png"
                        alt="MadNutz"
                        class="h-10 object-contain"
                    />
                </router-link>

                <!-- Desktop nav -->
                <nav class="hidden md:flex items-center gap-8 text-white font-bold text-sm uppercase tracking-widest">
                    <router-link to="/" class="hover:text-mn-yellow transition-colors cursor-pointer">INÍCIO</router-link>
                    <button @click="navTo('#quem-somos')" class="hover:text-mn-yellow transition-colors cursor-pointer">QUEM SOMOS</button>
                    <button @click="navTo('#produtos1')" class="hover:text-mn-yellow transition-colors cursor-pointer">PRODUTOS</button>
                    <button @click="navTo('#contato')" class="hover:text-mn-yellow transition-colors cursor-pointer">FALE CONOSCO</button>
                </nav>

                <!-- Cart + menu -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Botão PEDIDOS — ticket no desktop, ícone compacto no mobile -->
                    <!-- Desktop: ticket SVG -->
                    <router-link
                        to="/carrinho"
                        class="relative hidden sm:inline-block"
                        style="width:137px; height:60px;"
                        @mouseenter="pedidosHover = true"
                        @mouseleave="pedidosHover = false"
                    >
                        <svg viewBox="0 0 137 60" class="absolute inset-0 w-full h-full" :fill="pedidosHover ? '#ffffff' : '#000000'" xmlns="http://www.w3.org/2000/svg">
                            <path d="M11.5828 47.0875C13.7502 44.136 13.513 40.0604 11.0177 37.3804L4.95929 30.8734C-5.9737 19.1309 2.35349 0 18.3977 0H121.599C130.491 0 135.295 10.4228 129.52 17.1837C125.99 21.3149 126.229 27.4647 130.068 31.3099L132.117 33.3631C140.647 41.9076 134.595 56.5 122.522 56.5H73.3638L18.1013 59.2141C11.6892 59.5291 7.78287 52.262 11.5828 47.0875Z"/>
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center gap-2 z-10"
                              :style="{ color: pedidosHover ? '#000' : '#fff', fontFamily: '\'Passion One\', sans-serif', fontSize: '18px', fontWeight: '500' }">
                            <svg class="w-4 h-4 flex-shrink-0" viewBox="0 0 448 512" fill="currentColor">
                                <path d="M352 160v-32C352 57.42 294.579 0 224 0 153.42 0 96 57.42 96 128v32H0v272c0 44.183 35.817 80 80 80h288c44.183 0 80-35.817 80-80V160h-96zm-192-32c0-35.29 28.71-64 64-64s64 28.71 64 64v32H160v-32zm160 120c-13.255 0-24-10.745-24-24s10.745-24 24-24 24 10.745 24 24-10.745 24-24 24zm-192 0c-13.255 0-24-10.745-24-24s10.745-24 24-24 24 10.745 24 24-10.745 24-24 24z"/>
                            </svg>
                            PEDIDOS
                        </span>
                        <span v-if="count > 0" class="absolute -top-1 -right-1 bg-mn-yellow text-mn-black text-xs font-black w-5 h-5 flex items-center justify-center rounded-full leading-none z-20">
                            {{ count > 9 ? '9+' : count }}
                        </span>
                    </router-link>

                    <!-- Mobile: botão compacto -->
                    <router-link to="/carrinho"
                        class="relative sm:hidden flex items-center justify-center w-11 h-11 rounded-xl"
                        style="background:#000;">
                        <svg class="w-5 h-5" viewBox="0 0 448 512" fill="white">
                            <path d="M352 160v-32C352 57.42 294.579 0 224 0 153.42 0 96 57.42 96 128v32H0v272c0 44.183 35.817 80 80 80h288c44.183 0 80-35.817 80-80V160h-96zm-192-32c0-35.29 28.71-64 64-64s64 28.71 64 64v32H160v-32zm160 120c-13.255 0-24-10.745-24-24s10.745-24 24-24 24 10.745 24 24-10.745 24-24 24zm-192 0c-13.255 0-24-10.745-24-24s10.745-24 24-24 24 10.745 24 24-10.745 24-24 24z"/>
                        </svg>
                        <span v-if="count > 0" class="absolute -top-1 -right-1 bg-mn-yellow text-mn-black text-[10px] font-black w-4 h-4 flex items-center justify-center rounded-full leading-none z-20">
                            {{ count > 9 ? '9+' : count }}
                        </span>
                    </router-link>

                    <!-- Minha Conta -->
                    <component :is="isLoggedIn ? 'router-link' : 'button'"
                        v-bind="isLoggedIn ? { to: '/minha-conta' } : {}"
                        @click="!isLoggedIn && (loginOpen = true)"
                        class="hidden md:flex items-center gap-1.5 text-white font-bold text-xs uppercase tracking-widest px-3 py-2 rounded-lg border border-white/20 transition-all hover:border-white/60 hover:bg-white/10">
                        <i class="pi pi-user" style="font-size:14px;" />
                        <span>{{ isLoggedIn ? (customer?.name?.split(' ')[0] ?? 'Conta') : 'Entrar' }}</span>
                    </component>

                    <!-- Mobile menu btn -->
                    <button class="md:hidden text-white font-black text-sm uppercase tracking-widest cursor-pointer" @click="menuOpen = !menuOpen">
                        MENU
                    </button>
                </div>
            </div>

            <!-- Mobile menu -->
            <div v-if="menuOpen" class="md:hidden bg-mn-black border-t border-white/20">
                <nav class="flex flex-col px-4 py-4 gap-3 text-white font-bold text-sm uppercase tracking-widest">
                    <router-link to="/" @click="menuOpen = false" class="hover:text-mn-yellow">INÍCIO</router-link>
                    <button @click="navTo('#quem-somos')" class="hover:text-mn-yellow text-left">QUEM SOMOS</button>
                    <button @click="navTo('#produtos1')" class="hover:text-mn-yellow text-left">PRODUTOS</button>
                    <button @click="navTo('#contato')" class="hover:text-mn-yellow text-left">FALE CONOSCO</button>
                    <component :is="isLoggedIn ? 'router-link' : 'button'"
                        v-bind="isLoggedIn ? { to: '/minha-conta' } : {}"
                        @click="isLoggedIn ? (menuOpen = false) : (menuOpen = false, loginOpen = true)"
                        class="hover:text-mn-yellow flex items-center gap-2 text-left">
                        <i class="pi pi-user" style="font-size:13px;" />
                        {{ isLoggedIn ? 'MINHA CONTA' : 'ENTRAR' }}
                    </component>
                </nav>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1">
            <RouterView />
        </main>

        <CustomerLoginModal :open="loginOpen" @close="loginOpen = false" />

        <!-- Footer -->
        <footer class="text-white" style="background:#b01e25">
            <!-- Main footer -->
            <div class="max-w-6xl mx-auto px-6 py-14">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-10">

                    <!-- Logo + social -->
                    <div class="flex flex-col gap-5">
                        <img
                            src="https://madnutz.com.br/wp-content/uploads/2026/01/MadNutz-Logo@1400x-scaled.png"
                            alt="MadNutz"
                            class="h-14 object-contain object-left"
                        />
                        <div class="flex gap-3">
                            <a href="https://instagram.com/madnutz" target="_blank"
                               class="w-9 h-9 border border-white/40 hover:border-white rounded-full flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                            <a href="https://tiktok.com/@madnutz" target="_blank"
                               class="w-9 h-9 border border-white/40 hover:border-white rounded-full flex items-center justify-center transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-2.88 2.5 2.89 2.89 0 01-2.89-2.89 2.89 2.89 0 012.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 00-.79-.05 6.34 6.34 0 00-6.34 6.34 6.34 6.34 0 006.34 6.34 6.34 6.34 0 006.33-6.34V8.69a8.18 8.18 0 004.78 1.52V6.75a4.85 4.85 0 01-1.01-.06z"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <!-- Contato -->
                    <div>
                        <h4 class="font-black uppercase text-white text-sm tracking-widest mb-4"
                            style="font-family:'Passion One',sans-serif">Contato</h4>
                        <ul class="space-y-2 text-sm text-white/80 font-semibold uppercase">
                            <li>(85) 99249-2148</li>
                            <li>contato@comanutz.com.br</li>
                        </ul>
                    </div>

                    <!-- Sobre -->
                    <div>
                        <h4 class="font-black uppercase text-white text-sm tracking-widest mb-4"
                            style="font-family:'Passion One',sans-serif">Sobre</h4>
                        <ul class="space-y-2 text-sm font-semibold uppercase">
                            <li><button @click="navTo('#quem-somos')" class="text-white/80 hover:text-white transition-colors">Quem Somos</button></li>
                            <li><button @click="navTo('#produtos1')" class="text-white/80 hover:text-white transition-colors">Nossos Produtos</button></li>
                            <li><a href="#" class="text-white/80 hover:text-white transition-colors">Sustentabilidade</a></li>
                        </ul>
                    </div>

                    <!-- Ajuda e Suporte -->
                    <div>
                        <h4 class="font-black uppercase text-white text-sm tracking-widest mb-4"
                            style="font-family:'Passion One',sans-serif">Ajuda e Suporte</h4>
                        <ul class="space-y-2 text-sm font-semibold uppercase">
                            <li><button @click="navTo('#contato')" class="text-white/80 hover:text-white transition-colors">Fale Conosco</button></li>
                            <li><a href="#" class="text-white/80 hover:text-white transition-colors">FAQs</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Bottom bar -->
            <div style="background:#8f1118">
                <div class="max-w-6xl mx-auto px-6 py-4 flex flex-col md:flex-row justify-between items-center gap-2 text-xs text-white/70 font-semibold uppercase tracking-wide">
                    <span>&copy; 2026 MadNutz. Todos os Direitos Reservados.</span>
                    <div class="flex gap-4">
                        <a href="#" class="hover:text-white transition-colors">Política de Privacidade</a>
                        <span class="text-white/30">·</span>
                        <a href="#" class="hover:text-white transition-colors">Termos de Serviço</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>
