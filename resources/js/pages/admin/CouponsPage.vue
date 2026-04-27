<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Cupons de desconto</h2>
                <p class="text-sm text-gray-400 mt-0.5">Gerencie os cupons de desconto</p>
            </div>
            <Button label="Novo cupom" icon="pi pi-plus" @click="openNew" />
        </div>

        <!-- Table -->
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <DataTable :value="coupons" :loading="loading"
                       stripedRows removableSort
                       class="text-sm">

                <Column field="code" header="Código" sortable>
                    <template #body="{ data }">
                        <span class="font-black tracking-widest text-xs px-2.5 py-1 rounded-lg"
                              style="background:#FFF1F2;color:#C82830;">
                            {{ data.code }}
                        </span>
                    </template>
                </Column>

                <Column field="type" header="Tipo" sortable>
                    <template #body="{ data }">
                        <span class="px-2 py-0.5 rounded text-xs font-bold"
                              :style="data.type === 'percent'
                                  ? 'background:#EEF2FF;color:#4338CA'
                                  : 'background:#F0FDF4;color:#166534'">
                            {{ data.type === 'percent' ? 'Percentual' : 'Fixo' }}
                        </span>
                    </template>
                </Column>

                <Column field="value" header="Valor" sortable>
                    <template #body="{ data }">
                        <span class="font-bold">
                            {{ data.type === 'percent'
                                ? `${Number(data.value).toFixed(0)}%`
                                : `R$ ${Number(data.value).toFixed(2).replace('.', ',')}` }}
                        </span>
                    </template>
                </Column>

                <Column field="min_order" header="Pedido mín." sortable>
                    <template #body="{ data }">
                        {{ data.min_order > 0
                            ? `R$ ${Number(data.min_order).toFixed(2).replace('.', ',')}`
                            : '—' }}
                    </template>
                </Column>

                <Column header="Usos">
                    <template #body="{ data }">
                        <span class="text-gray-600">
                            {{ data.uses_count }}
                            {{ data.max_uses !== null ? ` / ${data.max_uses}` : '' }}
                        </span>
                    </template>
                </Column>

                <Column field="expires_at" header="Expira em" sortable>
                    <template #body="{ data }">
                        {{ data.expires_at ? fmtDate(data.expires_at) : '—' }}
                    </template>
                </Column>

                <Column field="active" header="Status" sortable>
                    <template #body="{ data }">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold"
                              :style="data.active
                                  ? 'background:#D1FAE5;color:#065F46'
                                  : 'background:#F3F4F6;color:#6B7280'">
                            {{ data.active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </template>
                </Column>

                <Column header="" style="width:100px">
                    <template #body="{ data }">
                        <div class="flex gap-1 justify-end">
                            <Button icon="pi pi-pencil" text rounded size="small"
                                    @click="editCoupon(data)" />
                            <Button icon="pi pi-trash" text rounded size="small" severity="danger"
                                    @click="confirmDelete(data)" />
                        </div>
                    </template>
                </Column>
            </DataTable>
        </div>

        <!-- Dialog -->
        <Dialog v-model:visible="dialogVisible"
                :header="editing ? 'Editar cupom' : 'Novo cupom'"
                modal :style="{ width: '480px' }"
                :closable="!saving">
            <div class="space-y-4 pt-2">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Código *</label>
                        <InputText v-model="form.code" fluid placeholder="EX: MADNUTZ10"
                                   :disabled="!!editing"
                                   style="text-transform:uppercase"
                                   @input="form.code = form.code.toUpperCase()" />
                    </div>
                    <div>
                        <label class="field-label">Tipo *</label>
                        <Select v-model="form.type" :options="typeOptions"
                                option-label="label" option-value="value" fluid />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">
                            Valor * {{ form.type === 'percent' ? '(%)' : '(R$)' }}
                        </label>
                        <InputNumber v-model="form.value" fluid :min="0.01"
                                     :mode="form.type === 'fixed' ? 'currency' : 'decimal'"
                                     :currency="form.type === 'fixed' ? 'BRL' : undefined"
                                     :locale="form.type === 'fixed' ? 'pt-BR' : undefined"
                                     :max="form.type === 'percent' ? 100 : undefined"
                                     :suffix="form.type === 'percent' ? '%' : undefined" />
                    </div>
                    <div>
                        <label class="field-label">Pedido mínimo (R$)</label>
                        <InputNumber v-model="form.min_order" fluid :min="0"
                                     mode="currency" currency="BRL" locale="pt-BR" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="field-label">Limite de usos <span class="text-gray-400 font-normal">(vazio = ilimitado)</span></label>
                        <InputNumber v-model="form.max_uses" fluid :min="1" :useGrouping="false"
                                     placeholder="Ilimitado" />
                    </div>
                    <div>
                        <label class="field-label">Expira em</label>
                        <DatePicker v-model="form.expires_at" fluid showIcon
                                    dateFormat="dd/mm/yy" placeholder="Sem expiração" />
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-1">
                    <ToggleSwitch v-model="form.active" />
                    <span class="text-sm font-medium text-gray-600">Cupom ativo</span>
                </div>

                <p v-if="formError" class="text-sm text-red-600 font-medium">{{ formError }}</p>
            </div>

            <template #footer>
                <Button label="Cancelar" text @click="dialogVisible = false" :disabled="saving" />
                <Button :label="editing ? 'Salvar' : 'Criar cupom'"
                        icon="pi pi-check" :loading="saving" @click="save" />
            </template>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import ToggleSwitch from 'primevue/toggleswitch';
import DatePicker from 'primevue/datepicker';
import { useAuthStore } from '../../stores/auth.js';

const toast   = useToast();
const confirm = useConfirm();
const { api } = useAuthStore();

const coupons       = ref([]);
const loading       = ref(false);
const dialogVisible = ref(false);
const editing       = ref(null);
const saving        = ref(false);
const formError     = ref('');

const typeOptions = [
    { label: 'Percentual (%)', value: 'percent' },
    { label: 'Valor fixo (R$)', value: 'fixed' },
];

const emptyForm = () => ({
    code: '', type: 'percent', value: null,
    min_order: 0, max_uses: null,
    active: true, expires_at: null,
});

const form = ref(emptyForm());

const fmtDate = (d) => new Date(d).toLocaleDateString('pt-BR');

onMounted(loadCoupons);

async function loadCoupons() {
    loading.value = true;
    try {
        const res = await api.get('admin/coupons');
        coupons.value = res.data;
    } finally {
        loading.value = false;
    }
}

function openNew() {
    editing.value       = null;
    form.value          = emptyForm();
    formError.value     = '';
    dialogVisible.value = true;
}

function editCoupon(c) {
    editing.value   = c;
    formError.value = '';
    form.value = {
        code:       c.code,
        type:       c.type,
        value:      Number(c.value),
        min_order:  Number(c.min_order),
        max_uses:   c.max_uses ?? null,
        active:     c.active,
        expires_at: c.expires_at ? new Date(c.expires_at) : null,
    };
    dialogVisible.value = true;
}

async function save() {
    formError.value = '';
    if (!form.value.code) { formError.value = 'Código obrigatório.'; return; }
    if (!form.value.value || form.value.value <= 0) { formError.value = 'Valor inválido.'; return; }

    saving.value = true;
    try {
        const payload = {
            ...form.value,
            expires_at: form.value.expires_at
                ? new Date(form.value.expires_at).toISOString().slice(0, 10)
                : null,
        };

        if (editing.value) {
            await api.put(`admin/coupons/${editing.value.id}`, payload);
            toast.add({ severity: 'success', summary: 'Cupom atualizado', life: 3000 });
        } else {
            await api.post('admin/coupons', payload);
            toast.add({ severity: 'success', summary: 'Cupom criado', life: 3000 });
        }
        await loadCoupons();
        dialogVisible.value = false;
    } catch (e) {
        const errs = e.response?.data?.errors;
        if (errs) {
            formError.value = Object.values(errs).flat().join(' ');
        } else {
            formError.value = e.response?.data?.message ?? 'Erro ao salvar.';
        }
    } finally {
        saving.value = false;
    }
}

function confirmDelete(c) {
    confirm.require({
        message: `Excluir o cupom "${c.code}"?`,
        header:  'Confirmar exclusão',
        icon:    'pi pi-trash',
        acceptClass: 'p-button-danger',
        accept: async () => {
            await api.delete(`admin/coupons/${c.id}`);
            await loadCoupons();
            toast.add({ severity: 'info', summary: 'Cupom excluído', life: 3000 });
        },
    });
}
</script>

<style scoped>
@reference "tailwindcss";
.field-label { @apply block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1.5; }
</style>
