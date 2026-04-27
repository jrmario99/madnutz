<script setup>
import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useCustomer } from '../../stores/customer.js';
import { useCart } from '../../stores/cart.js';

const router = useRouter();
const route  = useRoute();
const { api } = useCustomer();
const { addKitByName } = useCart();

const order     = ref(null);
const loading   = ref(true);
const repeating = ref(false);
const expanded  = ref({});

const statusLabel = {
    pending:   { label: 'Aguardando',  color: 'rgba(255,180,0,0.9)',   bg: 'rgba(255,180,0,0.1)',   border: 'rgba(255,180,0,0.3)' },
    paid:      { label: 'Pago',        color: 'rgba(74,222,128,0.9)',  bg: 'rgba(74,222,128,0.1)',  border: 'rgba(74,222,128,0.3)' },
    shipped:   { label: 'Enviado',     color: 'rgba(100,160,255,0.9)', bg: 'rgba(100,160,255,0.1)', border: 'rgba(100,160,255,0.3)' },
    cancelled: { label: 'Cancelado',   color: 'rgba(255,100,100,0.9)', bg: 'rgba(255,100,100,0.1)', border: 'rgba(255,100,100,0.3)' },
};

const timeline = [
    { key: 'pending',  label: 'Aguardando', icon: 'pi-clock' },
    { key: 'paid',     label: 'Pago',       icon: 'pi-check-circle' },
    { key: 'shipped',  label: 'Enviado',    icon: 'pi-truck' },
];

const fmt     = v => Number(v).toFixed(2).replace('.', ',');
const fmtDate = d => new Date(d).toLocaleDateString('pt-BR', { day: '2-digit', month: 'long', year: 'numeric' });

const statusOrder = ['pending', 'paid', 'shipped', 'cancelled'];

function timelineStep(key) {
    if (order.value?.status === 'cancelled') {
        return key === 'pending' ? 'done' : 'future';
    }
    const orderIdx   = statusOrder.indexOf(order.value?.status ?? 'pending');
    const stepIdx    = statusOrder.indexOf(key);
    if (stepIdx < orderIdx)  return 'done';
    if (stepIdx === orderIdx) return 'current';
    return 'future';
}

onMounted(async () => {
    try {
        const res = await api().get(`/customer/orders/${route.params.id}`);
        order.value = res.data;
    } catch {
        router.push({ name: 'customer.orders' });
    } finally {
        loading.value = false;
    }
});

async function repeatOrder() {
    if (!order.value) return;
    repeating.value = true;
    try {
        const res = await api().post(`/customer/orders/${order.value.id}/repeat`);
        res.data.items.forEach(item => {
            addKitByName(item.name, item.price, item.qty, item.is_custom, item.custom_items);
        });
        router.push({ name: 'cart' });
    } catch {}
    finally { repeating.value = false; }
}

function toggleExpand(id) {
    expanded.value = { ...expanded.value, [id]: !expanded.value[id] };
}
</script>

