<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useCart } from '../stores/cart.js';
import { useCustomer } from '../stores/customer.js';
import { b4youEnabled, createCheckout } from '../services/b4you.js';

const router = useRouter();

const props = defineProps({ open: Boolean });
const emit  = defineEmits(['close']);

const { items, total, coupon, discount, shipping, freeShipping, orderTotal, clear } = useCart();
const { customer, token, login } = useCustomer();

// ── Login inline ───────────────────────────────────────────────────────────────
const authTab        = ref('login');  // 'login' | 'register'
const authEmail      = ref('');
const authPassword   = ref('');
const authMethod     = ref('otp');   // 'otp' | 'password'
const authOtp        = ref(['', '', '', '', '', '']);
const authOtpSent    = ref(false);
const authRegName    = ref('');
const authRegEmail   = ref('');
const authRegPass    = ref('');
const authLoading    = ref(false);
const authError      = ref('');
const authSuccess    = ref('');

const authOtpFull = computed(() => authOtp.value.join(''));

function resetAuth() {
    authEmail.value = ''; authPassword.value = ''; authMethod.value = 'otp';
    authOtp.value = ['','','','','','']; authOtpSent.value = false;
    authRegName.value = ''; authRegEmail.value = ''; authRegPass.value = '';
    authLoading.value = false; authError.value = ''; authSuccess.value = '';
}

function onAuthOtpInput(idx, e) {
    const val = e.target.value.replace(/\D/g,'').slice(-1);
    authOtp.value[idx] = val;
    if (val && idx < 5) document.getElementById(`co-otp-${idx+1}`)?.focus();
}
function onAuthOtpKeydown(idx, e) {
    if (e.key === 'Backspace' && !authOtp.value[idx] && idx > 0)
        document.getElementById(`co-otp-${idx-1}`)?.focus();
}
function onAuthOtpPaste(e) {
    const p = e.clipboardData.getData('text').replace(/\D/g,'').slice(0,6);
    p.split('').forEach((c,i) => { authOtp.value[i] = c; });
    document.getElementById(`co-otp-${Math.min(p.length,5)}`)?.focus();
    e.preventDefault();
}

async function authSendOtp() {
    authError.value = '';
    if (!authEmail.value) { authError.value = 'Informe seu e-mail.'; return; }
    authLoading.value = true;
    try {
        await axios.post('/api/customer/auth/send-otp', { email: authEmail.value });
        authOtpSent.value = true;
        authSuccess.value = 'Código enviado! Verifique seu e-mail.';
    } catch(e) { authError.value = e.response?.data?.message ?? 'Erro ao enviar código.'; }
    finally { authLoading.value = false; }
}

async function authSubmitPassword() {
    authError.value = '';
    authLoading.value = true;
    try {
        const res = await axios.post('/api/customer/auth/login-password', {
            email: authEmail.value, password: authPassword.value,
        });
        login(res.data.token, res.data.customer);
        resetAuth();
        form.value.customer_name  = res.data.customer.name  ?? '';
        form.value.customer_email = res.data.customer.email ?? '';
        form.value.customer_phone = res.data.customer.phone ?? '';
    } catch(e) { authError.value = e.response?.data?.message ?? 'Credenciais inválidas.'; }
    finally { authLoading.value = false; }
}

async function authSubmitOtp() {
    authError.value = '';
    if (authOtpFull.value.length < 6) { authError.value = 'Digite os 6 dígitos.'; return; }
    authLoading.value = true;
    try {
        const res = await axios.post('/api/customer/auth/verify-otp', {
            email: authEmail.value, code: authOtpFull.value,
        });
        login(res.data.token, res.data.customer);
        resetAuth();
        form.value.customer_name  = res.data.customer.name  ?? '';
        form.value.customer_email = res.data.customer.email ?? '';
        form.value.customer_phone = res.data.customer.phone ?? '';
    } catch(e) { authError.value = e.response?.data?.message ?? 'Código incorreto ou expirado.'; }
    finally { authLoading.value = false; }
}

async function authSubmitRegister() {
    authError.value = '';
    authLoading.value = true;
    try {
        const res = await axios.post('/api/customer/auth/register', {
            name: authRegName.value, email: authRegEmail.value,
            password: authRegPass.value || undefined,
        });
        login(res.data.token, res.data.customer);
        resetAuth();
        form.value.customer_name  = res.data.customer.name  ?? '';
        form.value.customer_email = res.data.customer.email ?? '';
        form.value.customer_phone = res.data.customer.phone ?? '';
    } catch(e) {
        const errs = e.response?.data?.errors;
        authError.value = errs ? Object.values(errs).flat().join(' ') : 'Erro ao criar conta.';
    }
    finally { authLoading.value = false; }
}

const step      = ref(1);
const direction = ref(1);
const loading   = ref(false);
const orderDone = ref(null);
const errors    = ref({});

const form = ref({
    customer_name:    '',
    customer_email:   '',
    customer_phone:   '',
    customer_address: { zip: '', street: '', city: '', state: '' },
});

// ── Address state ──────────────────────────────────────────────────────────────
const savedAddresses    = ref([]);
const selectedAddrId    = ref(null);
const addressMode       = ref('select'); // 'select' | 'new'
const saveAddrPrompt    = ref(false);
const savingAddr        = ref(false);
const cepLoading        = ref(false);
const newAddr = ref({ zip: '', street: '', number: '', complement: '', city: '', state: '' });

const fmt = v => Number(v).toFixed(2).replace('.', ',');

const canNext1 = computed(() =>
    customer.value
        ? true
        : form.value.customer_name.trim().length > 1 && form.value.customer_email.trim().includes('@')
);

const canNext2 = computed(() =>
    addressMode.value === 'select'
        ? !!selectedAddrId.value
        : newAddr.value.street.trim().length > 1 && newAddr.value.city.trim().length > 1
);

const stepLabels = ['Dados', 'Endereço', 'Confirmar'];

async function loadAddresses() {
    if (!token.value) return;
    try {
        const res = await axios.get('/api/customer/addresses', {
            headers: { Authorization: `Bearer ${token.value}` },
        });
        savedAddresses.value = res.data;
        if (savedAddresses.value.length > 0) {
            addressMode.value = 'select';
            const def = savedAddresses.value.find(a => a.is_default) ?? savedAddresses.value[0];
            selectAddress(def);
        } else {
            addressMode.value = 'new';
        }
    } catch {
        addressMode.value = 'new';
    }
}

function selectAddress(addr) {
    selectedAddrId.value = addr.id;
    form.value.customer_address = {
        zip:    addr.zip,
        street: [addr.street, addr.number, addr.complement].filter(Boolean).join(', '),
        city:   addr.city,
        state:  addr.state,
    };
}

