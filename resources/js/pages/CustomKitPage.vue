<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';
import { useCart } from '../stores/cart.js';

const router = useRouter();
const route  = useRoute();
const { items, addCustomKit, updateCustomKit } = useCart();

const editMode    = computed(() => route.query.edit === 'true');
const cartItemKey = computed(() => route.query.key ?? '');
const cartItem    = computed(() => items.value.find(i => i.key === cartItemKey.value));

const products  = ref([]);
const selection = ref({});
const kitQty    = ref(1);
const loading   = ref(true);
const added     = ref(false);

onMounted(async () => {
    try {
        const res = await axios.get('/api/products');
        products.value = res.data.data ?? res.data;
        // Pré-preenche seleção se estiver em modo edição
        if (editMode.value && cartItem.value?.custom_items) {
            const pre = {};
            cartItem.value.custom_items.forEach(ci => { pre[ci.product_id] = ci.quantity; });
            selection.value = pre;
            kitQty.value    = cartItem.value.qty ?? 1;
        }
    } catch {}
    finally { loading.value = false; }
});

function qty(id) { return selection.value[id] ?? 0; }

function increment(id) {
    selection.value = { ...selection.value, [id]: (selection.value[id] ?? 0) + 1 };
}
function decrement(id) {
    const current = selection.value[id] ?? 0;
    if (current <= 0) return;
    const next = { ...selection.value, [id]: current - 1 };
    if (next[id] === 0) delete next[id];
    selection.value = next;
}

const totalItems = computed(() =>
    Object.values(selection.value).reduce((s, q) => s + q, 0)
);
const totalPrice = computed(() =>
    products.value.reduce((sum, p) => sum + (qty(p.id) > 0 ? p.price * qty(p.id) : 0), 0)
);

const fmt = v => Number(v).toFixed(2).replace('.', ',');

function handleSubmit() {
    if (totalItems.value < 2 || added.value) return;
    const customItems = products.value
        .filter(p => qty(p.id) > 0)
        .map(p => ({ product_id: p.id, name: p.name, quantity: qty(p.id), price: p.price }));

    if (editMode.value && cartItemKey.value) {
        updateCustomKit(cartItemKey.value, customItems, totalPrice.value);
    } else {
        addCustomKit(customItems, totalPrice.value, kitQty.value);
    }

    added.value = true;
    selection.value = {};
    setTimeout(() => {
        added.value = false;
        router.push({ name: 'cart' });
    }, 800);
}
</script>

