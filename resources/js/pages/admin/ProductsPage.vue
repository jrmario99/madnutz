<template>
    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 class="font-bold text-gray-700 uppercase text-sm tracking-widest">Produtos</h2>
            <Button label="Novo Produto" icon="pi pi-plus" @click="openNew" size="small"
                    style="background:#C82830;border-color:#C82830" />
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
                                class="p-1.5 rounded-lg hover:bg-green-50 text-gray-400 hover:text-green-600 transition-colors"
                                @click="restore(data)">
                            <i class="pi pi-refresh text-xs" />
                        </button>
                    </div>
                </template>
            </Column>
        </DataTable>

        <!-- Dialog -->
        <Dialog v-model:visible="dialogVisible" :header="editing ? 'Editar Produto' : 'Novo Produto'"
                modal :style="{ width: '500px' }" :closable="true">
            <div class="space-y-4 pt-2">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Nome</label>
                        <InputText v-model="form.name" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Marca</label>
                        <InputText v-model="form.brand" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Tamanho</label>
                        <InputText v-model="form.size" class="w-full" placeholder="ex: 50g" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Preço</label>
                        <InputNumber v-model="form.price" mode="currency" currency="BRL" locale="pt-BR" class="w-full" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600 mb-1">Estoque</label>
                        <InputNumber v-model="form.stock" :min="0" class="w-full" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Foto do produto</label>
                        <ImageUpload v-model="form.thumbnail" />
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Descrição</label>
                        <Textarea v-model="form.description" rows="3" class="w-full" />
                    </div>
                    <div class="flex items-center gap-2">
                        <ToggleSwitch v-model="form.active" />
                        <label>Ativo</label>
                    </div>
                </div>
            </div>
            <template #footer>
                <Button label="Cancelar" text @click="dialogVisible = false" />
                <Button :label="editing ? 'Salvar' : 'Criar'" :loading="saving"
                        @click="save" style="background:#C82830;border-color:#C82830" />
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
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
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

