<template>
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-700 uppercase text-sm tracking-widest">Kits</h2>
            <Button label="Novo Kit" icon="pi pi-plus" @click="openNew" size="small"
                    style="background:#C82830;border-color:#C82830" />
        </div>

        <DataTable :value="kits" :loading="loading" size="small" dataKey="id" :pt="tablePt">
            <Column field="name" header="Nome" sortable />
            <Column header="Preço">
                <template #body="{ data }">
                    <span v-if="data.sale_price" class="line-through text-gray-400 text-xs mr-1">R$ {{ fmt(data.price) }}</span>
                    <span :class="data.sale_price ? 'text-mn-red font-bold' : ''">R$ {{ fmt(data.effective_price) }}</span>
                </template>
            </Column>
            <Column header="Slots">
                <template #body="{ data }">
                    <span v-if="data.slots?.length" class="text-xs text-gray-600">
                        {{ data.slots.map(s => `${s.quantity}× ${s.size}`).join(' · ') }}
                    </span>
                    <span v-else class="text-xs text-gray-400">—</span>
                </template>
            </Column>
            <Column header="Badge">
                <template #body="{ data }">
                    <span v-if="data.badge" class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                          style="background:#FEF3C7;color:#92400E">{{ data.badge }}</span>
                </template>
            </Column>
            <Column header="Status">
                <template #body="{ data }">
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold uppercase"
                          :style="data.active ? 'background:#D1FAE5;color:#065F46' : 'background:#F3F4F6;color:#6B7280'">
                        {{ data.active ? 'Ativo' : 'Inativo' }}
                    </span>
                </template>
            </Column>
            <Column header="Ações" style="width:90px">
                <template #body="{ data }">
                    <div class="flex gap-1">
                        <button class="p-1.5 rounded-lg hover:bg-gray-100 text-gray-500 hover:text-gray-800 transition-colors"
                                @click="editKit(data)">
                            <i class="pi pi-pencil text-xs" />
                        </button>
                        <button class="p-1.5 rounded-lg hover:bg-red-50 text-gray-400 hover:text-red-600 transition-colors"
                                @click="confirmDelete(data)">
                            <i class="pi pi-trash text-xs" />
                        </button>
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Dialog -->
        <Dialog v-model:visible="dialogVisible" :header="editing ? 'Editar Kit' : 'Novo Kit'"
                modal :style="{ width: '760px', maxWidth: '95vw' }" :closable="true"
                :pt="{ content: { style: 'padding: 0' } }">

            <div class="overflow-y-auto" style="max-height: calc(90vh - 140px);">
                <div class="px-6 py-5 space-y-6">

                    <!-- Seção: Informações básicas -->
                    <div class="rounded-xl border border-gray-100 p-4 space-y-4">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Informações básicas</p>

                        <div>
                            <label class="field-label">Nome do Kit *</label>
                            <InputText v-model="form.name" fluid placeholder="Ex: Kit Família" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Badge <span class="text-gray-400 font-normal">(ex: MAIS VENDIDO)</span></label>
                                <InputText v-model="form.badge" fluid placeholder="Deixe vazio para ocultar" />
                            </div>
                            <div class="flex items-end pb-1">
                                <div class="flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <ToggleSwitch v-model="form.active" />
                                        <span class="text-sm font-medium text-gray-600">Kit ativo</span>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <ToggleSwitch v-model="form.free_shipping" />
                                        <span class="text-sm font-medium text-gray-600">Frete grátis</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="field-label">Descrição</label>
                            <Textarea v-model="form.description" rows="2" fluid autoResize
                                      placeholder="Descrição curta exibida no card" />
                        </div>
                    </div>

                    <!-- Seção: Preços -->
                    <div class="rounded-xl border border-gray-100 p-4 space-y-4">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Preços</p>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="field-label">Preço normal (R$) *</label>
                                <InputNumber v-model="form.price" mode="currency" currency="BRL" locale="pt-BR" fluid :min="0" />
                            </div>
                            <div>
                                <label class="field-label">
                                    Preço de oferta (R$)
                                    <span class="text-gray-400 font-normal ml-1">— vazio = sem oferta</span>
                                </label>
                                <InputNumber v-model="form.sale_price" mode="currency" currency="BRL" locale="pt-BR"
                                             fluid :min="0" placeholder="Sem oferta" />
                            </div>
                        </div>

                        <div v-if="form.sale_price" class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm"
                             style="background:#fff5f5;border:1px solid #fecaca;">
                            <i class="pi pi-tag text-xs" style="color:#C82830" />
                            <span style="color:#C82830">
                                Oferta ativa — clientes verão
                                <strong>R$ {{ fmtNum(form.sale_price) }}</strong>
                                <span class="line-through ml-1 opacity-60">R$ {{ fmtNum(form.price) }}</span>
                            </span>
                        </div>
                    </div>

                    <!-- Seção: Imagens -->
                    <div class="rounded-xl border border-gray-100 p-4 space-y-4">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Imagens</p>

                        <!-- Imagem capa -->
                        <div>
                            <label class="field-label">Imagem capa</label>
                            <ImageUpload v-model="form.image" />
                        </div>

                        <!-- Galeria -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="field-label mb-0">Galeria (imagens do modal)</label>
                                <Button icon="pi pi-plus" label="Adicionar" size="small" text @click="addImage" />
                            </div>

                            <div v-if="form.images.length === 0"
                                 class="text-sm text-gray-400 py-4 text-center rounded-xl border border-dashed border-gray-200 bg-gray-50">
                                Nenhuma imagem na galeria.
                            </div>

                            <div v-for="(img, idx) in form.images" :key="idx"
                                 class="flex items-center gap-3 mb-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                                <div class="flex-1">
                                    <ImageUpload v-model="img.url" />
                                </div>
                                <Button icon="pi pi-trash" severity="danger" text rounded size="small" @click="removeImage(idx)" />
                            </div>
                        </div>
                    </div>

                    <!-- Seção: Slots -->
                    <div class="rounded-xl border border-gray-100 p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">Slots de sabores *</p>
                                <p class="text-xs text-gray-400 mt-0.5">Defina quantos itens de cada tamanho compõem o kit.</p>
                            </div>
                            <Button icon="pi pi-plus" label="Adicionar slot" size="small" text @click="addSlot" />
                        </div>

                        <div v-if="form.slots.length === 0"
                             class="text-sm text-gray-400 py-4 text-center rounded-xl border border-dashed border-gray-200 bg-gray-50">
                            Nenhum slot. Adicione ao menos 1.
                        </div>

                        <div v-for="(slot, idx) in form.slots" :key="idx"
                             class="flex items-end gap-3 p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <div class="flex-1">
                                <label class="text-xs text-gray-500 mb-1 block font-medium">Tamanho</label>
                                <Select v-model="slot.size" :options="availableSizes"
                                        placeholder="Selecionar ou digitar" class="w-full" editable fluid />
                            </div>
                            <div class="w-36">
                                <label class="text-xs text-gray-500 mb-1 block font-medium">Qtd. a escolher</label>
                                <InputNumber v-model="slot.quantity" :min="1" fluid showButtons
                                             decrementButtonClass="p-button-secondary"
                                             incrementButtonClass="p-button-secondary" />
                            </div>
                            <Button icon="pi pi-trash" severity="danger" text rounded @click="removeSlot(idx)" />
                        </div>

                        <small v-if="formError" class="text-red-500 flex items-center gap-1">
                            <i class="pi pi-exclamation-circle text-xs" /> {{ formError }}
                        </small>
                    </div>

                </div>
            </div>

            <template #footer>
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-gray-100">
                    <Button label="Cancelar" text severity="secondary" @click="dialogVisible = false" />
                    <Button :label="editing ? 'Salvar alterações' : 'Criar Kit'" :loading="saving"
                            @click="save" style="background:#C82830;border-color:#C82830" />
                </div>
            </template>
        </Dialog>

        <ConfirmDialog />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import Dialog from 'primevue/dialog';
