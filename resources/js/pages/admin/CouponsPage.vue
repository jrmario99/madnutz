<template>
    <div class="space-y-6">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-xl font-bold text-gray-800">Cupons de desconto</h2>
                <p class="text-sm text-gray-400 mt-0.5">Gerencie os cupons de desconto</p>
            </div>
            <button class="mn-btn-primary" @click="openNew">
                <i class="pi pi-plus" style="font-size:13px;" />
                Novo cupom
            </button>
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
                                  : 'background:#FFF1F2;color:#C82830'">
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

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="mn-modal">
                <div v-if="dialogVisible"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     style="background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);"
                     @mousedown.self="!saving && (dialogVisible = false)">

                    <div class="mn-modal-box w-full max-w-lg"
                         @mousedown.stop>

                        <!-- Header -->
                        <div class="mn-modal-header">
                            <div>
                                <p class="mn-modal-eyebrow">Área admin</p>
                                <h3 class="mn-modal-title">
                                    {{ editing ? 'Editar cupom' : 'Novo cupom' }}
                                </h3>
                            </div>
                            <button class="mn-modal-close" :disabled="saving"
                                    @click="dialogVisible = false">
                                <i class="pi pi-times" style="font-size:14px;" />
                            </button>
                        </div>

                        <!-- Body -->
                        <div class="mn-modal-body space-y-5">

                            <!-- Código + Tipo -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mn-label">Código *</label>
                                    <input v-model="form.code"
                                           class="mn-input"
                                           placeholder="EX: MADNUTZ10"
                                           :disabled="!!editing"
                                           @input="form.code = form.code.toUpperCase()" />
                                </div>
                                <div>
                                    <label class="mn-label">Tipo *</label>
                                    <select v-model="form.type" class="mn-input">
                                        <option v-for="o in typeOptions" :key="o.value" :value="o.value">
                                            {{ o.label }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- Valor + Pedido mínimo -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mn-label">
                                        Valor * <span class="mn-label-hint">{{ form.type === 'percent' ? '(%)' : '(R$)' }}</span>
                                    </label>
                                    <InputNumber v-model="form.value"
                                                 class="mn-inputnumber"
                                                 fluid :min="0.01"
                                                 :mode="form.type === 'fixed' ? 'currency' : 'decimal'"
                                                 :currency="form.type === 'fixed' ? 'BRL' : undefined"
                                                 :locale="form.type === 'fixed' ? 'pt-BR' : undefined"
                                                 :max="form.type === 'percent' ? 100 : undefined"
                                                 :suffix="form.type === 'percent' ? '%' : undefined" />
                                </div>
                                <div>
                                    <label class="mn-label">Pedido mínimo <span class="mn-label-hint">(R$)</span></label>
                                    <InputNumber v-model="form.min_order"
                                                 class="mn-inputnumber"
                                                 fluid :min="0"
                                                 mode="currency" currency="BRL" locale="pt-BR" />
                                </div>
                            </div>

                            <!-- Limite de usos + Expiração -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="mn-label">Limite de usos <span class="mn-label-hint">(vazio = ilimitado)</span></label>
                                    <InputNumber v-model="form.max_uses"
                                                 class="mn-inputnumber"
                                                 fluid :min="1" :useGrouping="false"
                                                 placeholder="Ilimitado" />
                                </div>
                                <div>
                                    <label class="mn-label">Expira em</label>
                                    <DatePicker v-model="form.expires_at"
                                                class="mn-inputnumber"
                                                fluid showIcon
                                                dateFormat="dd/mm/yy"
                                                placeholder="Sem expiração" />
                                </div>
                            </div>

                            <!-- Status -->
                            <div class="mn-toggle-row">
                                <div>
                                    <p class="mn-toggle-label">Cupom ativo</p>
                                    <p class="mn-toggle-hint">Cupom disponível para uso pelos clientes</p>
                                </div>
                                <ToggleSwitch v-model="form.active" />
                            </div>

                            <!-- Erro -->
                            <div v-if="formError" class="mn-error-box">
                                <i class="pi pi-exclamation-circle" style="font-size:14px;" />
                                {{ formError }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mn-modal-footer">
                            <button class="mn-btn-ghost" :disabled="saving"
                                    @click="dialogVisible = false">
                                Cancelar
                            </button>
                            <button class="mn-btn-primary" :disabled="saving" @click="save">
                                <i v-if="saving" class="pi pi-spinner pi-spin" style="font-size:13px;" />
                                <i v-else class="pi pi-check" style="font-size:13px;" />
                                {{ editing ? 'Salvar alterações' : 'Criar cupom' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from '../../composables/useToast.js';
import { useConfirmDialog as useConfirm } from '../../composables/useConfirmDialog.js';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
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

/* ── Botões ── */
.mn-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    background: #C82830;
    color: #FFDF00;
    border: none;
    cursor: pointer;
    transition: opacity .15s, transform .1s;
}
.mn-btn-primary:hover { opacity: .88; }
.mn-btn-primary:active { transform: scale(.97); }
.mn-btn-primary:disabled { opacity: .5; cursor: not-allowed; }

.mn-btn-ghost {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 10px 20px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    background: transparent;
    color: rgba(255,255,255,0.45);
    border: 1px solid rgba(255,255,255,0.12);
    cursor: pointer;
    transition: color .15s, border-color .15s;
}
.mn-btn-ghost:hover { color: rgba(255,255,255,0.85); border-color: rgba(255,255,255,0.3); }
.mn-btn-ghost:disabled { opacity: .4; cursor: not-allowed; }

/* ── Modal ── */
.mn-modal-box {
    background: #1a1a1a;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,0.7);
}

.mn-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 24px 28px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    background: linear-gradient(135deg, rgba(200,40,48,0.15) 0%, transparent 60%);
}

.mn-modal-eyebrow {
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.25em;
    color: #C82830;
    margin-bottom: 4px;
}

.mn-modal-title {
    font-family: 'Passion One', sans-serif;
    font-size: 1.8rem;
    font-weight: 900;
    text-transform: uppercase;
    color: #fff;
    line-height: 1;
    letter-spacing: 0.02em;
}

.mn-modal-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.4);
    cursor: pointer;
    transition: background .15s, color .15s;
}
.mn-modal-close:hover { background: rgba(255,255,255,0.12); color: #fff; }

.mn-modal-body {
    padding: 24px 28px;
}

.mn-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 28px 24px;
    border-top: 1px solid rgba(255,255,255,0.07);
}

