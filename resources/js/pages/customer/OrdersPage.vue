<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCustomer } from '../../stores/customer.js';
import { useCart } from '../../stores/cart.js';

const router = useRouter();
const { api } = useCustomer();
const { addKitByName } = useCart();

const orders  = ref([]);
const loading = ref(true);
const repeating = ref(null);

const statusLabel = {
    pending:   { label: 'Aguardando',  color: 'rgba(255,180,0,0.9)',   bg: 'rgba(255,180,0,0.1)',   border: 'rgba(255,180,0,0.3)' },
    paid:      { label: 'Pago',        color: 'rgba(74,222,128,0.9)',  bg: 'rgba(74,222,128,0.1)',  border: 'rgba(74,222,128,0.3)' },
    shipped:   { label: 'Enviado',     color: 'rgba(100,160,255,0.9)', bg: 'rgba(100,160,255,0.1)', border: 'rgba(100,160,255,0.3)' },
    cancelled: { label: 'Cancelado',   color: 'rgba(255,100,100,0.9)', bg: 'rgba(255,100,100,0.1)', border: 'rgba(255,100,100,0.3)' },
};

const fmt     = v => Number(v).toFixed(2).replace('.', ',');
const fmtDate = d => new Date(d).toLocaleDateString('pt-BR');

onMounted(async () => {
    try {
        const res = await api().get('/customer/orders');
        orders.value = res.data.data ?? res.data;
    } catch {}
    finally { loading.value = false; }
});

async function repeatOrder(order) {
    repeating.value = order.id;
    try {
        const res = await api().post(`/customer/orders/${order.id}/repeat`);
        res.data.items.forEach(item => {
            addKitByName(item.name, item.price, item.qty, item.is_custom, item.custom_items);
        });
        router.push({ name: 'cart' });
    } catch {}
    finally { repeating.value = null; }
}
</script>

<template>
    <div>
        <h1 class="text-3xl font-black uppercase tracking-tighter mb-6"
            style="font-family:'Passion One',sans-serif;color:#fff;">
            Meus Pedidos
        </h1>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="w-10 h-10 rounded-full border-2 animate-spin"
                 style="border-color:#FF003C;border-top-color:transparent;" />
        </div>

        <!-- Vazio -->
        <div v-else-if="orders.length === 0"
             class="flex flex-col items-center justify-center py-20 gap-4 text-center rounded-2xl"
             style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
            <i class="pi pi-shopping-bag" style="font-size:48px;color:rgba(255,255,255,0.15);" />
            <p class="text-lg font-bold text-white/50">Nenhum pedido ainda</p>
            <router-link to="/"
                         class="px-6 py-3 rounded-lg font-black uppercase tracking-widest text-sm transition-all"
                         style="background:#FF003C;color:#fff;">
                Ver produtos
            </router-link>
        </div>

        <!-- Lista -->
        <div v-else class="flex flex-col gap-4">
            <div v-for="order in orders" :key="order.id"
                 class="rounded-2xl overflow-hidden transition-all duration-200"
                 style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">

                <!-- Header do pedido -->
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4"
                     style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div class="flex items-center gap-3">
                        <span class="font-black text-white text-sm uppercase tracking-widest">
                            {{ order.number }}
                        </span>
                        <span class="text-xs px-2.5 py-1 rounded-full font-bold"
                              :style="`background:${statusLabel[order.status]?.bg ?? 'rgba(255,255,255,0.06)'};
                                       color:${statusLabel[order.status]?.color ?? '#fff'};
                                       border:1px solid ${statusLabel[order.status]?.border ?? 'rgba(255,255,255,0.1)'}`">
                            {{ statusLabel[order.status]?.label ?? order.status }}
                        </span>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-xs" style="color:rgba(255,255,255,0.4);">{{ fmtDate(order.created_at) }}</span>
                        <span class="font-black text-lg" style="color:#ff525c;">R$ {{ fmt(order.total) }}</span>
                    </div>
                </div>

                <!-- Itens resumo -->
                <div class="px-5 py-3 flex flex-wrap gap-2">
                    <span v-for="item in order.items" :key="item.id"
                          class="text-xs px-3 py-1 rounded-full font-bold"
                          style="background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.08);">
                        {{ item.quantity }}× {{ item.name }}
                    </span>
                </div>

                <!-- Ações -->
                <div class="flex items-center gap-3 px-5 py-3"
                     style="border-top:1px solid rgba(255,255,255,0.06);">
                    <router-link :to="{ name: 'customer.order', params: { id: order.id } }"
                                 class="text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg transition-all"
                                 style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.7);border:1px solid rgba(255,255,255,0.1);">
                        Ver detalhes
                    </router-link>
                    <button @click="repeatOrder(order)"
                            :disabled="repeating === order.id"
                            class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest px-4 py-2 rounded-lg transition-all"
                            style="background:rgba(255,0,60,0.1);color:#ff525c;border:1px solid rgba(255,0,60,0.3);">
                        <i class="pi pi-refresh" style="font-size:11px;" />
                        {{ repeating === order.id ? 'Adicionando...' : 'Repetir pedido' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
