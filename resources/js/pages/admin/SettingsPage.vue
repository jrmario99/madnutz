<template>
    <div class="max-w-xl">
        <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-bold text-gray-700 uppercase text-sm tracking-widest">Funcionalidades</h2>
            </div>

            <div class="divide-y divide-gray-100">
                <!-- Kit Personalizado -->
                <div class="flex items-center justify-between px-6 py-4">
                    <div>
                        <p class="font-semibold text-gray-800 text-sm">Kit Personalizado</p>
                        <p class="text-xs text-gray-400 mt-0.5">Exibe o card "Montar meu kit" na página inicial e libera a rota /kit-personalizado</p>
                    </div>
                    <button
                        @click="toggle"
                        :disabled="saving"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none disabled:opacity-50"
                        :style="{ background: localEnabled ? '#C82830' : '#d1d5db' }"
                        role="switch"
                        :aria-checked="localEnabled"
                    >
                        <span
                            class="pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow ring-0 transition-transform duration-200"
                            :style="{ transform: localEnabled ? 'translateX(20px)' : 'translateX(0)' }"
                        />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from '../../composables/useToast.js';
import { useAdminApi } from '../../composables/useAdminApi.js';
import { useSettingsStore } from '../../stores/settings.js';

const api   = useAdminApi();
const toast = useToast();
const { set: setSetting } = useSettingsStore();

const localEnabled = ref(true);
const saving = ref(false);

onMounted(async () => {
    try {
        const { data } = await api.get('/admin/settings');
        localEnabled.value = data.custom_kit_enabled !== '0';
    } catch {
        toast.add({ severity: 'error', summary: 'Erro ao carregar configurações', life: 3000 });
    }
});

async function toggle() {
    saving.value = true;
    const newValue = !localEnabled.value;
    try {
        await api.put('/admin/settings', { custom_kit_enabled: newValue ? '1' : '0' });
        localEnabled.value = newValue;
        setSetting('custom_kit_enabled', newValue ? '1' : '0');
        toast.add({ severity: 'success', summary: 'Configuração salva', life: 2000 });
    } catch {
        toast.add({ severity: 'error', summary: 'Erro ao salvar', life: 3000 });
    } finally {
        saving.value = false;
    }
}
</script>
