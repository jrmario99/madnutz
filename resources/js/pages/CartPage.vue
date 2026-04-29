<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useCart } from '../stores/cart.js';
import CheckoutDrawer from '../components/CheckoutDrawer.vue';

const router = useRouter();
const { items, total, coupon, discount, shipping, freeShipping, orderTotal, remove, updateQty, applyCoupon, removeCoupon } = useCart();

const couponCode  = ref('');
const couponError = ref('');
const couponLoading = ref(false);
const drawerOpen  = ref(false);

const fmt = v => Number(v).toFixed(2).replace('.', ',');

async function handleApplyCoupon() {
    if (!couponCode.value.trim()) return;
    couponError.value = '';
    couponLoading.value = true;
    try {
        await applyCoupon(couponCode.value.trim());
        couponCode.value = '';
    } catch (e) {
        couponError.value = e.message;
    } finally {
        couponLoading.value = false;
    }
}

function decQty(item) { if (item.qty > 1) updateQty(item.key, item.qty - 1); }
function incQty(item) { updateQty(item.key, item.qty + 1); }

function openEditModal(item) {
    const slug = item.kit_slug ?? item.kit_id;
    if (!slug) return;
    router.push({ name: 'kit.selector', params: { slug }, query: { edit: 'true', key: item.key } });
}

function openEditCustom(item) {
    router.push({ name: 'custom.kit', query: { edit: 'true', key: item.key } });
}

function hasSelections(item) {
    return item.is_kit && item.kit_selections?.length > 0;
}

function goToProducts() {
    router.push({ path: '/', hash: '#produtos1' });
}
</script>