async function fetchCep() {
    const cep = newAddr.value.zip.replace(/\D/g, '');
    if (cep.length !== 8) return;
    cepLoading.value = true;
    try {
        const res  = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await res.json();
        if (!data.erro) {
            newAddr.value.street = data.logradouro ?? '';
            newAddr.value.city   = data.localidade ?? '';
            newAddr.value.state  = data.uf ?? '';
        }
    } catch {}
    finally { cepLoading.value = false; }
}

async function goNextStep1() {
    // Salva telefone silenciosamente se mudou
    if (token.value && customer.value && form.value.customer_phone !== (customer.value.phone ?? '')) {
        try {
            await axios.put('/api/customer/profile',
                { name: customer.value.name, email: customer.value.email, phone: form.value.customer_phone },
                { headers: { Authorization: `Bearer ${token.value}` } }
            );
        } catch {}
    }
    direction.value = 1;
    step.value++;
    await loadAddresses();
}

async function goNextStep2() {
    if (addressMode.value === 'new') {
        form.value.customer_address = {
            zip:    newAddr.value.zip,
            street: [newAddr.value.street, newAddr.value.number, newAddr.value.complement].filter(Boolean).join(', '),
            city:   newAddr.value.city,
            state:  newAddr.value.state,
        };
        // Logado: pergunta se quer salvar
        if (token.value && !saveAddrPrompt.value) {
            saveAddrPrompt.value = true;
            return;
        }
    }
    saveAddrPrompt.value = false;
    direction.value = 1;
    step.value++;
}

async function handleSaveAddr(save) {
    if (save) {
        savingAddr.value = true;
        try {
            await axios.post('/api/customer/addresses', {
                label:      'Entrega',
                zip:        newAddr.value.zip.replace(/\D/g, ''),
                street:     newAddr.value.street,
                number:     newAddr.value.number || null,
                complement: newAddr.value.complement || null,
                city:       newAddr.value.city,
                state:      newAddr.value.state,
            }, { headers: { Authorization: `Bearer ${token.value}` } });
        } catch {}
        finally { savingAddr.value = false; }
    }
    saveAddrPrompt.value = false;
    direction.value = 1;
    step.value++;
}

// ── Showcase ──────────────────────────────────────────────────────────────────
const showcaseImages   = ref([]);
const showcaseIdx      = ref(0);
const cashewParticles  = ref([]);
let showcaseTimer = null;
let cashewTimer   = null;
let _cpid = 0;

async function loadShowcase() {
    if (showcaseImages.value.length) return;
    try {
        const res = await axios.get('/api/products');
        const all = res.data.data ?? res.data;
        // deduplicate by name, take up to 3
        const seen = new Set();
        showcaseImages.value = all.filter(p => {
            if (seen.has(p.name)) return false;
            seen.add(p.name);
            return true;
        }).slice(0, 3);
    } catch {}
}

function startShowcase() {
    clearInterval(showcaseTimer);
    showcaseTimer = setInterval(() => {
        showcaseIdx.value = (showcaseIdx.value + 1) % Math.max(showcaseImages.value.length, 1);
    }, 3800);
}

function stopShowcase() {
    clearInterval(showcaseTimer);
    showcaseTimer = null;
}

function spawnCashews() {
    for (let i = 0; i < 10; i++) {
        const id  = _cpid++;
        const dur = 2.2 + Math.random() * 1.6;
        const delay = Math.random() * 1.4;
        cashewParticles.value.push({
            id,
            style: [
                `left:${2 + Math.random() * 96}%`,
                `width:${16 + Math.random() * 22}px`,
                `--dur:${dur}s`,
                `--delay:${delay}s`,
                `--rot0:${Math.random() * 360}deg`,
                `--rot1:${Math.random() * 360 + 200}deg`,
                `--drift:${(Math.random() - 0.5) * 70}px`,
            ].join(';'),
        });
        setTimeout(() => {
            cashewParticles.value = cashewParticles.value.filter(x => x.id !== id);
        }, (delay + dur + 0.3) * 1000);
    }
}

function startCashewRain() {
    spawnCashews();
    cashewTimer = setInterval(spawnCashews, 2800);
}

function stopCashewRain() {
    clearInterval(cashewTimer);
    cashewTimer = null;
    cashewParticles.value = [];
}

watch(() => props.open, async val => {
    if (!val) { stopShowcase(); stopCashewRain(); resetAuth(); return; }
    step.value          = 1;
    orderDone.value     = null;
    errors.value        = {};
    showcaseIdx.value   = 0;
    saveAddrPrompt.value = false;
    savedAddresses.value = [];
    selectedAddrId.value = null;
    addressMode.value    = 'select';
    newAddr.value        = { zip: '', street: '', number: '', complement: '', city: '', state: '' };
    resetAuth();

    if (customer.value) {
        form.value.customer_name  = customer.value.name  ?? '';
        form.value.customer_email = customer.value.email ?? '';
        form.value.customer_phone = customer.value.phone ?? '';
    } else {
        form.value.customer_name  = '';
        form.value.customer_email = '';
        form.value.customer_phone = '';
    }
    form.value.customer_address = { zip: '', street: '', city: '', state: '' };

    await loadShowcase();
    startShowcase();
    startCashewRain();
});

function goNext() { direction.value =  1; step.value++; }
function goBack() { direction.value = -1; step.value--; }

function onKeydown(e) {
    if (e.key === 'Escape') emit('close');
}
onMounted(()  => window.addEventListener('keydown', onKeydown));
onUnmounted(() => { window.removeEventListener('keydown', onKeydown); stopShowcase(); stopCashewRain(); });