import InputText from 'primevue/inputtext';
import InputNumber from 'primevue/inputnumber';
import Textarea from 'primevue/textarea';
import ToggleSwitch from 'primevue/toggleswitch';
import Select from 'primevue/select';
import ImageUpload from '../../components/ImageUpload.vue';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { useAdminApi } from '../../composables/useAdminApi.js';

const api     = useAdminApi();
const confirm = useConfirm();
const toast   = useToast();

const kits           = ref([]);
const availableSizes = ref([]);
const loading        = ref(false);
const dialogVisible  = ref(false);
const saving         = ref(false);
const editing        = ref(null);
const formError      = ref('');

const tablePt = {
    thead:      { style: 'background:#f9fafb' },
    headerCell: { style: 'background:#f9fafb;color:#9ca3af;font-size:11px;text-transform:uppercase;letter-spacing:.05em;font-weight:700;border-bottom:1px solid #e5e7eb;padding:10px 16px' },
    bodyRow:    { style: 'border-bottom:1px solid #f3f4f6' },
    bodyCell:   { style: 'color:#374151;font-size:13px;padding:10px 16px' },
};

const emptyForm = () => ({
    name: '', description: '', price: 0, sale_price: null,
    image: '', badge: '', active: true, free_shipping: true, sort_order: 0,
    slots: [], images: [],
});