<template>
    <div class="min-h-screen relative" style="background:#131313;color:#e5e2e1;">

        <!-- Atmospheric glow -->
        <div class="pointer-events-none fixed inset-0"
             style="z-index:0;background:radial-gradient(ellipse 70% 50% at 70% 20%, rgba(255,0,60,0.1) 0%, transparent 60%);" />

        <div class="relative z-10 max-w-6xl mx-auto px-4 py-10">

            <!-- Header -->
            <div class="flex items-center gap-3 mb-8">
                <router-link to="/"
                    class="flex items-center gap-2 text-xs font-black uppercase tracking-widest transition-colors"
                    style="color:rgba(255,255,255,0.35);"
                    @mouseenter="$event.currentTarget.style.color='#fff'"
                    @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.35)'">
                    <i class="pi pi-arrow-left" style="font-size:11px;" />
                    Continuar comprando
                </router-link>
                <span style="color:rgba(255,255,255,0.1);">|</span>
                <h1 class="font-black uppercase tracking-tight"
                    style="font-family:'Passion One',sans-serif;font-size:1.6rem;color:#fff;">
                    Meu Carrinho
                    <span v-if="items.length"
                          class="ml-2 font-bold text-base"
                          style="color:rgba(255,255,255,0.3);">
                        ({{ items.length }} {{ items.length === 1 ? 'item' : 'itens' }})
                    </span>
                </h1>
            </div>

            <!-- ── VAZIO ── -->
            <div v-if="items.length === 0"
                 class="flex flex-col items-center justify-center py-28 gap-6 text-center rounded-3xl"
                 style="background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.07);">
                <div class="w-24 h-24 rounded-full flex items-center justify-center"
                     style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                    <i class="pi pi-shopping-cart" style="font-size:36px;color:rgba(255,255,255,0.12);" />
                </div>
                <div>
                    <p class="font-black text-3xl uppercase mb-2 text-white"
                       style="font-family:'Passion One',sans-serif;">Carrinho vazio</p>
                    <p style="color:rgba(255,255,255,0.4);">Escolha um kit incrível e volte aqui!</p>
                </div>
                <button @click="goToProducts"
                    class="px-8 py-3 rounded-xl font-black uppercase tracking-widest text-sm transition-all"
                    style="background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                    VER KITS
                </button>
            </div>

            <!-- ── CONTEÚDO ── -->
            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-5">

                <!-- Coluna de itens -->
                <div class="lg:col-span-2 flex flex-col gap-3">

                    <div v-for="item in items" :key="item.key"
                         class="rounded-2xl overflow-hidden"
                         style="background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.07);">

                        <div class="flex gap-4 p-4 items-start">

                            <!-- Thumbnail -->
                            <div class="w-20 h-20 rounded-xl flex items-center justify-center flex-shrink-0 overflow-hidden"
                                 style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
                                <img v-if="item.thumbnail"
                                     :src="item.thumbnail" :alt="item.name"
                                     class="w-full h-full object-contain p-1.5" />
                                <i v-else-if="item.is_kit || item.is_custom"
                                   class="pi pi-box" style="font-size:24px;color:rgba(255,255,255,0.2);" />
                                <i v-else class="pi pi-circle-fill" style="font-size:20px;color:rgba(255,255,255,0.15);" />
                            </div>

                            <!-- Info -->
                            <div class="flex-1 min-w-0">

                                <!-- Name row -->
                                <div class="flex items-start justify-between gap-2 mb-1">
                                    <div class="min-w-0">
                                        <p class="uppercase leading-tight font-semibold truncate"
                                           style="font-family:'Passion One',sans-serif;font-size:1.3rem;color:#ff525c;">
                                            {{ item.name }}
                                        </p>
                                        <p v-if="item.variant"
                                           class="text-xs font-bold mt-0.5"
                                           style="color:rgba(255,255,255,0.4);">
                                            {{ item.variant }}
                                        </p>
                                        <p v-if="item.brand && !item.is_kit && !item.is_custom"
                                           class="text-xs font-bold"
                                           style="color:rgba(255,255,255,0.3);">
                                            {{ item.brand }}
                                        </p>
                                    </div>
                                    <!-- Remove (desktop) -->
                                    <button @click="remove(item.key)"
                                            class="hidden sm:flex w-7 h-7 rounded-full items-center justify-center flex-shrink-0 transition-all"
                                            style="color:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.08);"
                                            @mouseenter="$event.currentTarget.style.cssText += ';color:#ff525c;border-color:rgba(255,82,92,0.4);background:rgba(255,0,60,0.08)'"
                                            @mouseleave="$event.currentTarget.style.cssText = 'color:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.08);'">
                                        <i class="pi pi-times" style="font-size:10px;" />
                                    </button>
                                </div>

                                <!-- Kit selections chips -->
                                <div v-if="hasSelections(item)" class="flex flex-wrap gap-1.5 mt-2 mb-3">
                                    <span v-for="ks in item.kit_selections"
                                          :key="ks.product_id + '-' + ks.slotIdx"
                                          class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-bold"
                                          style="background:rgba(255,0,60,0.08);color:rgba(255,82,92,0.9);border:1px solid rgba(255,0,60,0.2);">
                                        <span class="w-4 h-4 rounded-full flex items-center justify-center font-black text-[9px] flex-shrink-0"
                                              style="background:#FF003C;color:#fff;">{{ ks.qty }}</span>
                                        {{ ks.name }}
                                        <span class="text-[10px] font-bold px-1.5 py-0.5 rounded"
                                              style="background:rgba(230,235,0,0.12);color:#e6eb00;">{{ ks.size }}</span>
                                    </span>
                                </div>

                                <!-- Custom / legacy kit products — mesmo estilo dos chips de sabor -->
                                <div v-else-if="item.kit_products?.length || item.custom_items?.length"
                                     class="flex flex-wrap gap-1.5 mt-2 mb-3">
                                    <span v-for="kp in (item.custom_items ?? item.kit_products)"
                                          :key="kp.id ?? kp.product_id"
                                          class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-bold"
                                          style="background:rgba(255,0,60,0.08);color:rgba(255,82,92,0.9);border:1px solid rgba(255,0,60,0.2);">
                                        <span class="w-4 h-4 rounded-full flex items-center justify-center font-black text-[9px] flex-shrink-0"
                                              style="background:#FF003C;color:#fff;">{{ kp.pivot?.quantity ?? kp.quantity ?? 1 }}</span>
                                        {{ kp.name }}
                                    </span>
                                </div>

                                <!-- Price + Qty + Subtotal row -->
                                <div class="flex items-center gap-3 mt-3 flex-wrap">
                                    <!-- Unit price -->
                                    <span class="text-xs font-bold"
                                          style="color:rgba(255,255,255,0.3);">
                                        R$ {{ fmt(item.price) }} / un
                                    </span>

                                    <!-- Stepper -->
                                    <div class="flex items-center rounded-xl overflow-hidden ml-auto sm:ml-0"
                                         style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);">
                                        <button class="w-9 h-9 flex items-center justify-center font-black text-lg transition-colors disabled:opacity-25"
                                                style="color:rgba(255,255,255,0.5);"
                                                :disabled="item.qty <= 1"
                                                @click="decQty(item)"
                                                @mouseenter="$event.currentTarget.style.color='#fff'"
                                                @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.5)'">
                                            −
                                        </button>
                                        <span class="w-8 text-center text-sm font-black"
                                              style="color:#e6eb00;">{{ item.qty }}</span>
                                        <button class="w-9 h-9 flex items-center justify-center font-black text-lg transition-colors"
                                                style="color:rgba(255,255,255,0.5);"
                                                @click="incQty(item)"
                                                @mouseenter="$event.currentTarget.style.color='#fff'"
                                                @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.5)'">
                                            +
                                        </button>
                                    </div>

                                    <!-- Subtotal -->
                                    <span class="font-black text-base ml-auto"
                                          style="color:#fff;">
                                        R$ {{ fmt(item.price * item.qty) }}
                                    </span>

                                    <!-- Remove (mobile) -->
                                    <button @click="remove(item.key)"
                                            class="sm:hidden w-7 h-7 rounded-full flex items-center justify-center transition-all flex-shrink-0"
                                            style="color:rgba(255,255,255,0.2);border:1px solid rgba(255,255,255,0.08);">
                                        <i class="pi pi-times" style="font-size:10px;" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Alterar sabores / Editar kit personalizado -->
                        <div v-if="hasSelections(item) || item.is_custom"
                             style="border-top:1px solid rgba(255,255,255,0.05);">
                            <button class="w-full flex items-center justify-center gap-2 py-3 text-xs font-black uppercase tracking-widest transition-all"
                                    style="color:rgba(255,82,92,0.55);"
                                    @click="hasSelections(item) ? openEditModal(item) : openEditCustom(item)"
                                    @mouseenter="$event.currentTarget.style.color='#ff525c';$event.currentTarget.style.background='rgba(255,0,60,0.06)'"
                                    @mouseleave="$event.currentTarget.style.color='rgba(255,82,92,0.55)';$event.currentTarget.style.background='transparent'">
                                <i class="pi pi-pencil" style="font-size:11px;" />
                                {{ hasSelections(item) ? 'Alterar sabores do kit' : 'Editar kit personalizado' }}
                            </button>
                        </div>
                    </div>

                    <!-- Cupom -->
                    <div class="rounded-2xl p-4 flex flex-col gap-3"
                         style="background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.07);">

                        <!-- Cupom aplicado -->
                        <div v-if="coupon"
                             class="flex items-center justify-between gap-3 px-4 py-3 rounded-xl"
                             style="background:rgba(74,222,128,0.06);border:1px solid rgba(74,222,128,0.2);">
                            <div class="flex items-center gap-2">
                                <i class="pi pi-check-circle text-sm" style="color:rgba(74,222,128,0.9);" />
                                <span class="text-sm font-black uppercase tracking-widest" style="color:rgba(74,222,128,0.9);">
                                    {{ coupon.code }}
                                </span>
                                <span class="text-xs font-bold" style="color:rgba(74,222,128,0.6);">
                                    − R$ {{ fmt(discount) }}
                                </span>
                            </div>
                            <button @click="removeCoupon"
                                    class="w-6 h-6 flex items-center justify-center rounded-full transition-all"
                                    style="color:rgba(255,255,255,0.3);border:1px solid rgba(255,255,255,0.1);"
                                    @mouseenter="$event.currentTarget.style.color='#ff525c';$event.currentTarget.style.borderColor='rgba(255,82,92,0.4)'"
                                    @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.3)';$event.currentTarget.style.borderColor='rgba(255,255,255,0.1)'">
                                <i class="pi pi-times" style="font-size:9px;" />
                            </button>
                        </div>

                        <!-- Input de cupom -->
                        <div v-else class="flex flex-col sm:flex-row gap-3">
                            <div class="relative flex-1">
                                <i class="pi pi-tag absolute left-4 top-1/2 -translate-y-1/2 text-xs"
                                   style="color:rgba(255,255,255,0.25);" />
                                <input v-model="couponCode" type="text"
                                       placeholder="Código de cupom"
                                       class="w-full pl-10 pr-4 py-3 rounded-xl text-sm font-bold uppercase tracking-widest outline-none transition-all"
                                       style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);color:#fff;"
                                       @keyup.enter="handleApplyCoupon"
                                       @focus="$event.target.style.borderColor='rgba(255,0,60,0.45)'"
                                       @blur="$event.target.style.borderColor='rgba(255,255,255,0.1)'" />
                            </div>
                            <button class="font-black uppercase text-sm tracking-widest px-8 py-3 rounded-xl transition-all whitespace-nowrap disabled:opacity-50"
                                    style="background:rgba(255,0,60,0.15);color:#ff525c;border:1px solid rgba(255,0,60,0.3);"
                                    :disabled="couponLoading"
                                    @click="handleApplyCoupon"
                                    @mouseenter="$event.currentTarget.style.background='rgba(255,0,60,0.25)'"
                                    @mouseleave="$event.currentTarget.style.background='rgba(255,0,60,0.15)'">
                                {{ couponLoading ? '...' : 'APLICAR' }}
                            </button>
                        </div>

                        <p v-if="couponError" class="text-xs font-bold" style="color:#ff6b6b;">
                            {{ couponError }}
                        </p>
                    </div>
                </div>

                <!-- ── SIDEBAR RESUMO ── -->
                <div>
                    <div class="rounded-2xl p-5 flex flex-col gap-4 sticky top-20"
                         style="background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.07);">

                        <h2 class="font-black uppercase tracking-widest text-sm text-white">
                            Resumo do pedido
                        </h2>

                        <!-- Frete grátis badge -->
                        <div class="flex items-center gap-2 text-xs font-bold"
                             :style="freeShipping ? 'color:rgba(74,222,128,0.9)' : 'color:rgba(255,255,255,0.4)'">
                            <i :class="freeShipping ? 'pi pi-check-circle' : 'pi pi-truck'" style="font-size:13px;" />
                            <span>{{ freeShipping ? 'Frete grátis nos seus kits!' : 'Frete: R$ 9,99' }}</span>
                        </div>

                        <div style="border-top:1px solid rgba(255,255,255,0.06);" />

                        <!-- Subtotal -->
                        <div class="flex justify-between items-center text-sm">
                            <span style="color:rgba(255,255,255,0.45);">Subtotal</span>
                            <span class="font-bold text-white">R$ {{ fmt(total) }}</span>
                        </div>

                        <!-- Frete -->
                        <div class="flex justify-between items-center text-sm">
                            <span style="color:rgba(255,255,255,0.45);">Frete</span>
                            <span class="font-bold"
                                  :style="freeShipping ? 'color:rgba(74,222,128,0.9)' : 'color:#fff'">
                                {{ freeShipping ? 'Grátis' : `R$ ${fmt(shipping)}` }}
                            </span>
                        </div>

                        <!-- Desconto do cupom -->
                        <div v-if="coupon" class="flex justify-between items-center text-sm">
                            <span style="color:rgba(74,222,128,0.7);">
                                <i class="pi pi-tag mr-1" style="font-size:11px;" />
                                {{ coupon.code }}
                            </span>
                            <span class="font-bold" style="color:rgba(74,222,128,0.9);">
                                − R$ {{ fmt(discount) }}
                            </span>
                        </div>

                        <div style="border-top:1px solid rgba(255,255,255,0.06);" />

                        <!-- Total -->
                        <div class="flex justify-between items-center">
                            <span class="font-black uppercase tracking-widest text-sm text-white">Total</span>
                            <span class="font-black text-2xl" style="color:#e6eb00;">
                                R$ {{ fmt(orderTotal) }}
                            </span>
                        </div>

                        <!-- CTA -->
                        <button
                            class="block w-full text-center font-black uppercase tracking-widest py-4 rounded-xl transition-all text-sm text-white"
                            style="background:#FF003C;box-shadow:0 4px 20px rgba(255,0,60,0.4);"
                            @mouseenter="$event.currentTarget.style.boxShadow='0 4px 32px rgba(255,0,60,0.65)'"
                            @mouseleave="$event.currentTarget.style.boxShadow='0 4px 20px rgba(255,0,60,0.4)'"
                            @click="drawerOpen = true">
                            FINALIZAR COMPRA →
                        </button>

                        <router-link to="/"
                            class="block w-full text-center text-xs font-bold uppercase tracking-widest py-1 transition-colors"
                            style="color:rgba(255,255,255,0.25);"
                            @mouseenter="$event.currentTarget.style.color='rgba(255,255,255,0.6)'"
                            @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.25)'">
                            ← Continuar comprando
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <CheckoutDrawer :open="drawerOpen" @close="drawerOpen = false" />
</template>
