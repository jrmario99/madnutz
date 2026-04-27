<script setup>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useCustomer } from '../../stores/customer.js';

const router = useRouter();
const { login } = useCustomer();

const email       = ref('');
const method      = ref('otp');   // 'otp' | 'password'
const password    = ref('');
const otpCode     = ref(['', '', '', '', '', '']);
const otpSent     = ref(false);
const showRegister = ref(false);

const regName     = ref('');
const regEmail    = ref('');
const regPassword = ref('');

const loading = ref(false);
const error   = ref('');
const success  = ref('');

const otpFull = computed(() => otpCode.value.join(''));

// Avança o foco entre os inputs do OTP
function onOtpInput(idx, e) {
    const val = e.target.value.replace(/\D/g, '').slice(-1);
    otpCode.value[idx] = val;
    if (val && idx < 5) {
        document.getElementById(`otp-${idx + 1}`)?.focus();
    }
}
function onOtpKeydown(idx, e) {
    if (e.key === 'Backspace' && !otpCode.value[idx] && idx > 0) {
        document.getElementById(`otp-${idx - 1}`)?.focus();
    }
}
function onOtpPaste(e) {
    const paste = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6);
    paste.split('').forEach((c, i) => { otpCode.value[i] = c; });
    document.getElementById(`otp-${Math.min(paste.length, 5)}`)?.focus();
    e.preventDefault();
}

async function sendOtp() {
    error.value = '';
    if (!email.value) { error.value = 'Informe seu e-mail.'; return; }
    loading.value = true;
    try {
        await axios.post('/api/customer/auth/send-otp', { email: email.value });
        otpSent.value = true;
        success.value = 'Código enviado! Verifique seu e-mail.';
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Erro ao enviar código.';
    } finally {
        loading.value = false;
    }
}

async function submitPassword() {
    error.value = '';
    loading.value = true;
    try {
        const res = await axios.post('/api/customer/auth/login-password', {
            email: email.value, password: password.value,
        });
        login(res.data.token, res.data.customer);
        router.push({ name: 'customer.orders' });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Credenciais inválidas.';
    } finally {
        loading.value = false;
    }
}

async function submitOtp() {
    error.value = '';
    if (otpFull.value.length < 6) { error.value = 'Digite os 6 dígitos.'; return; }
    loading.value = true;
    try {
        const res = await axios.post('/api/customer/auth/verify-otp', {
            email: email.value, code: otpFull.value,
        });
        login(res.data.token, res.data.customer);
        router.push({ name: 'customer.orders' });
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Código incorreto ou expirado.';
    } finally {
        loading.value = false;
    }
}

