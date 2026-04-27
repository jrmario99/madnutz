<template>
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-700 uppercase text-sm tracking-widest">Kits</h2>
            <button class="mn-btn-primary" @click="openNew">
                <i class="pi pi-plus" style="font-size:13px;" />
                Novo Kit
            </button>
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

        <!-- Modal -->
        <Teleport to="body">
            <Transition name="mn-modal">
                <div v-if="dialogVisible"
                     class="fixed inset-0 z-50 flex items-center justify-center p-4"
                     style="background:rgba(0,0,0,0.75);backdrop-filter:blur(4px);"
                     @mousedown.self="!saving && (dialogVisible = false)">

                    <div class="mn-modal-box w-full" style="max-width:860px;" @mousedown.stop>

                        <!-- Header -->
                        <div class="mn-modal-header">
                            <h3 class="mn-modal-title" style="font-size:1.25rem;font-weight:700;text-transform:none;letter-spacing:0;">
                                <span v-if="editing">Editar Kit: <span style="color:#C82830;">{{ editing.name }}</span></span>
                                <span v-else>Novo Kit</span>
                            </h3>
                            <button class="mn-modal-close" :disabled="saving" @click="dialogVisible = false">
                                <i class="pi pi-times" style="font-size:14px;" />
                            </button>
                        </div>

                        <!-- Body: col esquerda (3fr) + direita (2fr) -->
                        <div class="mn-modal-body">
                            <div class="grid gap-6" style="grid-template-columns:3fr 2fr;">

                                <!-- ── ESQUERDA: Info + Preços + Slots ── -->
                                <div class="space-y-5">

                                    <!-- Informações básicas -->
                                    <div>
                                        <p class="mn-section-title">Informações básicas</p>
                                        <div class="space-y-3">
                                            <div>
                                                <label class="mn-label">Nome do Kit *</label>
                                                <input v-model="form.name" class="mn-input" placeholder="Ex: Full Madness" />
                                            </div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="mn-label">Badge</label>
                                                    <input v-model="form.badge" class="mn-input" placeholder="Mais Vendido" />
                                                </div>
                                                <div>
                                                    <label class="mn-label">Ordem</label>
                                                    <InputNumber v-model="form.sort_order" class="mn-inputnumber" fluid :min="0" />
                                                </div>
                                            </div>
                                            <div>
                                                <label class="mn-label">Descrição</label>
                                                <textarea v-model="form.description" rows="4"
                                                          class="mn-input" style="resize:none;"
                                                          placeholder="Frase curta exibida no card..." />
                                            </div>
                                            <div class="flex gap-6 pt-1">
                                                <div class="mn-toggle-inline">
                                                    <ToggleSwitch v-model="form.active" />
                                                    <span>Kit Ativo</span>
                                                </div>
                                                <div class="mn-toggle-inline">
                                                    <ToggleSwitch v-model="form.free_shipping" />
                                                    <span>Frete Grátis</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Preços -->
                                    <div>
                                        <p class="mn-section-title">Preços</p>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div>
                                                <label class="mn-label">Preço normal *</label>
                                                <InputNumber v-model="form.price" class="mn-inputnumber"
                                                             fluid mode="currency" currency="BRL" locale="pt-BR" :min="0" />
                                            </div>
                                            <div>
                                                <label class="mn-label">Oferta <span class="mn-label-hint">(opcional)</span></label>
                                                <InputNumber v-model="form.sale_price" class="mn-inputnumber"
                                                             fluid mode="currency" currency="BRL" locale="pt-BR" :min="0" />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Slots de sabores -->
                                    <div>
                                        <div class="flex items-center justify-between mb-3">
                                            <p class="mn-section-title" style="margin-bottom:0;">Slots de sabores *</p>
                                            <button class="mn-btn-slot-add" @click="addSlot">
                                                <span class="mn-dot" />+ Novo Slot
                                            </button>
                                        </div>

                                        <div v-if="form.slots.length === 0" class="mn-empty-state">
                                            Adicione ao menos 1 slot.
                                        </div>

                                        <div class="grid grid-cols-3 gap-2">
                                            <div v-for="(slot, idx) in form.slots" :key="idx"
                                                 class="mn-slot-card">
                                                <label class="mn-label">Tamanho</label>
                                                <Select v-model="slot.size" :options="availableSizes"
                                                        placeholder="100g" class="mn-select" editable fluid />
                                                <div class="flex items-center gap-2 mt-2">
                                                    <InputNumber v-model="slot.quantity" :min="1"
                                                                 class="mn-inputnumber flex-1" fluid showButtons />
                                                    <button class="mn-btn-danger-icon flex-shrink-0" @click="removeSlot(idx)">
                                                        <i class="pi pi-trash" style="font-size:12px;" />
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="formError" class="mn-error-box mt-3">
                                            <i class="pi pi-exclamation-circle" style="font-size:14px;" />
                                            {{ formError }}
                                        </div>
                                    </div>

                                </div>

                                <!-- ── DIREITA: Mídia ── -->
                                <div>
                                    <p class="mn-section-title">Mídia do produto</p>

                                    <!-- Capa grande -->
                                    <div class="relative rounded-2xl overflow-hidden cursor-pointer mb-3"
                                         style="aspect-ratio:1;border:2px solid rgba(255,255,255,0.1);background:rgba(255,255,255,0.03);"
                                         @click="triggerCoverUpload">
                                        <img v-if="form.image" :src="form.image" class="w-full h-full object-cover" />
                                        <div v-else class="w-full h-full flex flex-col items-center justify-center gap-2">
                                            <i class="pi pi-image" style="font-size:36px;color:rgba(255,255,255,0.18);" />
                                            <span style="font-size:11px;color:rgba(255,255,255,0.28);font-weight:600;">Clique para adicionar</span>
                                        </div>
                                        <!-- badge CAPA -->
                                        <div v-if="form.image"
                                             class="absolute top-2 left-2 px-2 py-0.5 rounded-md text-white font-black uppercase"
                                             style="background:#C82830;font-size:9px;letter-spacing:0.12em;">CAPA</div>
                                        <!-- spinner upload -->
                                        <div v-if="coverUploading"
                                             class="absolute inset-0 flex items-center justify-center"
                                             style="background:rgba(0,0,0,0.55);">
                                            <i class="pi pi-spinner pi-spin" style="color:white;font-size:28px;" />
                                        </div>
                                    </div>
                                    <input ref="coverInput" type="file" accept="image/*" class="hidden" @change="onCoverChange" />

                                    <!-- Galeria em linha -->
                                    <div class="flex gap-2 flex-wrap">
                                        <div v-for="(img, idx) in form.images" :key="idx"
                                             class="relative group" style="width:64px;height:64px;flex-shrink:0;">
                                            <div class="w-full h-full rounded-xl overflow-hidden cursor-pointer border-2 transition-all"
                                                 :style="img.url ? 'border-color:#C82830' : 'border-color:#555;border-style:dashed'"
                                                 @click="triggerGalleryReplace(idx)">
                                                <img v-if="img.url" :src="img.url" class="w-full h-full object-cover" />
                                                <div v-else class="w-full h-full flex items-center justify-center">
                                                    <i class="pi pi-image" style="color:#666;font-size:16px;" />
                                                </div>
                                                <div v-if="galleryUploading && pendingGalleryIdx === idx"
                                                     class="absolute inset-0 flex items-center justify-center"
                                                     style="background:rgba(0,0,0,0.6);">
                                                    <i class="pi pi-spinner pi-spin" style="color:white;font-size:14px;" />
                                                </div>
                                            </div>
                                            <button class="absolute -top-1 -right-1 w-4 h-4 rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity font-bold text-white"
                                                    style="background:#C82830;font-size:11px;line-height:1;z-index:2;"
                                                    @click.stop="removeImage(idx)">&times;</button>
                                        </div>

                                        <!-- botão + NOVA -->
                                        <button class="mn-btn-nova flex-shrink-0" :disabled="galleryUploading"
                                                @click="triggerAddGallery">
                                            <i class="pi pi-plus" style="font-size:10px;color:#FFDF00;" />
                                            <span>NOVA</span>
                                        </button>
                                    </div>
                                    <input ref="galleryAddInput" type="file" accept="image/*" class="hidden" @change="onGalleryAdd" />
                                    <input ref="galleryReplaceInput" type="file" accept="image/*" class="hidden" @change="onGalleryReplace" />
                                </div>

                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="mn-modal-footer">
                            <button class="mn-btn-ghost" :disabled="saving" @click="dialogVisible = false">Cancelar</button>
                            <button class="mn-btn-primary" :disabled="saving" @click="save">
                                <i v-if="saving" class="pi pi-spinner pi-spin" style="font-size:13px;" />
                                <i v-else class="pi pi-check" style="font-size:13px;" />
                                {{ editing ? 'Salvar Alterações' : 'Criar Kit' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <ConfirmDialog />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputNumber from 'primevue/inputnumber';
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

const form              = ref(emptyForm());
const coverInput          = ref(null);
const coverUploading      = ref(false);
const galleryAddInput     = ref(null);
const galleryReplaceInput = ref(null);
const galleryUploading    = ref(false);
const pendingGalleryIdx   = ref(-1);

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

function addSlot()         { form.value.slots.push({ size: '', quantity: 1 }); }
function removeSlot(idx)   { form.value.slots.splice(idx, 1); }
function removeImage(idx)  { form.value.images.splice(idx, 1); }

function triggerCoverUpload() { coverInput.value?.click(); }
async function onCoverChange(e) {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    coverUploading.value = true;
    try {
        const fd = new FormData();
        fd.append('file', file);
        const res = await api.post('admin/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        form.value.image = res.data.url;
    } finally {
        coverUploading.value = false;
    }
}

function triggerAddGallery()      { galleryAddInput.value?.click(); }
function triggerGalleryReplace(idx) {
    pendingGalleryIdx.value = idx;
    galleryReplaceInput.value?.click();
}

async function uploadGalleryFile(file) {
    galleryUploading.value = true;
    try {
        const fd = new FormData();
        fd.append('file', file);
        const res = await api.post('admin/upload', fd, { headers: { 'Content-Type': 'multipart/form-data' } });
        return res.data.url;
    } finally {
        galleryUploading.value = false;
    }
}

async function onGalleryAdd(e) {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    const url = await uploadGalleryFile(file);
    if (url) form.value.images.push({ url, sort_order: form.value.images.length });
}

async function onGalleryReplace(e) {
    const file = e.target.files?.[0];
    e.target.value = '';
    if (!file) return;
    const url = await uploadGalleryFile(file);
    if (url) form.value.images[pendingGalleryIdx.value].url = url;
}

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

.mn-btn-add {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px; border-radius: 8px;
    font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em;
    background: rgba(200,40,48,0.15); color: #ff6060;
    border: 1px solid rgba(200,40,48,0.3); cursor: pointer;
    transition: background .15s;
}
.mn-btn-add:hover { background: rgba(200,40,48,0.25); }

.mn-btn-danger-icon {
    display: flex; align-items: center; justify-content: center;
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    background: rgba(200,40,48,0.12); color: rgba(255,80,80,0.7);
    border: 1px solid rgba(200,40,48,0.2); cursor: pointer;
    transition: background .15s, color .15s;
}
.mn-btn-danger-icon:hover { background: rgba(200,40,48,0.25); color: #ff5050; }

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

/* ── Seções ── */
.mn-section {
    padding: 20px;
    border-radius: 14px;
    background: rgba(255,255,255,0.03);
    border: 1px solid rgba(255,255,255,0.07);
}
.mn-section-title {
    display: flex; align-items: center; gap: 6px; margin-bottom: 16px;
    font-size: 10px; font-weight: 900; text-transform: uppercase;
    letter-spacing: 0.2em; color: rgba(255,255,255,0.35);
}

/* ── Campos ── */
.mn-label {
    display: block; font-size: 10px; font-weight: 900;
    text-transform: uppercase; letter-spacing: 0.15em;
    color: rgba(255,255,255,0.4); margin-bottom: 7px;
}
.mn-label-hint { font-weight: 500; text-transform: none; letter-spacing: 0; color: rgba(255,255,255,0.25); }

.mn-input {
    width: 100%; padding: 10px 14px; border-radius: 10px;
    background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
    color: #fff; font-size: 14px; font-weight: 600;
    outline: none; transition: border-color .2s, box-shadow .2s;
}
.mn-input:focus { border-color: #C82830; box-shadow: 0 0 0 3px rgba(200,40,48,0.2); }

:deep(.mn-inputnumber .p-inputtext),
:deep(.mn-select .p-inputtext) {
    width: 100%; padding: 10px 14px; border-radius: 10px;
    background: rgba(255,255,255,0.05) !important;
    border: 1px solid rgba(255,255,255,0.1) !important;
    color: #fff !important; font-size: 14px; font-weight: 600;
}
:deep(.mn-inputnumber .p-inputtext:focus),
:deep(.mn-select .p-inputtext:focus) {
    border-color: #C82830 !important;
    box-shadow: 0 0 0 3px rgba(200,40,48,0.2) !important;
}
:deep(.mn-inputnumber .p-inputnumber-button) {
    background: rgba(255,255,255,0.07) !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: rgba(255,255,255,0.5) !important;
}
:deep(.mn-select .p-select-dropdown) {
    background: rgba(255,255,255,0.07) !important;
    border-color: rgba(255,255,255,0.1) !important;
    color: rgba(255,255,255,0.5) !important;
}

/* Slot card */
.mn-slot-card {
    padding: 12px; border-radius: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
}

/* Slot row (legado) */
.mn-slot-row {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 14px; border-radius: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
}

/* Botão + Novo Slot */
.mn-btn-slot-add {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 5px 12px; border-radius: 8px;
    font-size: 11px; font-weight: 900; text-transform: uppercase; letter-spacing: 0.08em;
    background: transparent; color: #FFDF00;
    border: 1px solid rgba(255,223,0,0.3); cursor: pointer;
    transition: background .15s;
}
.mn-btn-slot-add:hover { background: rgba(255,223,0,0.08); }
.mn-dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #FFDF00; display: inline-block; flex-shrink: 0;
}

/* Botão + NOVA (galeria) */
.mn-btn-nova {
    display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;
    width: 64px; height: 64px; border-radius: 12px; flex-shrink: 0;
    background: rgba(255,255,255,0.04);
    border: 1px dashed rgba(255,255,255,0.15);
    color: rgba(255,255,255,0.5); font-size: 9px; font-weight: 900;
    text-transform: uppercase; letter-spacing: 0.1em; cursor: pointer;
    transition: background .15s;
}
.mn-btn-nova:hover { background: rgba(255,255,255,0.08); }
.mn-btn-nova:disabled { opacity: .4; cursor: not-allowed; }

/* Toggles inline */
.mn-toggle-inline {
    display: flex; align-items: center; gap: 10px;
    font-size: 12px; font-weight: 600; color: rgba(255,255,255,0.55);
}

/* Info box */
.mn-info-box {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px; border-radius: 10px;
    background: rgba(200,40,48,0.1); border: 1px solid rgba(200,40,48,0.25);
    color: #ff8080; font-size: 13px;
}

/* Empty state */
.mn-empty-state {
    text-align: center; padding: 20px;
    border: 1px dashed rgba(255,255,255,0.1);
    border-radius: 10px; font-size: 13px;
    color: rgba(255,255,255,0.25);
}

/* Erro */
.mn-error-box {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 14px; border-radius: 10px;
    background: rgba(200,40,48,0.15); border: 1px solid rgba(200,40,48,0.3);
    color: #ff8080; font-size: 13px; font-weight: 600;
}

/* Transição */
.mn-modal-enter-active, .mn-modal-leave-active { transition: opacity .2s ease, transform .2s ease; }
.mn-modal-enter-from, .mn-modal-leave-to { opacity: 0; transform: scale(.97); }
</style>
