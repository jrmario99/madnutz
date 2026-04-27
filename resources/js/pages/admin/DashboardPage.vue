<template>
    <div class="space-y-6">
        <!-- Stats cards -->
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-4">
            <div v-for="card in cards" :key="card.label"
                 class="bg-white rounded-2xl p-5 border border-gray-100 flex items-center gap-4">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                     :style="{ background: card.bg }">
                    <i :class="card.icon" class="text-white text-lg" />
                </div>
                <div class="min-w-0">
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold truncate">{{ card.label }}</p>
                    <p class="text-2xl text-gray-800" style="font-family:'Passion One',sans-serif; font-weight:400;">{{ card.value }}</p>
                </div>
            </div>
        </div>

        <!-- Recent orders -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="font-bold text-gray-700 uppercase text-sm tracking-widest">Últimos Pedidos</h2>
            </div>
            <DataTable :value="recentOrders" :loading="loading" size="small"
                       :pt="tablePt">
                <Column field="number" header="Número" />
                <Column field="customer_name" header="Cliente" />
                <Column header="Total">
                    <template #body="{ data }">R$ {{ Number(data.total).toFixed(2) }}</template>
                </Column>
                <Column header="Status">
                    <template #body="{ data }">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                              :style="statusStyle(data.status)">
                            {{ statusLabel(data.status) }}
                        </span>
                    </template>
                </Column>
                <Column header="Data">
                    <template #body="{ data }">{{ formatDate(data.created_at) }}</template>
                </Column>
            </DataTable>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import { useAdminApi } from '../../composables/useAdminApi.js';

const api = useAdminApi();

const recentOrders = ref([]);
const loading      = ref(false);

const cards = ref([
    { label: 'Total Pedidos', value: '—', icon: 'pi pi-shopping-cart',  bg: '#C82830' },
    { label: 'Receita Paga',  value: '—', icon: 'pi pi-dollar',          bg: '#1a1a1a' },
    { label: 'Pendentes',     value: '—', icon: 'pi pi-clock',            bg: '#EF841A' },
    { label: 'Cancelados',    value: '—', icon: 'pi pi-times-circle',     bg: '#6b7280' },
]);

const tablePt = {
    thead: { style: 'background:#f9fafb' },
    headerCell: { style: 'background:#f9fafb;color:#6b7280;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700;border-bottom:1px solid #e5e7eb' },
    bodyRow: { style: 'border-bottom:1px solid #f3f4f6' },
    bodyCell: { style: 'color:#374151;font-size:13px;padding:10px 16px' },
};

onMounted(async () => {
    loading.value = true;
    try {
        const [s, o] = await Promise.all([
            api.get('admin/orders/stats'),
            api.get('admin/orders', { params: { per_page: 8 } }),
        ]);
        cards.value[0].value = s.data.total_orders;
        cards.value[1].value = 'R$ ' + Number(s.data.total_revenue).toFixed(2);
        cards.value[2].value = s.data.pending;
        cards.value[3].value = s.data.cancelled;
        recentOrders.value   = o.data.data ?? [];
    } finally {
        loading.value = false;
    }
});

const statusMap = {
    pending:   { label: 'Pendente',  bg: '#FEF3C7', color: '#92400E' },
    paid:      { label: 'Pago',      bg: '#D1FAE5', color: '#065F46' },
    shipped:   { label: 'Enviado',   bg: '#DBEAFE', color: '#1E40AF' },
    cancelled: { label: 'Cancelado', bg: '#F3F4F6', color: '#6B7280' },
};

function statusStyle(s) {
    const m = statusMap[s] ?? { bg: '#F3F4F6', color: '#6B7280' };
    return `background:${m.bg};color:${m.color}`;
}
function statusLabel(s) { return statusMap[s]?.label ?? s; }
function formatDate(d)  { return new Date(d).toLocaleDateString('pt-BR'); }
</script>