async function submit() {
    errors.value  = {};
    loading.value = true;
    try {
        const headers = token.value ? { Authorization: `Bearer ${token.value}` } : {};
        const payload = {
            ...form.value,
            coupon_code: coupon.value?.code ?? null,
            items: items.value.map(i => ({
                name:         i.name,
                price:        i.price,
                qty:          i.qty,
                product_id:   i.product_id ?? null,
                is_custom:    i.is_custom ?? false,
                custom_items: i.custom_items ?? null,
            })),
        };

        const { data: order } = await axios.post('/api/orders', payload, { headers });

        if (b4youEnabled) {
            const url = await createCheckout(order, {
                name:  form.value.customer_name,
                email: form.value.customer_email,
                phone: form.value.customer_phone,
            }, items.value);
            clear();
            window.location.href = url;
        } else {
            clear();
            orderDone.value = order;
        }
    } catch (err) {
        if (err.response?.status === 422) {
            errors.value = err.response.data.errors ?? {};
            const s1 = ['customer_name', 'customer_email', 'customer_phone'];
            if (s1.some(f => errors.value[f])) step.value = 1;
        }
    } finally {
        loading.value = false;
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="backdrop">
            <div v-if="open"
                 class="fixed inset-0 z-50"
                 style="background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);"
                 @click.self="$emit('close')" />
        </Transition>

        <!-- ── LEFT PRODUCT SHOWCASE (desktop only) ── -->
        <Transition name="backdrop">
            <div v-if="open"
                 class="showcase-wrapper fixed top-0 bottom-0 left-0 z-[51] overflow-hidden"
                 style="right:min(480px,100vw);background:#0a0a0a;cursor:pointer;"
                 @click="$emit('close')">

                <!-- Halftone texture -->
                <div class="absolute inset-0 pointer-events-none"
                     style="background-image:radial-gradient(circle,rgba(255,255,255,0.025) 1px,transparent 1px);background-size:22px 22px;" />

                <!-- Cashew rain -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden" style="z-index:2;">
                    <div v-for="p in cashewParticles" :key="p.id"
                         class="cashew-particle absolute"
                         :style="p.style">
                        <svg viewBox="0 0 56 78" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                            <defs>
                                <filter id="scf" x="-20%" y="-20%" width="140%" height="140%">
                                    <feGaussianBlur in="SourceAlpha" stdDeviation="1.5" result="blur"/>
                                    <feOffset dx="1" dy="2" result="shadow"/>
                                    <feFlood flood-color="rgba(0,0,0,0.3)" result="color"/>
                                    <feComposite in="color" in2="shadow" operator="in" result="sc"/>
                                    <feMerge><feMergeNode in="sc"/><feMergeNode in="SourceGraphic"/></feMerge>
                                </filter>
                            </defs>
                            <path d="M29,5 C44,4 54,16 53,30 C52,44 44,58 34,66 C25,73 13,73 8,63 C3,54 7,42 12,33 C17,24 17,16 14,10 C11,5 18,2 29,5 Z"
                                  fill="#ECD89A" stroke="#C4A060" stroke-width="0.8" filter="url(#scf)"/>
                            <path d="M29,5 C44,4 54,16 53,30 C52,44 44,58 34,66"
                                  fill="none" stroke="rgba(180,130,60,0.4)" stroke-width="5" stroke-linecap="round"/>
                            <path d="M22,8 C32,6 44,14 46,26"
                                  fill="none" stroke="rgba(255,255,255,0.75)" stroke-width="3.5" stroke-linecap="round"/>
                            <circle cx="36" cy="16" r="2.2" fill="rgba(255,255,255,0.65)"/>
                            <circle cx="44" cy="26" r="1.5" fill="rgba(255,255,255,0.5)"/>
                            <circle cx="40" cy="40" r="1.8" fill="rgba(255,255,255,0.45)"/>
                            <circle cx="22" cy="55" r="1.3" fill="rgba(255,255,255,0.4)"/>
                            <circle cx="15" cy="38" r="1"   fill="rgba(255,255,255,0.35)"/>
                        </svg>
                    </div>
                </div>

                <!-- Animated color glow behind image -->
                <div class="absolute pointer-events-none transition-all duration-1000"
                     style="width:500px;height:500px;border-radius:50%;filter:blur(80px);transform:translate(-50%,-50%);top:50%;left:50%;background:radial-gradient(circle,rgba(255,0,60,0.22) 0%,transparent 70%);" />

                <!-- Product image + info -->
                <Transition name="showcase" mode="out-in">
                    <div v-if="showcaseImages.length === 0" key="loading"
                         class="w-16 h-16 rounded-full border-2 animate-spin"
                         style="border-color:rgba(255,0,60,0.3);border-top-color:#FF003C;" />
                    <div v-else
                         :key="showcaseIdx"
                         class="relative flex flex-col items-center gap-5 px-12 text-center"
                         @click.stop>

                        <!-- Floating image or placeholder -->
                        <div class="relative">
                            <div class="absolute inset-0 pointer-events-none"
                                 style="border-radius:50%;filter:blur(40px);transform:scale(0.7) translateY(30%);background:rgba(0,0,0,0.7);" />
                            <img v-if="showcaseImages[showcaseIdx].thumbnail"
                                 :src="showcaseImages[showcaseIdx].thumbnail"
                                 :alt="showcaseImages[showcaseIdx].name"
                                 class="showcase-float relative z-10"
                                 style="width:280px;height:280px;object-fit:contain;filter:drop-shadow(0 20px 40px rgba(0,0,0,0.9));"
                                 draggable="false" />
                            <!-- Fallback quando não há imagem -->
                            <div v-else
                                 class="showcase-float relative z-10 rounded-3xl flex items-center justify-center"
                                 style="width:240px;height:240px;background:linear-gradient(135deg,rgba(255,0,60,0.2),rgba(230,235,0,0.1));border:2px solid rgba(255,0,60,0.3);box-shadow:0 0 60px rgba(255,0,60,0.15);">
                                <i class="pi pi-box" style="font-size:64px;color:rgba(255,82,92,0.5);" />
                            </div>
                        </div>

                        <!-- Name + price -->
                        <div>
                            <p class="font-black uppercase leading-tight text-white"
                               style="font-family:'Passion One',sans-serif;font-size:2.2rem;letter-spacing:-0.02em;">
                                {{ showcaseImages[showcaseIdx].name }}
                            </p>
                            <p class="font-black text-2xl mt-1" style="color:#e6eb00;">
                                R$ {{ fmt(showcaseImages[showcaseIdx].price) }}
                            </p>
                        </div>
                    </div>

                </Transition>

                <!-- Dot navigation -->
                <div v-if="showcaseImages.length > 1"
                     class="absolute bottom-10 flex items-center gap-2"
                     @click.stop>
                    <button v-for="(_, i) in showcaseImages" :key="i"
                            class="rounded-full transition-all duration-300 hover:opacity-100"
                            :style="i === showcaseIdx
                                ? 'width:28px;height:6px;background:#FF003C;opacity:1'
                                : 'width:6px;height:6px;background:rgba(255,255,255,0.3);opacity:0.6'"
                            @click="showcaseIdx = i; startShowcase()" />
                </div>

                <!-- Close hint -->
                <p class="absolute bottom-4 text-xs font-bold uppercase tracking-widest"
                   style="color:rgba(255,255,255,0.12);">
                    Clique para fechar
                </p>
            </div>
        </Transition>

        <Transition name="drawer">
            <div v-if="open"
                 class="fixed right-0 top-0 bottom-0 z-50 flex flex-col"
                 style="width:min(480px,100vw);background:#111;border-left:1px solid rgba(255,255,255,0.08);">

                <!-- Glow top-right -->
                <div class="pointer-events-none absolute top-0 right-0 w-64 h-64"
                     style="background:radial-gradient(circle at top right,rgba(255,0,60,0.18) 0%,transparent 70%);z-index:0;" />

                <!-- ── HEADER ── -->
                <div class="relative z-10 flex items-center justify-between px-6 pt-6 pb-4"
                     style="border-bottom:1px solid rgba(255,255,255,0.06);">
                    <div>
                        <p class="text-xs font-black uppercase tracking-widest mb-0.5"
                           style="color:rgba(255,82,92,0.7);">Checkout</p>
                        <h2 class="font-black uppercase text-white text-xl tracking-tight"
                            style="font-family:'Passion One',sans-serif;">
                            {{ orderDone ? 'Pedido Confirmado' : !customer ? (authTab === 'register' ? 'Criar Conta' : 'Identificação') : stepLabels[step - 1] }}
                        </h2>
                    </div>
                    <button @click="$emit('close')"
                            class="w-9 h-9 rounded-full flex items-center justify-center transition-all"
                            style="color:rgba(255,255,255,0.35);border:1px solid rgba(255,255,255,0.1);"
                            @mouseenter="$event.currentTarget.style.color='#fff';$event.currentTarget.style.borderColor='rgba(255,255,255,0.3)'"
                            @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.35)';$event.currentTarget.style.borderColor='rgba(255,255,255,0.1)'">
                        <i class="pi pi-times" style="font-size:12px;" />
                    </button>
                </div>

                <!-- ── STEP INDICATOR ── -->
                <div v-if="!orderDone && customer"
                     class="relative z-10 flex items-center px-6 py-4 gap-0"
                     style="border-bottom:1px solid rgba(255,255,255,0.05);">
                    <template v-for="(label, i) in stepLabels" :key="i">
                        <div class="flex items-center gap-2">
                            <!-- Dot -->
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-black transition-all duration-400"
                                 :style="step > i + 1
                                     ? 'background:rgba(74,222,128,0.2);border:1.5px solid rgba(74,222,128,0.6);color:rgba(74,222,128,0.9)'
                                     : step === i + 1
                                         ? 'background:rgba(255,0,60,0.15);border:1.5px solid #FF003C;color:#fff;box-shadow:0 0 10px rgba(255,0,60,0.4)'
                                         : 'background:transparent;border:1.5px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.25)'">
                                <i v-if="step > i + 1" class="pi pi-check" style="font-size:10px;" />
                                <span v-else>{{ i + 1 }}</span>
                            </div>
                            <!-- Label -->
                            <span class="text-xs font-black uppercase tracking-widest transition-colors duration-300"
                                  :style="step === i + 1
                                      ? 'color:#fff'
                                      : step > i + 1
                                          ? 'color:rgba(74,222,128,0.7)'
                                          : 'color:rgba(255,255,255,0.2)'">
                                {{ label }}
                            </span>
                        </div>
                        <!-- Connector line -->
                        <div v-if="i < stepLabels.length - 1"
                             class="flex-1 h-px mx-3 transition-all duration-500"
                             :style="step > i + 1
                                 ? 'background:rgba(74,222,128,0.4)'
                                 : 'background:rgba(255,255,255,0.08)'" />
                    </template>
                </div>

                <!-- ── SCROLL CONTENT ── -->
                <div class="flex-1 overflow-y-auto relative z-10">

                    <!-- AUTH STATE (não logado) -->
                    <div v-if="!customer && !orderDone" class="px-6 py-6 flex flex-col gap-5">

                        <!-- Tabs -->
                        <div class="flex rounded-xl overflow-hidden"
                             style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
                            <button v-for="tab in [['login','Entrar'],['register','Criar conta']]" :key="tab[0]"
                                    @click="authTab = tab[0]; authError = ''; authSuccess = ''"
                                    class="flex-1 py-2.5 text-xs font-black uppercase tracking-widest transition-all"
                                    :style="authTab === tab[0]
                                        ? 'background:#FF003C;color:#fff'
                                        : 'color:rgba(255,255,255,0.4)'">
                                {{ tab[1] }}
                            </button>
                        </div>

                        <!-- Mensagens -->
                        <div v-if="authError" class="px-4 py-3 rounded-lg text-sm font-bold"
                             style="background:rgba(255,0,60,0.1);border:1px solid rgba(255,0,60,0.3);color:#ff6b6b;">
                            {{ authError }}
                        </div>
                        <div v-if="authSuccess && !authError" class="px-4 py-3 rounded-lg text-sm font-bold"
                             style="background:rgba(74,222,128,0.1);border:1px solid rgba(74,222,128,0.3);color:#4ade80;">
                            {{ authSuccess }}
                        </div>

                        <!-- ── ENTRAR ── -->
                        <template v-if="authTab === 'login'">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-widest" style="color:rgba(255,255,255,0.4);">E-mail</label>
                                <input v-model="authEmail" type="email" placeholder="seu@email.com"
                                       :disabled="authOtpSent"
                                       class="drawer-input"
                                       @keyup.enter="authMethod === 'password' ? authSubmitPassword() : authSendOtp()" />
                            </div>

                            <div v-if="!authOtpSent" class="flex flex-col gap-2">
                                <p class="text-xs font-bold uppercase tracking-widest" style="color:rgba(255,255,255,0.3);">Como quer entrar?</p>
                                <label v-for="opt in [
                                        {value:'otp',      label:'Código por e-mail', sub:'Sem senha — rápido e seguro'},
                                        {value:'password', label:'Usar minha senha',  sub:'Para quem já cadastrou senha'},
                                       ]" :key="opt.value"
                                       class="flex items-start gap-3 p-3 rounded-lg cursor-pointer transition-all"
                                       :style="authMethod === opt.value
                                           ? 'background:rgba(255,0,60,0.08);border:1px solid rgba(255,0,60,0.35)'
                                           : 'background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.08)'">
                                    <input type="radio" :value="opt.value" v-model="authMethod" class="mt-0.5 accent-red-500" />
                                    <div>
                                        <p class="text-sm font-bold text-white">{{ opt.label }}</p>
                                        <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.35);">{{ opt.sub }}</p>
                                    </div>
                                </label>
                            </div>

                            <!-- Senha -->
                            <template v-if="authMethod === 'password'">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-xs font-black uppercase tracking-widest" style="color:rgba(255,255,255,0.4);">Senha</label>
                                    <input v-model="authPassword" type="password" placeholder="Sua senha"
                                           class="drawer-input" @keyup.enter="authSubmitPassword" />
                                </div>
                                <button @click="authSubmitPassword" :disabled="authLoading"
                                        class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-sm text-white transition-all disabled:opacity-60"
                                        style="background:#FF003C;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                                    {{ authLoading ? 'Entrando...' : 'ENTRAR' }}
                                </button>
                            </template>

                            <!-- OTP -->
                            <template v-else>
                                <button v-if="!authOtpSent" @click="authSendOtp" :disabled="authLoading"
                                        class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-sm text-white transition-all disabled:opacity-60"
                                        style="background:#FF003C;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                                    {{ authLoading ? 'Enviando...' : 'ENVIAR CÓDIGO' }}
                                </button>
                                <template v-else>
                                    <p class="text-sm text-center" style="color:rgba(255,255,255,0.45);">
                                        Código enviado para <strong class="text-white">{{ authEmail }}</strong>
                                    </p>
                                    <div class="flex gap-2 justify-center">
                                        <input v-for="idx in 6" :key="idx"
                                               :id="`co-otp-${idx-1}`" :value="authOtp[idx-1]"
                                               type="text" maxlength="1" inputmode="numeric"
                                               class="w-10 h-13 text-center text-xl font-black rounded-lg outline-none transition-all"
                                               style="height:52px;background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.15);color:#e6eb00;"
                                               @input="onAuthOtpInput(idx-1, $event)"
                                               @keydown="onAuthOtpKeydown(idx-1, $event)"
                                               @paste="onAuthOtpPaste" />
                                    </div>
                                    <button @click="authSubmitOtp" :disabled="authLoading || authOtpFull.length < 6"
                                            class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-sm transition-all"
                                            :style="authOtpFull.length === 6
                                                ? 'background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4)'
                                                : 'background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.3);cursor:not-allowed'">
                                        {{ authLoading ? 'Verificando...' : 'CONFIRMAR CÓDIGO' }}
                                    </button>
                                    <button @click="authOtpSent = false; authOtp = ['','','','','','']; authSuccess = ''"
                                            class="text-xs font-bold uppercase tracking-widest transition-colors text-center w-full"
                                            style="color:rgba(255,255,255,0.3);">
                                        Reenviar código
                                    </button>
                                </template>
                            </template>
                        </template>

                        <!-- ── CRIAR CONTA ── -->
                        <template v-else>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-widest" style="color:rgba(255,255,255,0.4);">Nome</label>
                                <input v-model="authRegName" type="text" placeholder="Seu nome completo" class="drawer-input" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-widest" style="color:rgba(255,255,255,0.4);">E-mail</label>
                                <input v-model="authRegEmail" type="email" placeholder="seu@email.com" class="drawer-input" />
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-xs font-black uppercase tracking-widest" style="color:rgba(255,255,255,0.4);">
                                    Senha <span style="color:rgba(255,255,255,0.2);">(opcional)</span>
                                </label>
                                <input v-model="authRegPass" type="password" placeholder="Mínimo 6 caracteres"
                                       class="drawer-input" @keyup.enter="authSubmitRegister" />
                            </div>
                            <button @click="authSubmitRegister" :disabled="authLoading"
                                    class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-sm text-white transition-all disabled:opacity-60"
                                    style="background:#FF003C;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                                {{ authLoading ? 'Criando conta...' : 'CRIAR CONTA' }}
                            </button>
                        </template>
                    </div>

                    <!-- SUCCESS STATE -->
                    <div v-if="orderDone"
                         class="flex flex-col items-center justify-center h-full px-8 py-16 text-center gap-6">
                        <div class="w-20 h-20 rounded-full flex items-center justify-center"
                             style="background:rgba(74,222,128,0.12);border:2px solid rgba(74,222,128,0.4);">
                            <i class="pi pi-check" style="font-size:32px;color:rgba(74,222,128,0.9);" />
                        </div>
                        <div>
                            <p class="text-xs font-black uppercase tracking-widest mb-2"
                               style="color:rgba(74,222,128,0.7);">Pedido recebido!</p>
                            <p class="font-black text-3xl text-white uppercase tracking-tight mb-1"
                               style="font-family:'Passion One',sans-serif;">
                                {{ orderDone.order_number }}
                            </p>
                            <p class="text-sm font-bold" style="color:rgba(255,255,255,0.4);">
                                Você receberá a confirmação por e-mail
                            </p>
                        </div>
                        <div class="font-black text-2xl" style="color:#e6eb00;">
                            R$ {{ fmt(orderDone.total) }}
                        </div>
                        <div class="flex flex-col gap-3 w-full">
                            <button @click="() => { $emit('close'); router.push({ name: 'customer.orders' }); }"
                                    class="w-full py-4 rounded-xl font-black uppercase tracking-widest text-sm text-white transition-all"
                                    style="background:#FF003C;box-shadow:0 4px 20px rgba(255,0,60,0.4);">
                                <i class="pi pi-list mr-2" style="font-size:13px;" />
                                Ver meus pedidos
                            </button>
                            <button @click="$emit('close')"
                                    class="w-full py-3 rounded-xl font-bold uppercase tracking-widest text-xs transition-all"
                                    style="color:rgba(255,255,255,0.4);border:1px solid rgba(255,255,255,0.1);"
                                    @mouseenter="$event.currentTarget.style.color='#fff';$event.currentTarget.style.borderColor='rgba(255,255,255,0.3)'"
                                    @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.4)';$event.currentTarget.style.borderColor='rgba(255,255,255,0.1)'">
                                Continuar comprando
                            </button>
                        </div>
                    </div>

                    <!-- STEP CONTENT (animated) -->
                    <template v-else>
                        <Transition :name="direction > 0 ? 'step-fwd' : 'step-bck'" mode="out-in">

                            <!-- STEP 1: Dados pessoais -->
                            <div v-if="step === 1" key="step1" class="px-6 py-6 flex flex-col gap-4">
                                <p class="text-xs font-bold uppercase tracking-widest"
                                   style="color:rgba(255,255,255,0.3);">
                                    Quem vai receber o pedido?
                                </p>

                                <!-- Logado: nome + e-mail bloqueados, só whatsapp editável -->
                                <template v-if="customer">
                                    <div class="rounded-xl px-4 py-3 flex flex-col gap-3"
                                         style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-xs font-black uppercase tracking-widest mb-0.5"
                                                   style="color:rgba(255,255,255,0.3);">Nome</p>
                                                <p class="text-sm font-bold text-white truncate">{{ customer.name }}</p>
                                            </div>
                                            <i class="pi pi-lock flex-shrink-0" style="font-size:11px;color:rgba(255,255,255,0.2);" />
                                        </div>
                                        <div style="border-top:1px solid rgba(255,255,255,0.06);" />
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="min-w-0">
                                                <p class="text-xs font-black uppercase tracking-widest mb-0.5"
                                                   style="color:rgba(255,255,255,0.3);">E-mail</p>
                                                <p class="text-sm font-bold text-white truncate">{{ customer.email }}</p>
                                            </div>
                                            <i class="pi pi-lock flex-shrink-0" style="font-size:11px;color:rgba(255,255,255,0.2);" />
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-widest"
                                               style="color:rgba(255,255,255,0.4);">WhatsApp</label>
                                        <input v-model="form.customer_phone" type="text"
                                               placeholder="(11) 99999-9999"
                                               class="drawer-input" />
                                        <p v-if="errors.customer_phone?.[0]" class="text-xs font-bold" style="color:#ff6b6b;">
                                            {{ errors.customer_phone[0] }}
                                        </p>
                                    </div>
                                </template>

                                <!-- Visitante: preenche tudo -->
                                <template v-else>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-widest"
                                               style="color:rgba(255,255,255,0.4);">Nome completo</label>
                                        <input v-model="form.customer_name" type="text"
                                               placeholder="Seu nome completo"
                                               class="drawer-input" />
                                        <p v-if="errors.customer_name?.[0]" class="text-xs font-bold" style="color:#ff6b6b;">
                                            {{ errors.customer_name[0] }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-widest"
                                               style="color:rgba(255,255,255,0.4);">E-mail</label>
                                        <input v-model="form.customer_email" type="email"
                                               placeholder="seu@email.com"
                                               class="drawer-input" />
                                        <p v-if="errors.customer_email?.[0]" class="text-xs font-bold" style="color:#ff6b6b;">
                                            {{ errors.customer_email[0] }}
                                        </p>
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-widest"
                                               style="color:rgba(255,255,255,0.4);">WhatsApp</label>
                                        <input v-model="form.customer_phone" type="text"
                                               placeholder="(11) 99999-9999"
                                               class="drawer-input" />
                                        <p v-if="errors.customer_phone?.[0]" class="text-xs font-bold" style="color:#ff6b6b;">
                                            {{ errors.customer_phone[0] }}
                                        </p>
                                    </div>
                                </template>
                            </div>

                            <!-- STEP 2: Endereço -->
                            <div v-else-if="step === 2" key="step2" class="px-6 py-6 flex flex-col gap-4">
                                <p class="text-xs font-bold uppercase tracking-widest"
                                   style="color:rgba(255,255,255,0.3);">
                                    Para onde enviamos?
                                </p>

                                <!-- Endereços salvos (logado + tem endereços) -->
                                <template v-if="token && addressMode === 'select' && savedAddresses.length > 0">
                                    <div class="flex flex-col gap-2">
                                        <div v-for="addr in savedAddresses" :key="addr.id"
                                             class="flex items-start gap-3 p-4 rounded-xl cursor-pointer transition-all"
                                             :style="selectedAddrId === addr.id
                                                 ? 'background:rgba(255,0,60,0.1);border:1.5px solid rgba(255,0,60,0.5)'
                                                 : 'background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08)'"
                                             @click="selectAddress(addr)">
                                            <div class="mt-0.5 w-4 h-4 rounded-full flex-shrink-0 flex items-center justify-center"
                                                 :style="selectedAddrId === addr.id
                                                     ? 'background:#FF003C;border:2px solid #FF003C'
                                                     : 'border:2px solid rgba(255,255,255,0.2)'">
                                                <div v-if="selectedAddrId === addr.id"
                                                     class="w-1.5 h-1.5 rounded-full bg-white" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center gap-2 mb-0.5">
                                                    <span class="text-xs font-black uppercase tracking-widest"
                                                          style="color:rgba(255,255,255,0.6);">{{ addr.label }}</span>
                                                    <span v-if="addr.is_default"
                                                          class="text-[10px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded"
                                                          style="background:rgba(255,0,60,0.15);color:#ff525c;">Padrão</span>
                                                </div>
                                                <p class="text-sm font-bold text-white truncate">
                                                    {{ addr.street }}{{ addr.number ? `, ${addr.number}` : '' }}
                                                </p>
                                                <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.35);">
                                                    {{ addr.city }}/{{ addr.state }}
                                                    <span v-if="addr.zip"> · {{ addr.zip }}</span>
                                                </p>
                                                <p v-if="addr.recipient_name" class="text-xs mt-1 font-bold" style="color:#e6eb00;">
                                                    <i class="pi pi-gift mr-1" style="font-size:9px;" />
                                                    Para: {{ addr.recipient_name }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <button @click="addressMode = 'new'; selectedAddrId = null"
                                            class="flex items-center justify-center gap-2 py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all"
                                            style="border:1px dashed rgba(255,255,255,0.15);color:rgba(255,255,255,0.4);"
                                            @mouseenter="$event.currentTarget.style.borderColor='rgba(255,0,60,0.4)';$event.currentTarget.style.color='#ff525c'"
                                            @mouseleave="$event.currentTarget.style.borderColor='rgba(255,255,255,0.15)';$event.currentTarget.style.color='rgba(255,255,255,0.4)'">
                                        <i class="pi pi-plus" style="font-size:11px;" />
                                        Usar outro endereço
                                    </button>
                                </template>

                                <!-- Formulário de novo endereço -->
                                <template v-else>
                                    <!-- Voltar para lista (se tem endereços salvos) -->
                                    <button v-if="token && savedAddresses.length > 0"
                                            @click="addressMode = 'select'; saveAddrPrompt = false"
                                            class="flex items-center gap-1.5 text-xs font-bold uppercase tracking-widest transition-colors self-start"
                                            style="color:rgba(255,255,255,0.35);"
                                            @mouseenter="$event.currentTarget.style.color='rgba(255,255,255,0.7)'"
                                            @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.35)'">
                                        <i class="pi pi-arrow-left" style="font-size:10px;" />
                                        Ver endereços salvos
                                    </button>

                                    <!-- CEP com auto-fill -->
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-widest"
                                               style="color:rgba(255,255,255,0.4);">CEP</label>
                                        <div class="flex gap-2">
                                            <input v-model="newAddr.zip" type="text"
                                                   placeholder="00000-000" maxlength="9"
                                                   class="drawer-input flex-1"
                                                   @blur="fetchCep" />
                                            <button @click="fetchCep"
                                                    class="px-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all flex-shrink-0"
                                                    style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.1);"
                                                    @mouseenter="$event.currentTarget.style.background='rgba(255,255,255,0.12)'"
                                                    @mouseleave="$event.currentTarget.style.background='rgba(255,255,255,0.06)'">
                                                <i v-if="cepLoading" class="pi pi-spin pi-spinner" style="font-size:12px;" />
                                                <span v-else>OK</span>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-black uppercase tracking-widest"
                                               style="color:rgba(255,255,255,0.4);">Rua</label>
                                        <input v-model="newAddr.street" type="text"
                                               placeholder="Nome da rua / avenida"
                                               class="drawer-input" />
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-black uppercase tracking-widest"
                                                   style="color:rgba(255,255,255,0.4);">Número</label>
                                            <input v-model="newAddr.number" type="text"
                                                   placeholder="123"
                                                   class="drawer-input" />
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-black uppercase tracking-widest"
                                                   style="color:rgba(255,255,255,0.4);">Complemento</label>
                                            <input v-model="newAddr.complement" type="text"
                                                   placeholder="Apto, bloco..."
                                                   class="drawer-input" />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-black uppercase tracking-widest"
                                                   style="color:rgba(255,255,255,0.4);">Cidade</label>
                                            <input v-model="newAddr.city" type="text"
                                                   placeholder="Cidade"
                                                   class="drawer-input" />
                                        </div>
                                        <div class="flex flex-col gap-1.5">
                                            <label class="text-xs font-black uppercase tracking-widest"
                                                   style="color:rgba(255,255,255,0.4);">UF</label>
                                            <input v-model="newAddr.state" type="text"
                                                   placeholder="SP" maxlength="2"
                                                   class="drawer-input"
                                                   @input="newAddr.state = newAddr.state.toUpperCase()" />
                                        </div>
                                    </div>

                                    <!-- Prompt: salvar endereço? (logado) -->
                                    <Transition name="step-fwd">
                                        <div v-if="saveAddrPrompt && token"
                                             class="rounded-xl px-4 py-4 flex flex-col gap-3"
                                             style="background:rgba(230,235,0,0.05);border:1px solid rgba(230,235,0,0.2);">
                                            <p class="text-sm font-bold text-white">
                                                <i class="pi pi-bookmark mr-2" style="color:#e6eb00;font-size:13px;" />
                                                Salvar este endereço para próximas compras?
                                            </p>
                                            <div class="flex gap-2">
                                                <button @click="handleSaveAddr(true)"
                                                        :disabled="savingAddr"
                                                        class="flex-1 py-2.5 rounded-lg font-black uppercase text-xs tracking-widest transition-all disabled:opacity-60"
                                                        style="background:#FF003C;color:#fff;">
                                                    {{ savingAddr ? '...' : 'Sim, salvar' }}
                                                </button>
                                                <button @click="handleSaveAddr(false)"
                                                        class="flex-1 py-2.5 rounded-lg font-black uppercase text-xs tracking-widest transition-all"
                                                        style="background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.1);">
                                                    Não, continuar
                                                </button>
                                            </div>
                                        </div>
                                    </Transition>
                                </template>
                            </div>

                            <!-- STEP 3: Confirmar -->
                            <div v-else-if="step === 3" key="step3" class="px-6 py-6 flex flex-col gap-4">
                                <p class="text-xs font-bold uppercase tracking-widest"
                                   style="color:rgba(255,255,255,0.3);">
                                    Confira seu pedido
                                </p>

                                <!-- Resumo dos itens -->
                                <div class="rounded-xl overflow-hidden"
                                     style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);">
                                    <div v-for="(item, i) in items" :key="item.key"
                                         class="flex items-center justify-between gap-3 px-4 py-3"
                                         :style="i < items.length - 1 ? 'border-bottom:1px solid rgba(255,255,255,0.05)' : ''">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-lg flex-shrink-0 overflow-hidden flex items-center justify-center"
                                                 style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);">
                                                <img v-if="item.thumbnail"
                                                     :src="item.thumbnail" :alt="item.name"
                                                     class="w-full h-full object-contain p-1" />
                                                <i v-else class="pi pi-box" style="font-size:14px;color:rgba(255,255,255,0.2);" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-bold text-white truncate">{{ item.name }}</p>
                                                <p class="text-xs" style="color:rgba(255,255,255,0.35);">
                                                    {{ item.qty }}× R$ {{ fmt(item.price) }}
                                                </p>
                                            </div>
                                        </div>
                                        <span class="font-black text-sm flex-shrink-0" style="color:#ff525c;">
                                            R$ {{ fmt(item.price * item.qty) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Totais -->
                                <div class="rounded-xl px-4 py-4 flex flex-col gap-2.5"
                                     style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);">
                                    <div class="flex justify-between text-sm">
                                        <span style="color:rgba(255,255,255,0.45);">Subtotal</span>
                                        <span class="font-bold text-white">R$ {{ fmt(total) }}</span>
                                    </div>
                                    <div class="flex justify-between text-sm">
                                        <span style="color:rgba(255,255,255,0.45);">Frete</span>
                                        <span class="font-bold"
                                              :style="freeShipping ? 'color:rgba(74,222,128,0.9)' : 'color:#fff'">
                                            {{ freeShipping ? 'Grátis' : `R$ ${fmt(shipping)}` }}
                                        </span>
                                    </div>
                                    <div v-if="coupon" class="flex justify-between text-sm">
                                        <span style="color:rgba(74,222,128,0.7);">
                                            <i class="pi pi-tag mr-1" style="font-size:11px;" />
                                            {{ coupon.code }}
                                        </span>
                                        <span class="font-bold" style="color:rgba(74,222,128,0.9);">
                                            − R$ {{ fmt(discount) }}
                                        </span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2"
                                         style="border-top:1px solid rgba(255,255,255,0.06);">
                                        <span class="font-black uppercase text-sm text-white tracking-wider">Total</span>
                                        <span class="font-black text-xl" style="color:#e6eb00;">
                                            R$ {{ fmt(orderTotal) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Dados confirmados -->
                                <div class="rounded-xl px-4 py-3 flex flex-col gap-1.5 text-sm"
                                     style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);">
                                    <div class="flex gap-3">
                                        <span class="w-16 flex-shrink-0 font-bold text-xs uppercase tracking-widest"
                                              style="color:rgba(255,255,255,0.25);">Nome</span>
                                        <span class="font-bold text-white text-xs truncate">{{ form.customer_name }}</span>
                                    </div>
                                    <div class="flex gap-3">
                                        <span class="w-16 flex-shrink-0 font-bold text-xs uppercase tracking-widest"
                                              style="color:rgba(255,255,255,0.25);">E-mail</span>
                                        <span class="font-bold text-white text-xs truncate">{{ form.customer_email }}</span>
                                    </div>
                                    <div v-if="form.customer_address.street" class="flex gap-3">
                                        <span class="w-16 flex-shrink-0 font-bold text-xs uppercase tracking-widest"
                                              style="color:rgba(255,255,255,0.25);">Endereço</span>
                                        <span class="font-bold text-white text-xs">
                                            {{ form.customer_address.street }}
                                            <template v-if="form.customer_address.city">
                                                · {{ form.customer_address.city }}/{{ form.customer_address.state }}
                                            </template>
                                        </span>
                                    </div>
                                </div>
                            </div>

                        </Transition>
                    </template>
                </div>

                <!-- ── FOOTER ── -->
                <div v-if="!orderDone && customer"
                     class="relative z-10 px-6 py-5 flex flex-col gap-3"
                     style="border-top:1px solid rgba(255,255,255,0.06);">

                    <!-- Erros gerais -->
                    <div v-if="Object.keys(errors).length && step === 3"
                         class="px-4 py-3 rounded-xl text-xs font-bold"
                         style="background:rgba(255,0,60,0.1);border:1px solid rgba(255,0,60,0.3);color:#ff6b6b;">
                        Verifique os dados e tente novamente.
                    </div>

                    <div class="flex gap-3">
                        <!-- Back -->
                        <button v-if="step > 1"
                                @click="goBack"
                                class="flex items-center gap-2 px-5 py-4 rounded-xl font-black uppercase text-sm tracking-widest transition-all"
                                style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.5);border:1px solid rgba(255,255,255,0.1);">
                            <i class="pi pi-arrow-left" style="font-size:11px;" />
                        </button>

                        <!-- Next / Confirm -->
                        <button v-if="step < 3"
                                @click="step === 1 ? goNextStep1() : goNextStep2()"
                                :disabled="(step === 1 && !canNext1) || (step === 2 && !canNext2) || saveAddrPrompt"
                                class="flex-1 py-4 rounded-xl font-black uppercase text-sm tracking-widest transition-all text-white disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                                :style="(step === 1 ? canNext1 : canNext2) && !saveAddrPrompt
                                    ? 'background:#FF003C;box-shadow:0 4px 20px rgba(255,0,60,0.35)'
                                    : 'background:rgba(255,255,255,0.08)'">
                            {{ step === 1 ? 'Próximo: Endereço' : 'Próximo: Confirmar' }}
                            <i class="pi pi-arrow-right" style="font-size:11px;" />
                        </button>

                        <button v-else
                                @click="submit"
                                :disabled="loading"
                                class="flex-1 py-4 rounded-xl font-black uppercase text-sm tracking-widest transition-all text-white flex items-center justify-center gap-2"
                                style="background:#FF003C;box-shadow:0 4px 20px rgba(255,0,60,0.35);">
                            <i v-if="loading" class="pi pi-spin pi-spinner" style="font-size:14px;" />
                            {{ loading ? 'Processando...' : 'Confirmar Pedido' }}
                            <i v-if="!loading" class="pi pi-lock" style="font-size:12px;" />
                        </button>
                    </div>

                    <p class="text-center text-xs" style="color:rgba(255,255,255,0.2);">
                        Pagamento 100% seguro · Dados criptografados
                    </p>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>


<style scoped>
/* Backdrop */
.backdrop-enter-active, .backdrop-leave-active { transition: opacity 0.3s ease; }
.backdrop-enter-from, .backdrop-leave-to       { opacity: 0; }

/* Drawer slide */
.drawer-enter-active { transition: transform 0.42s cubic-bezier(0.25, 0.46, 0.45, 0.94); }
.drawer-leave-active { transition: transform 0.32s cubic-bezier(0.55, 0, 1, 0.45); }
.drawer-enter-from, .drawer-leave-to { transform: translateX(100%); }

/* Step forward */
.step-fwd-enter-active, .step-fwd-leave-active { transition: all 0.28s ease; }
.step-fwd-enter-from  { transform: translateX(32px); opacity: 0; }
.step-fwd-leave-to    { transform: translateX(-32px); opacity: 0; }

/* Step back */
.step-bck-enter-active, .step-bck-leave-active { transition: all 0.28s ease; }
.step-bck-enter-from  { transform: translateX(-32px); opacity: 0; }
.step-bck-leave-to    { transform: translateX(32px); opacity: 0; }

/* Product showcase float */
@keyframes showcase-float {
    0%, 100% { transform: translateY(0px) rotate(-2.5deg) scale(1); }
    50%       { transform: translateY(-22px) rotate(2.5deg) scale(1.03); }
}
.showcase-float { animation: showcase-float 5s ease-in-out infinite; }

/* Showcase slide transition */
.showcase-enter-active { transition: all 0.55s cubic-bezier(0.34, 1.56, 0.64, 1); }
.showcase-leave-active { transition: all 0.3s ease-in; }
.showcase-enter-from   { opacity: 0; transform: translateY(40px) scale(0.85); }
.showcase-leave-to     { opacity: 0; transform: translateY(-30px) scale(0.9); }

/* Cashew rain */
.cashew-particle {
    top: -70px;
    aspect-ratio: 56 / 78;
    animation: cashew-fall var(--dur, 2s) var(--delay, 0s) ease-in forwards;
    will-change: transform, opacity;
}
@keyframes cashew-fall {
    0%   { transform: translateY(0) translateX(0) rotate(var(--rot0, 0deg)); opacity: 0.9; }
    50%  { transform: translateY(50vh) translateX(calc(var(--drift, 0px) * 0.6)) rotate(calc((var(--rot0, 0deg) + var(--rot1, 360deg)) / 2)); }
    85%  { opacity: 0.8; }
    100% { transform: translateY(108vh) translateX(var(--drift, 0px)) rotate(var(--rot1, 360deg)); opacity: 0; }
}

/* Showcase wrapper — hidden on mobile, flex on desktop */
.showcase-wrapper {
    display: none;
}
@media (min-width: 1024px) {
    .showcase-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
}

/* Input style */
.drawer-input {
    width: 100%;
    padding: 12px 16px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,255,255,0.1);
    color: #fff;
    outline: none;
    transition: border-color 0.2s;
}
.drawer-input:focus {
    border-color: rgba(255,0,60,0.5);
}
.drawer-input::placeholder {
    color: rgba(255,255,255,0.2);
    font-weight: 500;
}
</style>