<template>
    <div>
        <!-- Back button -->
        <button @click="router.push({ name: 'customer.orders' })"
                class="flex items-center gap-2 mb-6 text-white/40 hover:text-white transition-colors">
            <i class="pi pi-arrow-left" style="font-size:11px;" />
            <span class="text-xs font-bold uppercase tracking-widest">Meus Pedidos</span>
        </button>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="w-10 h-10 rounded-full border-2 animate-spin"
                 style="border-color:#FF003C;border-top-color:transparent;" />
        </div>

        <template v-else-if="order">
            <!-- Header card -->
            <div class="rounded-2xl px-5 py-4 mb-4 flex flex-wrap items-center justify-between gap-4"
                 style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
                <div class="flex items-center gap-3 flex-wrap">
                    <h1 class="font-black text-white uppercase tracking-widest text-base"
                        style="font-family:'Passion One',sans-serif;">
                        {{ order.number }}
                    </h1>
                    <span class="text-xs px-2.5 py-1 rounded-full font-bold"
                          :style="`background:${statusLabel[order.status]?.bg ?? 'rgba(255,255,255,0.06)'};
                                   color:${statusLabel[order.status]?.color ?? '#fff'};
                                   border:1px solid ${statusLabel[order.status]?.border ?? 'rgba(255,255,255,0.1)'}`">
                        {{ statusLabel[order.status]?.label ?? order.status }}
                    </span>
                </div>
                <div class="flex items-center gap-4">
                    <span class="text-xs" style="color:rgba(255,255,255,0.4);">{{ fmtDate(order.created_at) }}</span>
                    <span class="font-black text-xl" style="color:#ff525c;">R$ {{ fmt(order.total) }}</span>
                </div>
            </div>

            <!-- Timeline (not shown for cancelled) -->
            <div v-if="order.status !== 'cancelled'"
                 class="rounded-2xl px-5 py-5 mb-4"
                 style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
                <div class="flex items-center justify-between relative">
                    <!-- Connecting line -->
                    <div class="absolute left-0 right-0 top-4 h-px mx-10"
                         style="background:rgba(255,255,255,0.08);" />

                    <div v-for="step in timeline" :key="step.key"
                         class="flex flex-col items-center gap-2 relative z-10">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 transition-all"
                             :style="timelineStep(step.key) === 'done'
                                 ? 'background:rgba(74,222,128,0.15);border-color:rgba(74,222,128,0.6);'
                                 : timelineStep(step.key) === 'current'
                                     ? 'background:rgba(255,0,60,0.15);border-color:#FF003C;box-shadow:0 0 10px rgba(255,0,60,0.4)'
                                     : 'background:transparent;border-color:rgba(255,255,255,0.1)'">
                            <i :class="['pi', step.icon]"
                               :style="timelineStep(step.key) === 'done'
                                   ? 'font-size:13px;color:rgba(74,222,128,0.9)'
                                   : timelineStep(step.key) === 'current'
                                       ? 'font-size:13px;color:#FF003C'
                                       : 'font-size:13px;color:rgba(255,255,255,0.2)'" />
                        </div>
                        <span class="text-xs font-bold uppercase tracking-widest"
                              :style="timelineStep(step.key) === 'done'
                                  ? 'color:rgba(74,222,128,0.7)'
                                  : timelineStep(step.key) === 'current'
                                      ? 'color:#fff'
                                      : 'color:rgba(255,255,255,0.2)'">
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Items -->
            <div class="rounded-2xl overflow-hidden mb-4"
                 style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
                <div class="px-5 py-3"
                     style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <h2 class="text-xs font-black uppercase tracking-widest"
                        style="color:rgba(255,255,255,0.5);">Itens do Pedido</h2>
                </div>

                <div v-for="(item, idx) in order.items" :key="item.id"
                     :style="idx < order.items.length - 1 ? 'border-bottom:1px solid rgba(255,255,255,0.04)' : ''">
                    <div class="px-5 py-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-white text-sm">{{ item.kit_name_snapshot }}</span>
                                    <span v-if="item.is_custom"
                                          class="text-xs px-2 py-0.5 rounded-full font-bold"
                                          style="background:rgba(230,235,0,0.1);color:rgba(230,235,0,0.7);border:1px solid rgba(230,235,0,0.2);">
                                        Personalizado
                                    </span>
                                </div>
                                <div class="mt-1 flex items-center gap-3">
                                    <span class="text-xs" style="color:rgba(255,255,255,0.4);">
                                        {{ item.quantity }}× R$ {{ fmt(item.price_snapshot) }}
                                    </span>
                                </div>

                                <!-- Custom items expandable -->
                                <template v-if="item.is_custom && item.custom_items?.length">
                                    <button @click="toggleExpand(item.id)"
                                            class="mt-2 flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest transition-colors"
                                            style="color:rgba(255,255,255,0.3);">
                                        <i :class="['pi', expanded[item.id] ? 'pi-chevron-up' : 'pi-chevron-down']"
                                           style="font-size:10px;" />
                                        {{ expanded[item.id] ? 'Ocultar' : 'Ver' }} produtos
                                    </button>
                                    <div v-if="expanded[item.id]"
                                         class="mt-3 flex flex-wrap gap-1.5">
                                        <span v-for="ci in item.custom_items" :key="ci.product_id"
                                              class="text-xs px-2.5 py-1 rounded-full font-bold"
                                              style="background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.55);border:1px solid rgba(255,255,255,0.08);">
                                            {{ ci.quantity }}× {{ ci.name }}
                                        </span>
                                    </div>
                                </template>
                            </div>
                            <span class="font-black flex-shrink-0 text-sm" style="color:#ff525c;">
                                R$ {{ fmt(item.price_snapshot * item.quantity) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Summary -->
            <div class="rounded-2xl px-5 py-4 mb-4"
                 style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
                <h2 class="text-xs font-black uppercase tracking-widest mb-3"
                    style="color:rgba(255,255,255,0.5);">Resumo</h2>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between text-sm">
                        <span style="color:rgba(255,255,255,0.5);">Subtotal</span>
                        <span class="font-bold text-white">R$ {{ fmt(order.subtotal) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span style="color:rgba(255,255,255,0.5);">Frete</span>
                        <span class="font-bold"
                              :style="order.shipping === 0 ? 'color:rgba(74,222,128,0.9)' : 'color:#fff'">
                            {{ order.shipping === 0 ? 'Grátis' : `R$ ${fmt(order.shipping)}` }}
                        </span>
                    </div>
                    <div class="flex justify-between text-base pt-2"
                         style="border-top:1px solid rgba(255,255,255,0.06);">
                        <span class="font-black text-white uppercase tracking-wider">Total</span>
                        <span class="font-black" style="color:#ff525c;">R$ {{ fmt(order.total) }}</span>
                    </div>
                </div>
            </div>

            <!-- Customer info -->
            <div class="rounded-2xl px-5 py-4 mb-6"
                 style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
                <h2 class="text-xs font-black uppercase tracking-widest mb-3"
                    style="color:rgba(255,255,255,0.5);">Dados do Pedido</h2>
                <div class="flex flex-col gap-1.5 text-sm">
                    <div class="flex gap-3">
                        <span class="w-20 flex-shrink-0" style="color:rgba(255,255,255,0.35);">Nome</span>
                        <span class="font-bold text-white">{{ order.customer_name }}</span>
                    </div>
                    <div class="flex gap-3">
                        <span class="w-20 flex-shrink-0" style="color:rgba(255,255,255,0.35);">E-mail</span>
                        <span class="font-bold text-white">{{ order.customer_email }}</span>
                    </div>
                    <div v-if="order.customer_phone" class="flex gap-3">
                        <span class="w-20 flex-shrink-0" style="color:rgba(255,255,255,0.35);">Telefone</span>
                        <span class="font-bold text-white">{{ order.customer_phone }}</span>
                    </div>
                    <template v-if="order.customer_address?.street">
                        <div class="flex gap-3">
                            <span class="w-20 flex-shrink-0" style="color:rgba(255,255,255,0.35);">Endereço</span>
                            <span class="font-bold text-white">{{ order.customer_address.street }}</span>
                        </div>
                        <div v-if="order.customer_address.city" class="flex gap-3">
                            <span class="w-20 flex-shrink-0" style="color:rgba(255,255,255,0.35);">Cidade</span>
                            <span class="font-bold text-white">{{ order.customer_address.city }} — {{ order.customer_address.state }}</span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-3">
                <button @click="repeatOrder"
                        :disabled="repeating"
                        class="flex items-center gap-2 text-sm font-black uppercase tracking-widest px-6 py-3 rounded-lg transition-all"
                        style="background:#FF003C;color:#fff;box-shadow:0 4px 16px rgba(255,0,60,0.35);">
                    <i class="pi pi-refresh" style="font-size:13px;" />
                    {{ repeating ? 'Adicionando...' : 'Repetir este pedido' }}
                </button>
            </div>
        </template>
    </div>
</template>