<template>
    <div class="min-h-screen" style="background:#131313;color:#e5e2e1;">
        <!-- Psychedelic background -->
        <div class="pointer-events-none fixed inset-0"
             style="z-index:0;background:radial-gradient(circle at center,rgba(255,0,60,0.15) 0%,rgba(0,0,0,1) 70%);" />

        <!-- Page body -->
        <main class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pt-8 pb-20
                     grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-start">

            <!-- ── LEFT: Graphic info (sticky) ── -->
            <div class="relative w-full aspect-square rounded-xl overflow-hidden lg:sticky lg:top-24"
                 style="background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);">

                <div class="absolute inset-0 flex flex-col items-center justify-center gap-6 p-8"
                     style="background:linear-gradient(135deg,rgba(255,82,92,0.08),rgba(0,0,0,0));">

                    <div class="text-center">
                        <div class="font-black uppercase tracking-tighter text-transparent bg-clip-text block"
                             style="font-family:'Passion One',sans-serif;
                                    font-size:clamp(3.5rem,8vw,5.5rem);
                                    background-image:linear-gradient(to right,#FF003C,#ff8c00);
                                    -webkit-background-clip:text;background-clip:text;line-height:0.9;">
                            MONTE
                        </div>
                        <div class="font-black uppercase tracking-tighter text-transparent bg-clip-text block"
                             style="font-family:'Passion One',sans-serif;
                                    font-size:clamp(3.5rem,8vw,5.5rem);
                                    background-image:linear-gradient(to right,#ff8c00,#e6eb00);
                                    -webkit-background-clip:text;background-clip:text;line-height:0.9;">
                            SEU KIT
                        </div>
                    </div>

                    <div class="text-center" style="color:rgba(255,255,255,0.4);">
                        <p class="text-sm font-bold uppercase tracking-widest">Mínimo: 2 produtos</p>
                        <p class="text-sm mt-1">Combine do seu jeito, sem limites</p>
                    </div>

                    <!-- Contador de selecionados -->
                    <Transition name="fade">
                        <div v-if="totalItems > 0"
                             class="px-6 py-3 rounded-full text-sm font-black uppercase tracking-widest"
                             style="background:rgba(255,0,60,0.15);border:1px solid rgba(255,0,60,0.4);color:#ff525c;">
                            {{ totalItems }} {{ totalItems === 1 ? 'produto selecionado' : 'produtos selecionados' }}
                        </div>
                    </Transition>

                    <!-- Total price -->
                    <Transition name="fade">
                        <div v-if="totalItems >= 2" class="text-center">
                            <p class="text-xs uppercase tracking-widest font-bold" style="color:rgba(255,255,255,0.3);">Total</p>
                            <p class="text-3xl font-black" style="color:#e6eb00;">R$ {{ fmt(totalPrice) }}</p>
                        </div>
                    </Transition>
                </div>

                <div class="absolute inset-0 rounded-xl pointer-events-none"
                     style="box-shadow:inset 0 0 0 1px rgba(255,255,255,0.1);" />
            </div>

            <!-- ── RIGHT: Config ── -->
            <div class="flex flex-col gap-8 w-full">

                <!-- Header -->
                <div class="flex flex-col gap-2">
                    <button @click="router.back()"
                            class="flex items-center gap-2 w-fit mb-1 text-white/40 hover:text-white transition-colors">
                        <i class="pi pi-arrow-left" style="font-size:11px;" />
                        <span class="text-xs font-bold uppercase tracking-widest">Voltar</span>
                    </button>
                    <h1 class="font-black uppercase leading-none tracking-tighter text-transparent bg-clip-text"
                        style="font-family:'Passion One',sans-serif;
                               font-size:clamp(2.5rem,6vw,4rem);
                               background-image:linear-gradient(to right,#fff,#c6c6c7);
                               -webkit-background-clip:text;background-clip:text;">
                        Kit Personalizado
                    </h1>
                    <p style="color:rgba(255,255,255,0.5);font-size:15px;line-height:1.6;">
                        Escolha qualquer combinação de produtos. Mínimo de 2 itens no total.
                    </p>
                </div>

                <!-- Step 1: Quantidade -->
                <div class="flex flex-col gap-3">
                    <h2 class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-white">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px]"
                              style="background:rgba(255,255,255,0.1);">1</span>
                        Quantidade
                    </h2>
                    <div class="grid grid-cols-3 gap-1.5">
                        <label v-for="n in [1, 2, 3]" :key="n" class="cursor-pointer relative">
                            <input type="radio" :value="n" v-model="kitQty" class="sr-only" />
                            <div class="rounded-lg border-2 flex flex-col items-center justify-center gap-1 py-4 transition-all duration-300"
                                 :style="kitQty === n
                                     ? 'background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border-color:#FF003C;box-shadow:0 0 15px rgba(255,0,60,0.6)'
                                     : 'background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border-color:rgba(255,255,255,0.1)'">
                                <span class="text-2xl font-bold text-white">{{ n }}</span>
                                <span class="text-xs font-bold uppercase" style="color:rgba(255,255,255,0.5);">
                                    {{ n === 1 ? 'Kit' : 'Kits' }}
                                </span>
                            </div>
                            <div v-if="kitQty === n" class="absolute top-2 right-2 text-xs" style="color:#e6eb00;">
                                <i class="pi pi-check-circle" />
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 2: Produtos -->
                <div class="flex flex-col gap-3">
                    <h2 class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-white">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px]"
                              style="background:rgba(255,255,255,0.1);">2</span>
                        Escolher Produtos
                    </h2>

                    <!-- Regra mínima -->
                    <div class="rounded-lg px-4 py-2.5 text-xs font-bold"
                         style="background:rgba(255,140,0,0.1);border:1px solid rgba(255,140,0,0.25);color:rgba(255,180,60,0.9);">
                        ⚡ Mínimo de 2 produtos · Sem limite máximo · Preço calculado automaticamente
                    </div>

                    <div v-if="loading" class="flex items-center justify-center py-8">
                        <div class="w-8 h-8 rounded-full border-2 animate-spin"
                             style="border-color:#FF003C;border-top-color:transparent;" />
                    </div>

                    <div v-else class="flex flex-col gap-2">
                        <div v-for="product in products" :key="product.id"
                             class="rounded-lg flex items-center justify-between gap-4 cursor-pointer transition-all duration-300"
                             style="padding:12px;backdrop-filter:blur(20px);"
                             :style="qty(product.id) > 0
                                 ? 'background:rgba(18,18,18,0.7);border:1px solid rgba(255,82,92,0.4);box-shadow:0 0 12px rgba(255,0,60,0.1)'
                                 : 'background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.1)'"
                             @click="qty(product.id) === 0 ? increment(product.id) : null">

                            <div class="flex items-center gap-3">
                                <div class="w-14 h-14 rounded overflow-hidden flex-shrink-0"
                                     style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
                                    <img v-if="product.thumbnail"
                                         :src="product.thumbnail" :alt="product.name"
                                         class="w-full h-full object-contain p-1" />
                                </div>
                                <div>
                                    <div class="flex items-baseline gap-2 flex-wrap">
                                        <h3 class="font-bold text-white text-sm leading-tight">{{ product.name }}</h3>
                                        <span class="text-xs" style="color:rgba(255,255,255,0.4);">{{ product.brand }} · {{ product.size }}</span>
                                    </div>
                                    <p class="font-bold mt-1" style="color:#ff525c;font-size:15px;">
                                        R$ {{ fmt(product.price) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Contador +/- -->
                            <div class="flex items-center gap-2 flex-shrink-0" @click.stop>
                                <button v-if="qty(product.id) > 0"
                                        class="w-8 h-8 rounded-full border flex items-center justify-center font-black text-base transition-all"
                                        style="border-color:rgba(255,0,60,0.4);color:#ff525c;"
                                        @click="decrement(product.id)">
                                    −
                                </button>
                                <span v-if="qty(product.id) > 0"
                                      class="w-6 text-center font-black text-sm"
                                      style="color:#e6eb00;">
                                    {{ qty(product.id) }}
                                </span>
                                <button class="w-10 h-10 rounded-full border flex items-center justify-center transition-all hover:bg-white hover:text-black"
                                        style="border-color:rgba(255,255,255,0.2);color:#fff;"
                                        @click="increment(product.id)">
                                    <i class="pi pi-plus" style="font-size:14px;" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Aviso mínimo -->
                <p v-if="totalItems > 0 && totalItems < 2"
                   class="text-center text-xs font-bold uppercase tracking-widest"
                   style="color:rgba(255,223,0,0.6);">
                    Adicione pelo menos mais {{ 2 - totalItems }} produto para continuar
                </p>

                <!-- CTA Button -->
                <button class="w-full py-5 font-black uppercase tracking-widest text-lg rounded-lg transition-all duration-300 flex items-center justify-center gap-2"
                        style="font-family:'Passion One',sans-serif;"
                        :class="(!added && totalItems >= 2) ? 'hover:ring-2 hover:ring-[#e6eb00] hover:shadow-[0_0_20px_rgba(230,235,0,0.5)]' : ''"
                        :style="added
                            ? 'background:#4ade80;color:#fff'
                            : totalItems >= 2
                                ? 'background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4)'
                                : 'background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.2);cursor:not-allowed'"
                        :disabled="totalItems < 2 || added"
                        @click="handleSubmit">
                    <i v-if="added" class="pi pi-check" />
                    <span>{{ added ? (editMode ? 'SALVO!' : 'ADICIONADO!') : totalItems >= 2 ? (editMode ? 'SALVAR ALTERAÇÕES' : 'ADICIONAR AO CARRINHO') : 'SELECIONE PELO MENOS 2 PRODUTOS' }}</span>
                </button>
            </div>
        </main>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s ease, transform 0.3s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; transform: translateY(6px); }
</style>
