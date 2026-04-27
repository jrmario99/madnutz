<script setup>
import { ref, onMounted } from 'vue';
import { useCustomer } from '../../stores/customer.js';

const { customer, token, login, api } = useCustomer();

// ── Tabs ──────────────────────────────────────────────────────────────────────
const tab = ref('dados'); // 'dados' | 'enderecos' | 'senha'

// ── Dados pessoais ────────────────────────────────────────────────────────────
const form    = ref({ name: '', email: '', phone: '' });
const saving  = ref(false);
const saved   = ref(false);
const saveErr = ref('');

function loadForm() {
    form.value = { name: customer.value?.name ?? '', email: customer.value?.email ?? '', phone: customer.value?.phone ?? '' };
}

async function saveProfile() {
    saving.value = true; saveErr.value = '';
    try {
        const res = await api().put('/customer/profile', form.value);
        login(token.value, res.data);
        saved.value = true;
        setTimeout(() => saved.value = false, 2000);
    } catch (e) {
        saveErr.value = e.response?.data?.message ?? 'Erro ao salvar.';
    } finally { saving.value = false; }
}

// ── Senha ─────────────────────────────────────────────────────────────────────
const pwForm   = ref({ current_password: '', password: '', password_confirmation: '' });
const pwSaving = ref(false);
const pwSaved  = ref(false);
const pwErr    = ref('');

async function savePassword() {
    pwSaving.value = true; pwErr.value = '';
    try {
        await api().put('/customer/profile/password', pwForm.value);
        pwSaved.value = true;
        pwForm.value  = { current_password: '', password: '', password_confirmation: '' };
        setTimeout(() => pwSaved.value = false, 2500);
    } catch (e) {
        pwErr.value = e.response?.data?.message
            ?? Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
            ?? 'Erro.';
    } finally { pwSaving.value = false; }
}

// ── Endereços ─────────────────────────────────────────────────────────────────
const addresses   = ref([]);
const addrLoading = ref(false);
const showForm    = ref(false);
const editingId   = ref(null);
const addrSaving  = ref(false);
const addrErr     = ref('');
const cepLoading  = ref(false);

const labelOptions = ['Casa', 'Trabalho', 'Apartamento', 'Casa dos Pais', 'Outro'];

const blankAddr = () => ({
    label: 'Casa', recipient_name: '', zip: '',
    street: '', number: '', complement: '',
    neighborhood: '', city: '', state: '', is_default: false,
});
const addrForm = ref(blankAddr());

async function loadAddresses() {
    addrLoading.value = true;
    try {
        const res = await api().get('/customer/addresses');
        addresses.value = res.data;
    } catch {} finally { addrLoading.value = false; }
}

async function fetchCep() {
    const cep = addrForm.value.zip.replace(/\D/g, '');
    if (cep.length !== 8) return;
    cepLoading.value = true;
    try {
        const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (!data.erro) {
            addrForm.value.street       = data.logradouro ?? addrForm.value.street;
            addrForm.value.neighborhood = data.bairro     ?? addrForm.value.neighborhood;
            addrForm.value.city         = data.localidade ?? addrForm.value.city;
            addrForm.value.state        = data.uf         ?? addrForm.value.state;
        }
    } catch {} finally { cepLoading.value = false; }
}

function formatCep(e) {
    let v = e.target.value.replace(/\D/g, '').slice(0, 8);
    if (v.length > 5) v = v.slice(0, 5) + '-' + v.slice(5);
    addrForm.value.zip = v;
    if (v.replace(/\D/g, '').length === 8) fetchCep();
}

function openNew() {
    editingId.value = null;
    addrForm.value  = blankAddr();
    addrErr.value   = '';
    showForm.value  = true;
}

function openEdit(addr) {
    editingId.value = addr.id;
    addrForm.value  = { ...addr };
    addrErr.value   = '';
    showForm.value  = true;
}

function cancelForm() { showForm.value = false; editingId.value = null; }

