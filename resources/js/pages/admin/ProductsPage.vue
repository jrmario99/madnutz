<template>
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-700 uppercase text-sm tracking-widest">Produtos</h2>
            <button class="mn-btn-primary" @click="openNew">
                <i class="pi pi-plus" style="font-size:13px;" />
                Novo Produto
            </button>
        </div>

        <DataTable :value="products" :loading="loading" size="small"
                   paginator :rows="20" dataKey="id" :pt="tablePt">
            <Column field="name" header="Nome" sortable />
            <Column field="brand" header="Marca" />
            <Column field="size" header="Tamanho" />
            <Column header="Preço">
                <template #body="{ data }">R$ {{ Number(data.price).toFixed(2) }}</template>
            </Column>
            <Column field="stock" header="Estoque" />
            <Column header="Status">
                <template #body="{ data }">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                          :style="data.deleted_at ? 'background:#F3F4F6;color:#6B7280' : 'background:#D1FAE5;color:#065F46'">
                        {{ data.deleted_at ? 'Inativo' : 'Ativo' }}
                    </span>
                </template>
            </Column>
            <Column header="Ações" style="width:100px">
                <template #body="{ data }">
                    <div class="flex gap-1">
                        <button class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-800 transition-colors"
                                @click="editProduct(data)">
                            <i class="pi pi-pencil text-xs" />
                        </button>
                        <button v-if="!data.deleted_at"
                                class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors"
                                @click="confirmDelete(data)">
                            <i class="pi pi-trash text-xs" />
                        </button>
                        <button v-else
                                class="p-1.5 rounded-lg hover:bg-gray-50 text-gray-400 hover:text-gray-600 transition-colors"
                                @click="restore(data)">
                            <i class="pi pi-refresh text-xs" />
                        </button>
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="mn-modal">
                <div v-if="dialogVisible"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     style="background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);"
                     @mousedown.self="dialogVisible = false">

                    <div class="mn-modal-box w-full max-w-2xl" @mousedown.stop>

                        <!-- Header -->
                        <div class="mn-modal-header">
                            <div>
                                <p class="mn-modal-eyebrow">Área admin</p>
                                <h3 class="mn-modal-title">
                                    {{ editing ? 'Editar Produto' : 'Novo Produto' }}
                                </h3>
                            </div>
                            <button class="mn-modal-close" @click="dialogVisible = false">
                                <i class="pi pi-times" style="font-size:14px;" />
                            </button>
                        </div>

                        <!-- Body scrollável -->
                        <div class="mn-modal-body overflow-y-auto" style="max-height:72vh;">
                            <div class="space-y-5">

                                <!-- Linha 1: Foto + Nome -->
                                <div class="flex items-start gap-5">
                                    <div class="flex-shrink-0">
                                        <p class="mn-label mb-2">Foto</p>
                                        <ImageUpload v-model="form.thumbnail" />
                                    </div>
                                    <div class="flex-1 space-y-4">
                                        <div>
                                            <label class="mn-label">Nome *</label>
                                            <input v-model="form.name" class="mn-input" placeholder="Ex: Choco Bomb" />
                                        </div>
                                        <div>
                                            <label class="mn-label">Descrição</label>
                                            <textarea v-model="form.description" rows="3"
                                                      class="mn-input" style="resize:vertical;"
                                                      placeholder="Descrição do produto..." />
                                        </div>
                                    </div>
                                </div>

                                <!-- Linha 2: Marca + Tamanho + Preço + Estoque -->
                                <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                    <div>
                                        <label class="mn-label">Marca</label>
                                        <input v-model="form.brand" class="mn-input" placeholder="MadNutz" />
                                    </div>
                                    <div>
                                        <label class="mn-label">Tamanho</label>
                                        <input v-model="form.size" class="mn-input" placeholder="100g" />
                                    </div>
                                    <div>
                                        <label class="mn-label">Preço (R$)</label>
                                        <InputNumber v-model="form.price" class="mn-inputnumber" fluid
                                                     mode="currency" currency="BRL" locale="pt-BR" :min="0" />
                                    </div>
                                    <div>
                                        <label class="mn-label">Estoque</label>
                                        <InputNumber v-model="form.stock" class="mn-inputnumber" fluid :min="0" />
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="mn-toggle-row">
                                    <div>
                                        <p class="mn-toggle-label">Produto ativo</p>
                                        <p class="mn-toggle-hint">Visível para clientes na loja</p>
                                    </div>
                                    <ToggleSwitch v-model="form.active" />
                                </div>

                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mn-modal-footer">
                            <button class="mn-btn-ghost" :disabled="saving" @click="dialogVisible = false">Cancelar</button>
                            <button class="mn-btn-primary" :disabled="saving" @click="save">
                                <i v-if="saving" class="pi pi-spinner pi-spin" style="font-size:13px;" />
                                <i v-else class="pi pi-check" style="font-size:13px;" />
                                {{ editing ? 'Salvar alterações' : 'Criar produto' }}
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
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import { useConfirmDialog as useConfirm } from '../../composables/useConfirmDialog.js';
import { useToast } from '../../composables/useToast.js';
import { useAdminApi } from '../../composables/useAdminApi.js';
import ImageUpload from '../../components/ImageUpload.vue';

const api     = useAdminApi();
const confirm = useConfirm();
const toast   = useToast();

const tablePt = {
    thead:      { style: 'background:#f9fafb' },
    headerCell: { style: 'background:#f9fafb;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700;border-bottom:1px solid #e5e7eb;padding:10px 16px' },
    bodyRow:    { style: 'border-bottom:1px solid #f3f4f6' },
    bodyCell:   { style: 'color:#374151;font-size:13px;padding:10px 16px' },
};

