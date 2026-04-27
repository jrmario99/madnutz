<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100">
        <div class="bg-white rounded-2xl shadow-xl p-10 w-full max-w-md">
            <div class="flex justify-center mb-8">
                <img src="https://madnutz.com.br/wp-content/uploads/2024/06/LOGO-MADNUTZ-SEM-FUNDO.png"
                     alt="MadNutz" class="h-16 object-contain" />
            </div>

            <h2 class="text-center text-2xl font-bold text-gray-800 mb-6">Área Administrativa</h2>

            <form @submit.prevent="submit" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">E-mail</label>
                    <InputText v-model="form.email" type="email" class="w-full" placeholder="admin@madnutz.com.br" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Senha</label>
                    <Password v-model="form.password" :feedback="false" class="w-full" input-class="w-full" placeholder="••••••••" toggle-mask />
                </div>

                <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

                <Button
                    type="submit"
                    label="Entrar"
                    :loading="loading"
                    class="w-full"
                    style="background:#C82830;border-color:#C82830"
                />
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Message from 'primevue/message';
import { useAuthStore } from '../../stores/auth.js';

const router  = useRouter();
const { login } = useAuthStore();

const form    = ref({ email: '', password: '' });
const loading = ref(false);
const error   = ref('');

async function submit() {
    error.value   = '';
    loading.value = true;
    try {
        await login(form.value.email, form.value.password);
        router.push({ name: 'admin.dashboard' });
    } catch (e) {
        error.value = e.response?.data?.message ?? e.message ?? 'Erro ao entrar.';
    } finally {
        loading.value = false;
    }
}
</script>
