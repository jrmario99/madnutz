<template>
    <Teleport to="body">
        <Transition name="overlay">
            <div v-if="modelValue"
                 class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4"
                 style="background:rgba(0,0,0,.6);backdrop-filter:blur(4px)"
                 @click.self="$emit('update:modelValue', false)">

                <Transition name="sheet">
                    <div v-if="modelValue"
                         class="bg-white w-full sm:max-w-2xl sm:rounded-3xl flex flex-col"
                         style="max-height:92vh">

                        <!-- Header -->
                        <div class="px-6 pt-6 pb-4 border-b border-gray-100 flex items-start justify-between flex-shrink-0">
                            <div>
                                <h2 class="text-3xl font-black uppercase text-mn-black"
                                    style="font-family:'Passion One',sans-serif">
                                    MONTE SEU KIT
                                </h2>
                                <p class="text-sm text-mn-gray mt-0.5">Escolha os produtos e quantidades</p>
                            </div>
                            <button @click="$emit('update:modelValue', false)"
                                    class="w-8 h-8 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors flex-shrink-0 mt-1">
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Regra mínimo -->
                        <div class="px-6 py-3 flex-shrink-0" style="background:#FFF7ED">
                            <p class="text-xs font-semibold" style="color:#92400E">
                                ⚡ Kit mínimo: <strong>2 produtos</strong>. Sem limite máximo.
                                Preço será calculado na finalização.
                            </p>
                        </div>

                        <!-- Produtos -->
                        <div class="flex-1 overflow-y-auto px-6 py-4 space-y-3">
                            <div v-for="product in products" :key="product.id"
                                 class="flex items-center gap-4 p-3 rounded-2xl border transition-all"
                                 :class="qty(product.id) > 0
                                     ? 'border-mn-red bg-red-50'
                                     : 'border-gray-100 hover:border-gray-200'">

                                <!-- Thumbnail -->
                                <div class="w-16 h-16 rounded-xl overflow-hidden bg-gray-100 flex-shrink-0">
                                    <img v-if="product.thumbnail" :src="product.thumbnail"
                                         :alt="product.name" class="w-full h-full object-cover" />
                                    <div v-else class="w-full h-full flex items-center justify-center">
                                        <svg class="w-6 h-6 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14"/>
                                        </svg>
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="font-black text-mn-black text-sm uppercase truncate"
                                       style="font-family:'Passion One',sans-serif">
                                        {{ product.name }}
                                    </p>
                                    <p class="text-xs text-mn-gray">{{ product.brand }} · {{ product.size }}</p>
                                </div>

                                <!-- Qty controls -->
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <button @click="decrement(product.id)"
                                            class="w-8 h-8 rounded-full border flex items-center justify-center font-bold text-lg transition-all"
                                            :class="qty(product.id) > 0
                                                ? 'border-mn-red text-mn-red hover:bg-mn-red hover:text-white'
                                                : 'border-gray-200 text-gray-300 cursor-not-allowed'"
                                            :disabled="qty(product.id) === 0">
                                        −
                                    </button>
                                    <span class="w-6 text-center font-black text-mn-black text-sm">
                                        {{ qty(product.id) }}
                                    </span>
                                    <button @click="increment(product.id)"
                                            class="w-8 h-8 rounded-full border border-mn-red text-mn-red hover:bg-mn-red hover:text-white flex items-center justify-center font-bold text-lg transition-all">
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 border-t border-gray-100 flex-shrink-0">
                            <!-- Resumo -->
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">Selecionados</p>
                                    <p class="text-2xl font-black text-mn-black" style="font-family:'Passion One',sans-serif">
                                        {{ totalItems }} {{ totalItems === 1 ? 'produto' : 'produtos' }}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold">Total</p>
                                    <p class="text-2xl font-black" style="font-family:'Passion One',sans-serif;color:#C82830">
                                        {{ totalItems > 0 ? `R$ ${totalPrice.toFixed(2).replace('.', ',')}` : '—' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Validation msg -->
                            <p v-if="totalItems > 0 && totalItems < 2"
                               class="text-xs font-semibold mb-3 text-center" style="color:#92400E">
                                Adicione pelo menos mais {{ 2 - totalItems }} produto para continuar.
                            </p>

                            <button
                                :disabled="totalItems < 2 || added"
                                class="w-full py-4 rounded-full font-black uppercase text-sm tracking-widest transition-all flex items-center justify-center gap-2"
                                :class="added
                                    ? 'bg-green-500 text-white'
                                    : totalItems >= 2
                                        ? 'text-white hover:opacity-90 active:scale-95'
                                        : 'bg-gray-100 text-gray-400 cursor-not-allowed'"
                                :style="!added && totalItems >= 2 ? 'background:#C82830' : ''"
                                @click="handleSubmit">
                                <svg v-if="added" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                <svg v-else-if="totalItems >= 2" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ added ? 'ADICIONADO! ✓' : totalItems >= 2 ? 'ADICIONAR AO CARRINHO' : 'SELECIONE PELO MENOS 2 PRODUTOS' }}
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import { useCart } from '../stores/cart.js';

defineProps({ modelValue: Boolean });
const emit = defineEmits(['update:modelValue']);

const { addCustomKit } = useCart();

const products  = ref([]);
const selection = ref({});
const added     = ref(false);

onMounted(async () => {
    try {
        const res = await axios.get('/api/products');
        products.value = res.data.data ?? res.data;
    } catch {}
});

function qty(id) {
    return selection.value[id] ?? 0;
}

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
    products.value.reduce((sum, p) => {
        const q = qty(p.id);
        return sum + (q > 0 ? p.price * q : 0);
    }, 0)
);

function handleSubmit() {
    const customItems = products.value
        .filter(p => qty(p.id) > 0)
        .map(p => ({ product_id: p.id, name: p.name, quantity: qty(p.id), price: p.price }));

    addCustomKit(customItems, totalPrice.value);

    added.value = true;
    selection.value = {};
    setTimeout(() => {
        added.value = false;
        emit('update:modelValue', false);
    }, 1200);
}
</script>

<style scoped>
.overlay-enter-active, .overlay-leave-active { transition: opacity .25s ease; }
.overlay-enter-from, .overlay-leave-to       { opacity: 0; }

.sheet-enter-active, .sheet-leave-active { transition: transform .3s cubic-bezier(.32,0,.67,0); }
.sheet-enter-from, .sheet-leave-to       { transform: translateY(40px); opacity: 0; }
@media (max-width: 639px) {
    .sheet-enter-from, .sheet-leave-to   { transform: translateY(100%); opacity: 1; }
}
</style>