const products      = ref([]);
const loading       = ref(false);
const dialogVisible = ref(false);
const saving        = ref(false);
const editing       = ref(null);

const emptyForm = () => ({
    name: '', brand: '', size: '', price: 0, stock: 0,
    thumbnail: '', description: '', active: true,
});
const form = ref(emptyForm());

onMounted(load);

async function load() {
    loading.value = true;
    try {
        const res = await api.get('admin/products', { params: { with_trashed: true } });
        products.value = res.data.data ?? res.data;
    } finally {
        loading.value = false;
    }
}

function openNew() {
    editing.value       = null;
    form.value          = emptyForm();
    dialogVisible.value = true;
}

function editProduct(p) {
    editing.value       = p;
    form.value          = { ...p };
    dialogVisible.value = true;
}

async function save() {
    saving.value = true;
    try {
        if (editing.value) {
            await api.put(`admin/products/${editing.value.id}`, form.value);
            toast.add({ severity: 'success', summary: 'Salvo', life: 3000 });
        } else {
            await api.post('admin/products', form.value);
            toast.add({ severity: 'success', summary: 'Criado', life: 3000 });
        }
        dialogVisible.value = false;
        await load();
    } catch (e) {
        toast.add({ severity: 'error', summary: 'Erro', detail: e.response?.data?.message ?? 'Erro', life: 4000 });
    } finally {
        saving.value = false;
    }
}

function confirmDelete(p) {
    confirm.require({
        message: `Excluir "${p.name}"?`,
        header: 'Confirmar',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        accept: () => deleteProduct(p),
    });
}

async function deleteProduct(p) {
    await api.delete(`admin/products/${p.id}`);
    toast.add({ severity: 'info', summary: 'Excluído', life: 3000 });
    await load();
}

async function restore(p) {
    await api.post(`admin/products/${p.id}/restore`);
    toast.add({ severity: 'success', summary: 'Restaurado', life: 3000 });
    await load();
}
</script>

<style scoped>
/* ── Botões ── */
.mn-btn-primary {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.06em;
    background: #C82830; color: #FFDF00; border: none; cursor: pointer;
    transition: opacity .15s, transform .1s;
}
.mn-btn-primary:hover { opacity: .88; }
.mn-btn-primary:active { transform: scale(.97); }
.mn-btn-primary:disabled { opacity: .5; cursor: not-allowed; }

.mn-btn-ghost {
    display: inline-flex; align-items: center; gap: 7px;
    padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 700;
    background: transparent; color: rgba(255,255,255,0.45);
    border: 1px solid rgba(255,255,255,0.12); cursor: pointer;
    transition: color .15s, border-color .15s;
}
.mn-btn-ghost:hover { color: rgba(255,255,255,0.85); border-color: rgba(255,255,255,0.3); }
.mn-btn-ghost:disabled { opacity: .4; cursor: not-allowed; }

/* ── Modal ── */
.mn-modal-box {
    background: #1a1a1a;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 18px; overflow: hidden;
    box-shadow: 0 24px 80px rgba(0,0,0,0.7);
}
.mn-modal-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 24px 28px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.07);
    background: linear-gradient(135deg, rgba(200,40,48,0.15) 0%, transparent 60%);
}
.mn-modal-eyebrow {
    font-size: 10px; font-weight: 900; text-transform: uppercase;
    letter-spacing: 0.25em; color: #C82830; margin-bottom: 4px;
}
.mn-modal-title {
    font-family: 'Passion One', sans-serif;
    font-size: 1.8rem; font-weight: 900; text-transform: uppercase;
    color: #fff; line-height: 1; letter-spacing: 0.02em;
}
.mn-modal-close {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.4); cursor: pointer;
    transition: background .15s, color .15s;
}
.mn-modal-close:hover { background: rgba(255,255,255,0.12); color: #fff; }
.mn-modal-body { padding: 24px 28px; }
.mn-modal-footer {
    display: flex; align-items: center; justify-content: flex-end; gap: 10px;
    padding: 16px 28px 24px;
    border-top: 1px solid rgba(255,255,255,0.07);
}

/* ── Campos ── */
.mn-label {
    display: block; font-size: 10px; font-weight: 900;
    text-transform: uppercase; letter-spacing: 0.15em;
    color: rgba(255,255,255,0.4); margin-bottom: 7px;
}
.mn-input {
    width: 100%; padding: 10px 14px; border-radius: 10px;
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
    color: #fff; font-size: 14px; font-weight: 600;
    outline: none; transition: border-color .2s, box-shadow .2s;
}
.mn-input:focus {
    border-color: #C82830;
    box-shadow: 0 0 0 3px rgba(200,40,48,0.2);
}
:deep(.mn-inputnumber .p-inputtext) {
    width: 100%; padding: 10px 14px; border-radius: 10px;
    background: rgba(255,255,255,0.05) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: #fff !important; font-size: 14px; font-weight: 600;
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

/* Toggle */
.mn-toggle-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 16px; border-radius: 12px;
    background: rgba(255,255,255,0.04); border: 1px solid rgba(255,255,255,0.07);
}
.mn-toggle-label { font-size: 13px; font-weight: 700; color: rgba(255,255,255,0.75); }
.mn-toggle-hint { font-size: 11px; color: rgba(255,255,255,0.3); margin-top: 2px; }

/* Transição */
.mn-modal-enter-active, .mn-modal-leave-active { transition: opacity .2s ease, transform .2s ease; }
.mn-modal-enter-from, .mn-modal-leave-to { opacity: 0; transform: scale(.97); }
</style>
