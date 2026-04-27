<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useCart } from '../stores/cart.js';
import { useCustomer } from '../stores/customer.js';
import { b4youEnabled, createCheckout } from '../services/b4you.js';

const router = useRouter();
const { items, total, clear } = useCart();
const { customer, token } = useCustomer();

const shipping    = computed(() => total.value >= 199 ? 0 : 19.90);
const orderTotal  = computed(() => total.value + shipping.value);

const form = ref({
    customer_name:  '',
    customer_email: '',
    customer_phone: '',
    customer_address: { street: '', city: '', state: '', zip: '' },
});

onMounted(() => {
    if (customer.value) {
        form.value.customer_name  = customer.value.name  ?? '';
        form.value.customer_email = customer.value.email ?? '';
        form.value.customer_phone = customer.value.phone ?? '';
    }
});

const errors    = ref({});
const loading   = ref(false);
const orderDone = ref(null);

function fmt(val) {
    return Number(val).toFixed(2).replace('.', ',');
}

async function submit() {
    errors.value = {};
    loading.value = true;
    try {
        const payload = {
            ...form.value,
            items: items.value.map(i => ({
                name:         i.name,
                price:        i.price,
                qty:          i.qty,
                product_id:   i.product_id ?? null,
                is_custom:    i.is_custom ?? false,
                custom_items: i.custom_items ?? null,
            })),
        };

        // 1. Cria o pedido no nosso backend
        const headers = token.value ? { Authorization: `Bearer ${token.value}` } : {};
        const { data: order } = await axios.post('/api/orders', payload, { headers });

        if (b4youEnabled) {
            // 2a. Com B4you: redireciona para o checkout de pagamento
            const checkoutUrl = await createCheckout(order, {
                name:  form.value.customer_name,
                email: form.value.customer_email,
                phone: form.value.customer_phone,
            }, items.value);
            clear();
            window.location.href = checkoutUrl;
        } else {
            // 2b. Sem B4you: confirma direto (ex: ambiente de dev)
            clear();
            orderDone.value = order;
        }
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors ?? {};
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="max-w-5xl mx-auto px-4 py-8">

        <!-- Confirmação -->
        <div v-if="orderDone" class="text-center py-20">
            <div class="text-7xl mb-6">🎉</div>
            <h1 class="font-black text-4xl uppercase mb-3 text-mn-black" style="font-family:'Passion One',sans-serif">
                PEDIDO CONFIRMADO!
            </h1>
            <p class="text-mn-gray text-lg mb-2">Número do pedido:</p>
            <p class="font-black text-3xl text-mn-red mb-6" style="font-family:'Passion One',sans-serif">
                {{ orderDone.order_number }}
            </p>
            <p class="text-mn-gray mb-8">Total: <strong>R$ {{ fmt(orderDone.total) }}</strong></p>
            <p class="text-mn-gray/70 text-sm mb-8">Você receberá uma confirmação por e-mail em breve.</p>
            <router-link to="/" class="btn-red inline-block text-base px-10 py-4">
                VOLTAR À LOJA
            </router-link>
        </div>

        <!-- Checkout form -->
        <template v-else>
            <h1 class="font-black text-4xl md:text-5xl uppercase mb-8 text-mn-black" style="font-family:'Passion One',sans-serif">
                FINALIZAR COMPRA
            </h1>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

                <!-- Form -->
                <form class="md:col-span-2 space-y-5" @submit.prevent="submit">
                    <div class="bg-white rounded-2xl shadow p-6 space-y-4">
                        <h2 class="font-black text-lg uppercase text-mn-black" style="font-family:'Passion One',sans-serif">Dados pessoais</h2>

                        <div>
                            <label class="block text-sm font-semibold mb-1">Nome completo *</label>
                            <input v-model="form.customer_name" type="text" placeholder="Seu nome"
                                class="w-full border rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-mn-red text-sm"
                                :class="errors.customer_name ? 'border-red-400' : 'border-gray-200'" />
                            <p v-if="errors.customer_name" class="text-red-500 text-xs mt-1">{{ errors.customer_name[0] }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1">E-mail *</label>
                                <input v-model="form.customer_email" type="email" placeholder="seu@email.com"
                                    class="w-full border rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-mn-red text-sm"
                                    :class="errors.customer_email ? 'border-red-400' : 'border-gray-200'" />
                                <p v-if="errors.customer_email" class="text-red-500 text-xs mt-1">{{ errors.customer_email[0] }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">WhatsApp *</label>
                                <input v-model="form.customer_phone" type="text" placeholder="(11) 99999-9999"
                                    class="w-full border rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-mn-red text-sm"
                                    :class="errors.customer_phone ? 'border-red-400' : 'border-gray-200'" />
                                <p v-if="errors.customer_phone" class="text-red-500 text-xs mt-1">{{ errors.customer_phone[0] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 space-y-4">
                        <h2 class="font-black text-lg uppercase text-mn-black" style="font-family:'Passion One',sans-serif">Endereço de entrega</h2>

                        <div>
                            <label class="block text-sm font-semibold mb-1">CEP</label>
                            <input v-model="form.customer_address.zip" type="text" placeholder="00000-000"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-mn-red text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1">Rua / Endereço</label>
                            <input v-model="form.customer_address.street" type="text" placeholder="Rua, número, complemento"
                                class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-mn-red text-sm" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold mb-1">Cidade</label>
                                <input v-model="form.customer_address.city" type="text" placeholder="Cidade"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-mn-red text-sm" />
                            </div>
                            <div>
                                <label class="block text-sm font-semibold mb-1">Estado</label>
                                <input v-model="form.customer_address.state" type="text" placeholder="SP"
                                    class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:ring-2 focus:ring-mn-red text-sm" />
                            </div>
                        </div>
                    </div>

                    <button type="submit" :disabled="loading"
                        class="btn-red w-full py-4 text-base disabled:opacity-60 disabled:cursor-not-allowed">
                        {{ loading ? 'PROCESSANDO...' : 'CONFIRMAR PEDIDO 🔒' }}
                    </button>
                </form>

                <!-- Resumo -->
                <div class="bg-mn-black text-white rounded-2xl p-6 flex flex-col gap-4 h-fit sticky top-24">
                    <h2 class="font-black text-xl uppercase border-b border-white/20 pb-3" style="font-family:'Passion One',sans-serif">Resumo</h2>

                    <div class="space-y-2">
                        <div v-for="item in items" :key="item.key" class="flex justify-between text-sm text-white/80">
                            <span class="truncate pr-2">{{ item.qty }}× {{ item.name }}</span>
                            <span class="font-bold flex-shrink-0">R$ {{ fmt(item.price * item.qty) }}</span>
                        </div>
                    </div>

                    <div class="border-t border-white/20 pt-3 space-y-1 text-sm">
                        <div class="flex justify-between text-white/70">
                            <span>Subtotal</span>
                            <span>R$ {{ fmt(total) }}</span>
                        </div>
                        <div class="flex justify-between font-semibold"
                            :class="shipping === 0 ? 'text-mn-yellow' : 'text-white/70'">
                            <span>Frete</span>
                            <span>{{ shipping === 0 ? 'Grátis' : `R$ ${fmt(shipping)}` }}</span>
                        </div>
                    </div>

                    <div class="border-t border-white/20 pt-3 flex justify-between items-end">
                        <span class="font-black text-lg uppercase">Total</span>
                        <span class="font-black text-3xl text-mn-yellow">R$ {{ fmt(orderTotal) }}</span>
                    </div>

                    <router-link to="/carrinho" class="text-center text-xs text-white/40 hover:text-white/70 underline">
                        ← Voltar ao carrinho
                    </router-link>
                </div>
            </div>
        </template>
    </div>
</template>
