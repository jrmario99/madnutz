<template>
    <div>
        <!-- Preview + área de upload -->
        <div class="flex items-start gap-3">
            <!-- Preview -->
            <div class="flex-shrink-0 w-20 h-20 rounded-xl border-2 border-dashed flex items-center justify-center overflow-hidden bg-gray-50 cursor-pointer transition-colors"
                 :style="modelValue ? 'border-color:#C82830;border-style:solid;' : 'border-color:#e5e7eb'"
                 @click="triggerInput">
                <img v-if="modelValue" :src="modelValue" class="w-full h-full object-contain p-1" />
                <div v-else class="flex flex-col items-center gap-1 text-gray-400">
                    <i class="pi pi-image text-xl" />
                    <span class="text-[10px] font-medium">Imagem</span>
                </div>
            </div>

            <!-- Ações -->
            <div class="flex-1 flex flex-col gap-2 justify-center">
                <button type="button"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border-2 text-sm font-bold transition-colors w-full justify-center"
                        :style="uploading ? 'border-color:#e5e7eb;color:#9ca3af;cursor:not-allowed' : 'border-color:#C82830;color:#C82830'"
                        :disabled="uploading"
                        @click="triggerInput">
                    <i v-if="uploading" class="pi pi-spinner pi-spin text-sm" />
                    <i v-else class="pi pi-upload text-sm" />
                    {{ uploading ? 'Enviando...' : (modelValue ? 'Trocar imagem' : 'Fazer upload') }}
                </button>

                <button v-if="modelValue" type="button"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-200 text-sm font-medium text-gray-400 hover:text-red-500 hover:border-red-200 transition-colors w-full justify-center"
                        @click="clear">
                    <i class="pi pi-times text-xs" />
                    Remover
                </button>

                <p v-if="error" class="text-xs text-red-500">{{ error }}</p>
            </div>
        </div>

        <input ref="inputRef" type="file" accept="image/*" class="hidden" @change="onFileChange" />
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAdminApi } from '../composables/useAdminApi.js';

const props = defineProps({
    modelValue: { type: String, default: '' },
});
const emit = defineEmits(['update:modelValue']);

const api       = useAdminApi();
const inputRef  = ref(null);
const uploading = ref(false);
const error     = ref('');

function triggerInput() {
    inputRef.value?.click();
}

async function onFileChange(e) {
    const file = e.target.files?.[0];
    if (!file) return;

    error.value     = '';
    uploading.value = true;

    try {
        const formData = new FormData();
        formData.append('file', file);

        const res = await api.post('admin/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        emit('update:modelValue', res.data.url);
    } catch (e) {
        error.value = 'Erro ao enviar imagem. Tente novamente.';
    } finally {
        uploading.value   = false;
        e.target.value    = '';
    }
}

function clear() {
    emit('update:modelValue', '');
}
</script>
