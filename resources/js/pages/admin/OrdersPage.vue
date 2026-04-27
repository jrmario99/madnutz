<template>
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 space-y-3">
            <h2 class="font-bold text-gray-700 uppercase text-sm tracking-widest">Pedidos</h2>
            <div class="flex flex-wrap gap-2">
                <InputText v-model="filters.search" placeholder="Buscar cliente, número..." class="w-56"
                           @keyup.enter="load" size="small" />
                <Select v-model="filters.status" :options="statusOptions" optionLabel="label" optionValue="value"
                        placeholder="Status" class="w-36" showClear size="small" />
                <DatePicker v-model="filters.date_from" placeholder="De" date-format="dd/mm/yy" class="w-32" size="small" />
                <DatePicker v-model="filters.date_to"   placeholder="Até" date-format="dd/mm/yy" class="w-32" size="small" />
                <Button icon="pi pi-search" label="Filtrar" outlined size="small" @click="load" />
                <Button icon="pi pi-times" label="Limpar" text size="small" @click="clearFilters" />
            </div>
        </div>

        <DataTable :value="orders" :loading="loading" size="small"
                   paginator :rows="20" lazy :totalRecords="totalRecords" @page="onPage" :pt="tablePt">
            <Column field="number" header="Número" />
            <Column field="customer_name" header="Cliente" />
            <Column field="customer_email" header="E-mail" />
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
            <Column header="" style="width:50px">
                <template #body="{ data }">
                    <button class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-800 transition-colors"
                            @click="viewOrder(data)">
                        <i class="pi pi-eye text-xs" />
                    </button>
                </template>
            </Column>
        </DataTable>

        <!-- Detail Dialog -->
        <Dialog v-model:visible="detailVisible" header="Detalhes do Pedido" modal :style="{ width: '600px' }">
            <div v-if="selected" class="space-y-4 pt-2">
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-gray-500">Número:</span> {{ selected.number }}</div>
                    <div><span class="text-gray-500">Total:</span> R$ {{ Number(selected.total).toFixed(2) }}</div>
                    <div><span class="text-gray-500">Cliente:</span> {{ selected.customer_name }}</div>
                    <div><span class="text-gray-500">E-mail:</span> {{ selected.customer_email }}</div>
                    <div><span class="text-gray-500">Telefone:</span> {{ selected.customer_phone }}</div>
                    <div><span class="text-gray-500">CEP:</span> {{ selected.zip_code }}</div>
                    <div class="col-span-2"><span class="text-gray-500">Endereço:</span>
                        {{ selected.address }}, {{ selected.city }} — {{ selected.state }}
                    </div>
                </div>

                <div>
                    <p class="text-sm font-semibold text-gray-600 mb-2">Itens</p>
                    <div v-for="item in selected.items" :key="item.id"
                         class="flex justify-between text-sm border-b py-1">
                        <span>{{ item.kit?.name ?? 'Kit' }} × {{ item.quantity }}</span>
                        <span>R$ {{ Number(item.unit_price * item.quantity).toFixed(2) }}</span>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <label class="text-sm font-medium text-gray-600">Status:</label>
                    <Select v-model="newStatus" :options="statusOptions" optionLabel="label" optionValue="value"
                            class="flex-1" />
                    <Button label="Atualizar" :loading="saving" @click="updateStatus"
                            style="background:#C82830;border-color:#C82830" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Notas</label>
                    <Textarea v-model="notes" rows="2" class="w-full" />
                </div>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import { useToast } from '../../composables/useToast.js';
import { useAdminApi } from '../../composables/useAdminApi.js';

const api   = useAdminApi();
const toast = useToast();

const orders       = ref([]);
const loading      = ref(false);
const totalRecords = ref(0);
const currentPage  = ref(1);

const detailVisible = ref(false);
const selected      = ref(null);
const newStatus     = ref('');
const notes         = ref('');
const saving        = ref(false);

const tablePt = {
    thead:      { style: 'background:#f9fafb' },
    headerCell: { style: 'background:#f9fafb;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700;border-bottom:1px solid #e5e7eb;padding:10px 16px' },
    bodyRow:    { style: 'border-bottom:1px solid #f3f4f6' },
    bodyCell:   { style: 'color:#374151;font-size:13px;padding:10px 16px' },
};

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

const statusOptions = [
    { label: 'Pendente',   value: 'pending' },
    { label: 'Pago',       value: 'paid' },
    { label: 'Enviado',    value: 'shipped' },
    { label: 'Cancelado',  value: 'cancelled' },
];

const filters = ref({ search: '', status: null, date_from: null, date_to: null });

onMounted(load);

async function load() {
    loading.value = true;
    try {
        const params = {
            page: currentPage.value,
            search: filters.value.search || undefined,
            status: filters.value.status || undefined,
            date_from: filters.value.date_from ? formatIso(filters.value.date_from) : undefined,
            date_to:   filters.value.date_to   ? formatIso(filters.value.date_to)   : undefined,
        };
        const res = await api.get('admin/orders', { params });
        orders.value       = res.data.data;
        totalRecords.value = res.data.total;
    } finally {
        loading.value = false;
    }
}

function onPage(e) {
    currentPage.value = e.page + 1;
    load();
}

function clearFilters() {
    filters.value = { search: '', status: null, date_from: null, date_to: null };
    currentPage.value = 1;
    load();
}

async function viewOrder(o) {
    const res = await api.get(`admin/orders/${o.id}`);
    selected.value      = res.data;
    newStatus.value     = res.data.status;
    notes.value         = res.data.notes ?? '';
    detailVisible.value = true;
}

async function updateStatus() {
    saving.value = true;
    try {
        await api.put(`admin/orders/${selected.value.id}`, {
            status: newStatus.value,
            notes: notes.value,
        });
        toast.add({ severity: 'success', summary: 'Atualizado', life: 3000 });
        detailVisible.value = false;
        await load();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message ?? 'Erro', life: 4000 });
    } finally {
        saving.value = false;
    }
}

function statusLabel(s) {
    return statusMap[s]?.label ?? s;
}
function formatDate(d) {
    return new Date(d).toLocaleDateString('pt-BR');
}
function formatIso(d) {
    return d instanceof Date ? d.toISOString().slice(0, 10) : d;
}
</script>