const form = ref(emptyForm());

const fmt    = v => Number(v).toFixed(2).replace('.', ',');
const fmtNum = v => v ? Number(v).toFixed(2).replace('.', ',') : '—';

onMounted(async () => {
    await Promise.all([loadKits(), loadSizes()]);
});

async function loadKits() {
    loading.value = true;
    try {
        const res = await api.get('admin/kits');
        kits.value = res.data;
    } finally {
        loading.value = false;
    }
}

async function loadSizes() {
    const res = await api.get('admin/products');
    const products = res.data.data ?? res.data;
    availableSizes.value = [...new Set(products.map(p => p.size).filter(Boolean))].sort();
}

function openNew() {
    editing.value       = null;
    form.value          = emptyForm();
    formError.value     = '';
    dialogVisible.value = true;
}

function editKit(k) {
    editing.value   = k;
    formError.value = '';
    form.value = {
        ...k,
        sale_price: k.sale_price ?? null,
        slots:  (k.slots  ?? []).map(s => ({ size: s.size, quantity: s.quantity })),
        images: (k.images ?? []).map(i => ({ url: i.url, sort_order: i.sort_order })),
    };
    dialogVisible.value = true;
}

function addSlot()  { form.value.slots.push({ size: '', quantity: 1 }); }
function removeSlot(idx) { form.value.slots.splice(idx, 1); }
function addImage() { form.value.images.push({ url: '', sort_order: form.value.images.length }); }
function removeImage(idx) { form.value.images.splice(idx, 1); }

async function save() {
    formError.value = '';
    const validSlots = form.value.slots.filter(s => s.size && s.quantity >= 1);
    if (validSlots.length === 0) {
        formError.value = 'Adicione pelo menos 1 slot de tamanho.';
        return;
    }

    saving.value = true;
    try {
        const payload = {
            ...form.value,
            sale_price: form.value.sale_price || null,
            slots:  validSlots,
            images: form.value.images.filter(i => i.url).map((i, idx) => ({ ...i, sort_order: idx })),
        };

        if (editing.value) {
            await api.put(`admin/kits/${editing.value.id}`, payload);
            toast.add({ severity: 'success', summary: 'Salvo', life: 3000 });
        } else {
            await api.post('admin/kits', payload);
            toast.add({ severity: 'success', summary: 'Kit criado', life: 3000 });
        }
        dialogVisible.value = false;
        await loadKits();
    } catch (e) {
        const msg = e.response?.data?.message ?? JSON.stringify(e.response?.data?.errors ?? 'Erro');
        toast.add({ severity: 'error', summary: 'Erro', detail: msg, life: 5000 });
    } finally {
        saving.value = false;
    }
}

function confirmDelete(k) {
    confirm.require({
        message: `Excluir kit "${k.name}"?`,
        header: 'Confirmar',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-text',
        accept: () => deleteKit(k),
    });
}

async function deleteKit(k) {
    await api.delete(`admin/kits/${k.id}`);
    toast.add({ severity: 'info', summary: 'Excluído', life: 3000 });
    await loadKits();
}
</script>

<style scoped>
.field-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4B5563;
    margin-bottom: 0.25rem;
}
</style>