/* ── Campos ── */
.mn-label {
    display: block;
    font-size: 10px;
    font-weight: 900;
    text-transform: uppercase;
    letter-spacing: 0.15em;
    color: rgba(255,255,255,0.4);
    margin-bottom: 7px;
}
.mn-label-hint {
    font-weight: 500;
    text-transform: none;
    letter-spacing: 0;
    color: rgba(255,255,255,0.25);
}

.mn-input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
    appearance: none;
}
.mn-input:focus {
    border-color: #C82830;
    box-shadow: 0 0 0 3px rgba(200,40,48,0.2);
}
.mn-input:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}
.mn-input option { background: #222; color: #fff; }

/* PrimeVue InputNumber / DatePicker herdados */
:deep(.mn-inputnumber .p-inputtext) {
    width: 100%;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(255,255,255,0.05) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: #fff !important;
    font-size: 14px;
    font-weight: 600;
    outline: none;
    transition: border-color .2s, box-shadow .2s;
}
:deep(.mn-inputnumber .p-inputtext:focus) {
    border-color: #C82830 !important;
    box-shadow: 0 0 0 3px rgba(200,40,48,0.2) !important;
}
:deep(.mn-inputnumber .p-inputnumber-button) {
    background: rgba(255,255,255,0.07) !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: rgba(255,255,255,0.5) !important;
}
:deep(.mn-inputnumber .p-datepicker-trigger) {
    background: rgba(255,255,255,0.07) !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: rgba(255,255,255,0.5) !important;
}

/* Toggle row */
.mn-toggle-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 16px;
    border-radius: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
}
.mn-toggle-label {
    font-size: 13px;
    font-weight: 700;
    color: rgba(255,255,255,0.75);
}
.mn-toggle-hint {
    font-size: 11px;
    color: rgba(255,255,255,0.3);
    margin-top: 2px;
}

/* Erro */
.mn-error-box {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    border-radius: 10px;
    background: rgba(200,40,48,0.15);
    border: 1px solid rgba(200,40,48,0.3);
    color: #ff8080;
    font-size: 13px;
    font-weight: 600;
}

/* Transição do modal */
.mn-modal-enter-active, .mn-modal-leave-active { transition: opacity .2s ease, transform .2s ease; }
.mn-modal-enter-from, .mn-modal-leave-to { opacity: 0; transform: scale(.97); }
</style>