async function saveAddress() {
    addrSaving.value = true; addrErr.value = '';
    try {
        if (editingId.value) {
            await api().put(`/customer/addresses/${editingId.value}`, addrForm.value);
        } else {
            await api().post('/customer/addresses', addrForm.value);
        }
        showForm.value = false;
        await loadAddresses();
    } catch (e) {
        addrErr.value = Object.values(e.response?.data?.errors ?? {}).flat().join(' ')
            || e.response?.data?.message || 'Erro ao salvar.';
    } finally { addrSaving.value = false; }
}

async function removeAddress(id) {
    await api().delete(`/customer/addresses/${id}`);
    addresses.value = addresses.value.filter(a => a.id !== id);
}

async function setDefault(id) {
    await api().post(`/customer/addresses/${id}/set-default`);
    addresses.value.forEach(a => { a.is_default = a.id === id; });
}

onMounted(() => { loadForm(); loadAddresses(); });
</script>

<template>
    <div class="flex flex-col gap-6">

        <!-- Page header -->
        <div>
            <h1 class="font-black uppercase tracking-tight text-white"
                style="font-family:'Passion One',sans-serif;font-size:2rem;">
                Meu Perfil
            </h1>
            <p style="color:rgba(255,255,255,0.4);font-size:14px;">Gerencie seus dados, endereços e senha.</p>
        </div>

        <!-- Tab bar -->
        <div class="flex gap-1 p-1 rounded-xl w-fit"
             style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
            <button v-for="t in [
                { key:'dados',     icon:'pi-user',       label:'Dados'     },
                { key:'enderecos', icon:'pi-map-marker', label:'Endereços' },
                { key:'senha',     icon:'pi-lock',       label:'Senha'     },
            ]" :key="t.key"
                    class="flex items-center gap-2 px-5 py-2.5 rounded-lg text-xs font-black uppercase tracking-widest transition-all duration-200"
                    :style="tab === t.key
                        ? 'background:#FF003C;color:#fff;box-shadow:0 2px 12px rgba(255,0,60,0.4)'
                        : 'color:rgba(255,255,255,0.45)'"
                    @click="tab = t.key">
                <i :class="['pi', t.icon]" style="font-size:13px;" />
                <span class="hidden sm:inline">{{ t.label }}</span>
            </button>
        </div>

        <!-- ── TAB: DADOS ── -->
        <Transition name="tab" mode="out-in">
        <div v-if="tab === 'dados'" key="dados"
             class="rounded-2xl p-6 sm:p-8 flex flex-col gap-6"
             style="background:rgba(18,18,18,0.8);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.08);">

            <div class="flex items-center gap-4 pb-5"
                 style="border-bottom:1px solid rgba(255,255,255,0.06);">
                <div class="w-14 h-14 rounded-full flex items-center justify-center text-2xl font-black flex-shrink-0"
                     style="background:linear-gradient(135deg,rgba(255,0,60,0.3),rgba(255,140,0,0.2));border:2px solid rgba(255,0,60,0.3);color:#ff525c;">
                    {{ customer?.name?.charAt(0)?.toUpperCase() ?? '?' }}
                </div>
                <div>
                    <p class="font-black text-white text-lg leading-tight">{{ customer?.name }}</p>
                    <p class="text-sm mt-0.5" style="color:rgba(255,255,255,0.35);">{{ customer?.email }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex flex-col gap-1.5 sm:col-span-2">
                    <label class="field-label">Nome completo</label>
                    <input v-model="form.name" type="text" class="field-input" placeholder="Seu nome" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="field-label">E-mail</label>
                    <input v-model="form.email" type="email" class="field-input" placeholder="seu@email.com" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="field-label">WhatsApp</label>
                    <input v-model="form.phone" type="text" class="field-input" placeholder="(11) 99999-9999" />
                </div>
            </div>

            <p v-if="saveErr" class="text-xs font-bold px-3 py-2 rounded-lg"
               style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2);">
                {{ saveErr }}
            </p>

            <button @click="saveProfile" :disabled="saving"
                    class="self-start flex items-center gap-2 px-8 py-3 rounded-xl font-black uppercase text-sm tracking-widest text-white transition-all"
                    :style="saved ? 'background:#4ade80' : 'background:#FF003C;box-shadow:0 4px 16px rgba(255,0,60,0.35)'">
                <i :class="['pi', saved ? 'pi-check' : saving ? 'pi-spin pi-spinner' : 'pi-save']" style="font-size:13px;" />
                {{ saved ? 'Salvo!' : saving ? 'Salvando…' : 'Salvar dados' }}
            </button>
        </div>
        </Transition>

        <!-- ── TAB: ENDEREÇOS ── -->
        <Transition name="tab" mode="out-in">
        <div v-if="tab === 'enderecos'" key="enderecos" class="flex flex-col gap-4">

            <div v-if="addrLoading" class="flex justify-center py-12">
                <div class="w-8 h-8 rounded-full border-2 animate-spin"
                     style="border-color:rgba(255,0,60,0.3);border-top-color:#FF003C;" />
            </div>

            <template v-else>
                <!-- Estado vazio -->
                <div v-if="addresses.length === 0 && !showForm"
                     class="rounded-2xl flex flex-col items-center gap-4 py-16 text-center"
                     style="background:rgba(18,18,18,0.8);border:1px solid rgba(255,255,255,0.08);">
                    <div class="w-16 h-16 rounded-full flex items-center justify-center"
                         style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
                        <i class="pi pi-map" style="font-size:28px;color:rgba(255,255,255,0.15);" />
                    </div>
                    <div>
                        <p class="font-black text-white text-lg">Nenhum endereço cadastrado</p>
                        <p class="text-sm mt-1" style="color:rgba(255,255,255,0.35);">
                            Adicione um endereço para agilizar seus pedidos.
                        </p>
                    </div>
                </div>

                <!-- Cards de endereços -->
                <TransitionGroup name="list" tag="div" class="flex flex-col gap-3">
                    <div v-for="addr in addresses" :key="addr.id"
                         class="rounded-2xl p-5 flex flex-col sm:flex-row sm:items-start gap-4 transition-all"
                         :style="addr.is_default
                             ? 'background:rgba(18,18,18,0.9);border:1px solid rgba(255,0,60,0.3)'
                             : 'background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.07)'">

                        <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                             :style="addr.is_default
                                 ? 'background:rgba(255,0,60,0.15);border:1px solid rgba(255,0,60,0.3)'
                                 : 'background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1)'">
                            <i class="pi pi-map-marker"
                               :style="addr.is_default ? 'font-size:18px;color:#ff525c' : 'font-size:18px;color:rgba(255,255,255,0.3)'" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1 flex-wrap">
                                <p class="font-black text-white text-sm">{{ addr.label }}</p>
                                <span v-if="addr.is_default"
                                      class="text-[10px] font-black uppercase tracking-widest px-2 py-0.5 rounded-full"
                                      style="background:rgba(255,0,60,0.15);color:#ff525c;border:1px solid rgba(255,0,60,0.2);">
                                    Padrão
                                </span>
                            </div>
                            <p v-if="addr.recipient_name" class="text-xs font-bold mb-1.5 flex items-center gap-1"
                               style="color:rgba(255,200,60,0.8);">
                                <i class="pi pi-gift" style="font-size:10px;" />
                                Para: {{ addr.recipient_name }}
                            </p>
                            <p class="text-sm" style="color:rgba(255,255,255,0.75);">
                                {{ addr.street }}<span v-if="addr.number">, {{ addr.number }}</span>
                                <span v-if="addr.complement" style="color:rgba(255,255,255,0.45);"> — {{ addr.complement }}</span>
                            </p>
                            <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.4);">
                                <span v-if="addr.neighborhood">{{ addr.neighborhood }}, </span>
                                {{ addr.city }}/{{ addr.state }} · CEP {{ addr.zip }}
                            </p>
                        </div>

                        <div class="flex items-center gap-2 flex-shrink-0 self-start sm:self-center">
                            <button v-if="!addr.is_default"
                                    @click="setDefault(addr.id)"
                                    class="text-xs font-bold px-3 py-1.5 rounded-lg transition-all whitespace-nowrap"
                                    style="color:rgba(255,255,255,0.4);border:1px solid rgba(255,255,255,0.1);"
                                    @mouseenter="$event.currentTarget.style.color='#fff';$event.currentTarget.style.borderColor='rgba(255,255,255,0.3)'"
                                    @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.4)';$event.currentTarget.style.borderColor='rgba(255,255,255,0.1)'">
                                Tornar padrão
                            </button>
                            <button @click="openEdit(addr)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-all"
                                    style="color:rgba(255,255,255,0.4);border:1px solid rgba(255,255,255,0.1);"
                                    @mouseenter="$event.currentTarget.style.color='#fff';$event.currentTarget.style.borderColor='rgba(255,255,255,0.3)'"
                                    @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.4)';$event.currentTarget.style.borderColor='rgba(255,255,255,0.1)'">
                                <i class="pi pi-pencil" style="font-size:11px;" />
                            </button>
                            <button @click="removeAddress(addr.id)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-all"
                                    style="color:rgba(255,255,255,0.25);border:1px solid rgba(255,255,255,0.07);"
                                    @mouseenter="$event.currentTarget.style.color='#ff525c';$event.currentTarget.style.borderColor='rgba(255,82,92,0.4)'"
                                    @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.25)';$event.currentTarget.style.borderColor='rgba(255,255,255,0.07)'">
                                <i class="pi pi-trash" style="font-size:11px;" />
                            </button>
                        </div>
                    </div>
                </TransitionGroup>

                <!-- Botão adicionar -->
                <button v-if="!showForm" @click="openNew"
                        class="rounded-2xl py-4 flex items-center justify-center gap-2 font-black uppercase text-sm tracking-widest transition-all"
                        style="border:2px dashed rgba(255,255,255,0.12);color:rgba(255,255,255,0.35);"
                        @mouseenter="$event.currentTarget.style.borderColor='rgba(255,0,60,0.4)';$event.currentTarget.style.color='#ff525c'"
                        @mouseleave="$event.currentTarget.style.borderColor='rgba(255,255,255,0.12)';$event.currentTarget.style.color='rgba(255,255,255,0.35)'">
                    <i class="pi pi-plus" style="font-size:13px;" />
                    Adicionar endereço
                </button>

                <!-- Formulário -->
                <Transition name="form-slide">
                <div v-if="showForm"
                     class="rounded-2xl p-6 flex flex-col gap-5"
                     style="background:rgba(18,18,18,0.95);border:1.5px solid rgba(255,0,60,0.3);box-shadow:0 0 30px rgba(255,0,60,0.06);">

                    <div class="flex items-center justify-between pb-4"
                         style="border-bottom:1px solid rgba(255,255,255,0.06);">
                        <h3 class="font-black uppercase text-white tracking-tight"
                            style="font-family:'Passion One',sans-serif;font-size:1.4rem;">
                            {{ editingId ? 'Editar endereço' : 'Novo endereço' }}
                        </h3>
                        <button @click="cancelForm"
                                class="w-8 h-8 rounded-full flex items-center justify-center transition-all"
                                style="color:rgba(255,255,255,0.3);border:1px solid rgba(255,255,255,0.1);"
                                @mouseenter="$event.currentTarget.style.color='#fff'"
                                @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.3)'">
                            <i class="pi pi-times" style="font-size:11px;" />
                        </button>
                    </div>

                    <!-- Label do endereço -->
                    <div class="flex flex-col gap-2">
                        <label class="field-label">Nome do endereço</label>
                        <div class="flex gap-2 flex-wrap">
                            <button v-for="opt in labelOptions" :key="opt"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-widest transition-all"
                                    :style="addrForm.label === opt
                                        ? 'background:#FF003C;color:#fff;box-shadow:0 2px 8px rgba(255,0,60,0.4)'
                                        : 'background:rgba(255,255,255,0.05);color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.1)'"
                                    @click="addrForm.label = opt">
                                {{ opt }}
                            </button>
                        </div>
                    </div>

                    <!-- Destinatário (presente) -->
                    <div class="flex flex-col gap-1.5 rounded-xl p-4"
                         style="background:rgba(255,200,0,0.05);border:1px solid rgba(255,200,0,0.15);">
                        <label class="field-label" style="color:rgba(255,200,60,0.7);">
                            <i class="pi pi-gift mr-1" style="font-size:10px;" />
                            Para quem é o pedido? <span style="color:rgba(255,255,255,0.25);font-size:10px;">(opcional — útil para presentes)</span>
                        </label>
                        <input v-model="addrForm.recipient_name" type="text"
                               class="field-input" placeholder="Nome de quem vai receber" />
                    </div>

                    <!-- CEP + logradouro -->
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="field-label">CEP</label>
                            <div class="relative">
                                <input :value="addrForm.zip" type="text" maxlength="9"
                                       class="field-input" style="padding-right:36px;"
                                       placeholder="00000-000"
                                       @input="formatCep" />
                                <div v-if="cepLoading"
                                     class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 rounded-full border-2 animate-spin"
                                     style="border-color:rgba(255,0,60,0.3);border-top-color:#FF003C;" />
                                <i v-else class="pi pi-search absolute right-3 top-1/2 -translate-y-1/2"
                                   style="font-size:11px;color:rgba(255,255,255,0.2);pointer-events:none;" />
                            </div>
                            <p class="text-[10px]" style="color:rgba(255,255,255,0.2);">Auto-preenche o endereço</p>
                        </div>
                        <div class="sm:col-span-2 flex flex-col gap-1.5">
                            <label class="field-label">Rua / Logradouro</label>
                            <input v-model="addrForm.street" type="text"
                                   class="field-input" placeholder="Rua, Avenida, etc." />
                        </div>
                    </div>

                    <!-- Número + complemento -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="field-label">Número</label>
                            <input v-model="addrForm.number" type="text"
                                   class="field-input" placeholder="Nº" />
                        </div>
                        <div class="sm:col-span-3 flex flex-col gap-1.5">
                            <label class="field-label">Complemento</label>
                            <input v-model="addrForm.complement" type="text"
                                   class="field-input" placeholder="Apto, Bloco, Casa..." />
                        </div>
                    </div>

                    <!-- Bairro + cidade + estado -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div class="sm:col-span-2 flex flex-col gap-1.5">
                            <label class="field-label">Bairro</label>
                            <input v-model="addrForm.neighborhood" type="text"
                                   class="field-input" placeholder="Bairro" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="field-label">Cidade</label>
                            <input v-model="addrForm.city" type="text"
                                   class="field-input" placeholder="Cidade" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="field-label">UF</label>
                            <input v-model="addrForm.state" type="text" maxlength="2"
                                   class="field-input" placeholder="SP" style="text-transform:uppercase;" />
                        </div>
                    </div>

                    <!-- Toggle padrão -->
                    <label class="flex items-center gap-3 cursor-pointer w-fit select-none">
                        <div class="relative w-11 h-6 flex-shrink-0">
                            <input type="checkbox" v-model="addrForm.is_default" class="sr-only peer" />
                            <div class="w-full h-full rounded-full transition-all duration-300 peer-checked:bg-red-600"
                                 style="background:rgba(255,255,255,0.1);"></div>
                            <div class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow-md transition-all duration-300 peer-checked:translate-x-5"></div>
                        </div>
                        <span class="text-sm font-bold" style="color:rgba(255,255,255,0.55);">Usar como endereço padrão</span>
                    </label>

                    <p v-if="addrErr" class="text-xs font-bold px-3 py-2 rounded-lg"
                       style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2);">
                        {{ addrErr }}
                    </p>

                    <div class="flex gap-3 pt-1">
                        <button @click="saveAddress" :disabled="addrSaving"
                                class="flex-1 py-3.5 rounded-xl font-black uppercase text-sm tracking-widest text-white flex items-center justify-center gap-2 transition-all"
                                style="background:#FF003C;box-shadow:0 4px 16px rgba(255,0,60,0.35);">
                            <i :class="['pi', addrSaving ? 'pi-spin pi-spinner' : 'pi-check']" style="font-size:13px;" />
                            {{ addrSaving ? 'Salvando…' : editingId ? 'Salvar alterações' : 'Adicionar endereço' }}
                        </button>
                        <button @click="cancelForm"
                                class="px-6 py-3.5 rounded-xl font-black uppercase text-sm tracking-widest transition-all"
                                style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.1);">
                            Cancelar
                        </button>
                    </div>
                </div>
                </Transition>
            </template>
        </div>
        </Transition>

        <!-- ── TAB: SENHA ── -->
        <Transition name="tab" mode="out-in">
        <div v-if="tab === 'senha'" key="senha"
             class="rounded-2xl p-6 sm:p-8 flex flex-col gap-5"
             style="background:rgba(18,18,18,0.8);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.08);">

            <div class="flex items-center gap-3 pb-5"
                 style="border-bottom:1px solid rgba(255,255,255,0.06);">
                <div class="w-11 h-11 rounded-xl flex items-center justify-center flex-shrink-0"
                     style="background:rgba(255,0,60,0.1);border:1px solid rgba(255,0,60,0.2);">
                    <i class="pi pi-lock" style="font-size:18px;color:#ff525c;" />
                </div>
                <div>
                    <p class="font-black text-white">Alterar senha</p>
                    <p class="text-xs" style="color:rgba(255,255,255,0.35);">Mínimo 6 caracteres</p>
                </div>
            </div>

            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <label class="field-label">Senha atual</label>
                    <input v-model="pwForm.current_password" type="password"
                           class="field-input" placeholder="Sua senha atual" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="field-label">Nova senha</label>
                    <input v-model="pwForm.password" type="password"
                           class="field-input" placeholder="Mínimo 6 caracteres" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="field-label">Confirmar nova senha</label>
                    <input v-model="pwForm.password_confirmation" type="password"
                           class="field-input" placeholder="Repita a nova senha" />
                </div>
            </div>

            <p v-if="pwErr" class="text-xs font-bold px-3 py-2 rounded-lg"
               style="background:rgba(255,0,60,0.1);color:#ff6b6b;border:1px solid rgba(255,0,60,0.2);">
                {{ pwErr }}
            </p>

            <button @click="savePassword" :disabled="pwSaving"
                    class="self-start flex items-center gap-2 px-8 py-3 rounded-xl font-black uppercase text-sm tracking-widest text-white transition-all"
                    :style="pwSaved ? 'background:#4ade80' : 'background:#FF003C;box-shadow:0 4px 16px rgba(255,0,60,0.35)'">
                <i :class="['pi', pwSaved ? 'pi-check' : pwSaving ? 'pi-spin pi-spinner' : 'pi-lock']" style="font-size:13px;" />
                {{ pwSaved ? 'Senha atualizada!' : pwSaving ? 'Salvando…' : 'Alterar senha' }}
            </button>
        </div>
        </Transition>

    </div>
</template>

<style scoped>
.field-label {
    font-size: 11px;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: rgba(255,255,255,0.4);
}
.field-input {
    width: 100%;
    padding: 11px 14px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    outline: none;
    transition: border-color 0.2s;
}
.field-input:focus { border-color: rgba(255,0,60,0.5); }
.field-input::placeholder { color: rgba(255,255,255,0.2); font-weight: 400; }

.tab-enter-active { transition: all 0.22s ease; }
.tab-leave-active { transition: all 0.15s ease; }
.tab-enter-from   { opacity: 0; transform: translateY(8px); }
.tab-leave-to     { opacity: 0; }

.form-slide-enter-active { transition: all 0.28s cubic-bezier(0.34, 1.3, 0.64, 1); }
.form-slide-leave-active { transition: all 0.18s ease; }
.form-slide-enter-from   { opacity: 0; transform: translateY(16px) scale(0.98); }
.form-slide-leave-to     { opacity: 0; transform: translateY(-8px); }

.list-enter-active, .list-leave-active { transition: all 0.28s ease; }
.list-enter-from, .list-leave-to       { opacity: 0; transform: translateX(-10px); }
</style>