async function submitRegister() {
    error.value = '';
    loading.value = true;
    try {
        const res = await axios.post('/api/customer/auth/register', {
            name:     regName.value,
            email:    regEmail.value,
            password: regPassword.value || undefined,
        });
        login(res.data.token, res.data.customer);
        router.push({ name: 'customer.orders' });
    } catch (e) {
        const errs = e.response?.data?.errors;
        error.value = errs ? Object.values(errs).flat().join(' ') : 'Erro ao criar conta.';
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <div class="min-h-screen flex items-center justify-center px-4 py-12"
         style="background:#131313;">
        <!-- Glow -->
        <div class="pointer-events-none fixed inset-0"
             style="background:radial-gradient(circle at center,rgba(200,40,48,0.15) 0%,rgba(0,0,0,1) 70%);z-index:0;" />

        <div class="relative z-10 w-full max-w-md">
            <!-- Logo -->
            <div class="text-center mb-8">
                <router-link to="/"
                             class="text-4xl font-black italic tracking-tighter text-white"
                             style="font-family:'Passion One',sans-serif;">
                    MadNutz
                </router-link>
                <p class="mt-2 text-sm font-bold uppercase tracking-widest"
                   style="color:rgba(255,255,255,0.4);">
                    {{ showRegister ? 'Criar conta' : 'Entrar na sua conta' }}
                </p>
            </div>

            <!-- Card -->
            <div class="rounded-2xl p-6 sm:p-8"
                 style="background:rgba(18,18,18,0.8);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);">

                <!-- Mensagens -->
                <div v-if="error" class="mb-4 px-4 py-3 rounded-lg text-sm font-bold"
                     style="background:rgba(255,0,60,0.1);border:1px solid rgba(255,0,60,0.3);color:#ff6b6b;">
                    {{ error }}
                </div>
                <div v-if="success && !error" class="mb-4 px-4 py-3 rounded-lg text-sm font-bold"
                     style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.3);color:#4ade80;">
                    {{ success }}
                </div>

                <!-- ── REGISTRO ── -->
                <template v-if="showRegister">
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.5);">Nome</label>
                            <input v-model="regName" type="text" placeholder="Seu nome completo"
                                   class="w-full px-4 py-3 rounded-lg text-sm font-bold bg-transparent outline-none transition-all"
                                   style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#fff;"
                                   @focus="$event.target.style.borderColor='rgba(255,0,60,0.5)'"
                                   @blur="$event.target.style.borderColor='rgba(255,255,255,0.12)'" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.5);">E-mail</label>
                            <input v-model="regEmail" type="email" placeholder="seu@email.com"
                                   class="w-full px-4 py-3 rounded-lg text-sm font-bold bg-transparent outline-none transition-all"
                                   style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#fff;"
                                   @focus="$event.target.style.borderColor='rgba(255,0,60,0.5)'"
                                   @blur="$event.target.style.borderColor='rgba(255,255,255,0.12)'" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.5);">
                                Senha <span style="color:rgba(255,255,255,0.25);">(opcional)</span>
                            </label>
                            <input v-model="regPassword" type="password" placeholder="Mínimo 6 caracteres"
                                   class="w-full px-4 py-3 rounded-lg text-sm font-bold bg-transparent outline-none transition-all"
                                   style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#fff;"
                                   @focus="$event.target.style.borderColor='rgba(255,0,60,0.5)'"
                                   @blur="$event.target.style.borderColor='rgba(255,255,255,0.12)'" />
                        </div>
                        <button @click="submitRegister" :disabled="loading"
                                class="w-full py-4 rounded-lg font-black uppercase tracking-widest text-base transition-all mt-2"
                                style="background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                            {{ loading ? 'Criando conta...' : 'CRIAR CONTA' }}
                        </button>
                    </div>
                    <p class="mt-5 text-center text-sm" style="color:rgba(255,255,255,0.4);">
                        Já tem conta?
                        <button @click="showRegister = false; error = ''"
                                class="font-bold transition-colors"
                                style="color:#FF003C;">
                            Entrar
                        </button>
                    </p>
                </template>

                <!-- ── LOGIN ── -->
                <template v-else>
                    <!-- Email -->
                    <div class="flex flex-col gap-1.5 mb-5">
                        <label class="text-xs font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.5);">E-mail</label>
                        <input v-model="email" type="email" placeholder="seu@email.com"
                               :disabled="otpSent"
                               class="w-full px-4 py-3 rounded-lg text-sm font-bold bg-transparent outline-none transition-all"
                               style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#fff;"
                               @focus="$event.target.style.borderColor='rgba(255,0,60,0.5)'"
                               @blur="$event.target.style.borderColor='rgba(255,255,255,0.12)'"
                               @keyup.enter="method === 'password' ? submitPassword() : sendOtp()" />
                    </div>

                    <!-- Toggle método (só antes de enviar OTP) -->
                    <div v-if="!otpSent" class="flex flex-col gap-2 mb-6">
                        <p class="text-xs font-bold uppercase tracking-widest mb-1" style="color:rgba(255,255,255,0.4);">Como quer entrar?</p>
                        <label v-for="opt in [
                                { value: 'otp',      label: 'Receber código por e-mail', sub: 'Sem senha — rápido e seguro' },
                                { value: 'password', label: 'Usar minha senha',           sub: 'Para quem já cadastrou senha' },
                               ]" :key="opt.value"
                               class="flex items-start gap-3 p-3 rounded-lg cursor-pointer transition-all"
                               :style="method === opt.value
                                   ? 'background:rgba(255,0,60,0.08);border:1px solid rgba(255,0,60,0.35)'
                                   : 'background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08)'">
                            <input type="radio" :value="opt.value" v-model="method" class="mt-0.5 accent-red-500" />
                            <div>
                                <p class="text-sm font-bold text-white">{{ opt.label }}</p>
                                <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.4);">{{ opt.sub }}</p>
                            </div>
                        </label>
                    </div>

                    <!-- Modo senha -->
                    <template v-if="method === 'password'">
                        <div class="flex flex-col gap-1.5 mb-5">
                            <label class="text-xs font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.5);">Senha</label>
                            <input v-model="password" type="password" placeholder="Sua senha"
                                   class="w-full px-4 py-3 rounded-lg text-sm font-bold bg-transparent outline-none transition-all"
                                   style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.12);color:#fff;"
                                   @focus="$event.target.style.borderColor='rgba(255,0,60,0.5)'"
                                   @blur="$event.target.style.borderColor='rgba(255,255,255,0.12)'"
                                   @keyup.enter="submitPassword" />
                        </div>
                        <button @click="submitPassword" :disabled="loading"
                                class="w-full py-4 rounded-lg font-black uppercase tracking-widest text-base transition-all"
                                style="background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                            {{ loading ? 'Entrando...' : 'ENTRAR' }}
                        </button>
                    </template>

                    <!-- Modo OTP -->
                    <template v-else>
                        <template v-if="!otpSent">
                            <button @click="sendOtp" :disabled="loading"
                                    class="w-full py-4 rounded-lg font-black uppercase tracking-widest text-base transition-all"
                                    style="background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                                {{ loading ? 'Enviando...' : 'ENVIAR CÓDIGO' }}
                            </button>
                        </template>
                        <template v-else>
                            <p class="text-sm text-center mb-4" style="color:rgba(255,255,255,0.5);">
                                Código enviado para <strong class="text-white">{{ email }}</strong>
                            </p>
                            <!-- Input 6 dígitos -->
                            <div class="flex gap-2 justify-center mb-5">
                                <input v-for="idx in 6" :key="idx"
                                       :id="`otp-${idx - 1}`"
                                       :value="otpCode[idx - 1]"
                                       type="text" maxlength="1" inputmode="numeric"
                                       class="w-11 h-14 text-center text-xl font-black rounded-lg outline-none transition-all"
                                       style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.15);color:#e6eb00;"
                                       @input="onOtpInput(idx - 1, $event)"
                                       @keydown="onOtpKeydown(idx - 1, $event)"
                                       @paste="onOtpPaste" />
                            </div>
                            <button @click="submitOtp" :disabled="loading || otpFull.length < 6"
                                    class="w-full py-4 rounded-lg font-black uppercase tracking-widest text-base transition-all"
                                    :style="otpFull.length === 6
                                        ? 'background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4)'
                                        : 'background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.3);cursor:not-allowed'">
                                {{ loading ? 'Verificando...' : 'CONFIRMAR CÓDIGO' }}
                            </button>
                            <button @click="otpSent = false; otpCode = ['','','','','','']; success = ''"
                                    class="w-full mt-3 text-xs font-bold uppercase tracking-widest transition-colors"
                                    style="color:rgba(255,255,255,0.3);">
                                Reenviar código
                            </button>
                        </template>
                    </template>

                    <p class="mt-6 text-center text-sm" style="color:rgba(255,255,255,0.4);">
                        Não tem conta?
                        <button @click="showRegister = true; error = ''"
                                class="font-bold transition-colors"
                                style="color:#FF003C;">
                            Criar agora
                        </button>
                    </p>
                </template>
            </div>
        </div>
    </div>
</template>
