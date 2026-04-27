<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';
import FavoriteButton from '../components/FavoriteButton.vue';

const router = useRouter();
const kits   = ref([]);

// ── Carousel ─────────────────────────────────────────────────────────────────
const CARD_GAP = 24;

// Card width adapts to screen size
function getCardW() {
    if (typeof window === 'undefined') return 340;
    if (window.innerWidth < 400) return window.innerWidth - 60;
    if (window.innerWidth < 640) return 300;
    return 340;
}
const CARD_W = ref(getCardW());
const STEP   = computed(() => CARD_W.value + CARD_GAP);

const currentIndex = ref(0);
const tiltX        = ref(0);
const tiltY        = ref(0);
const dragDelta    = ref(0);
const isDragging   = ref(false);
let autoTimer  = null;
let dragStartX = 0;

const allKits = computed(() => [
    ...kits.value,
    { __custom: true, name: 'Personalizado', id: null },
]);

const progress = computed(() => {
    const len = allKits.value.length;
    return len <= 1 ? 100 : (currentIndex.value / (len - 1)) * 100;
});

const trackStyle = computed(() => ({
    display:    'flex',
    gap:        `${CARD_GAP}px`,
    transform:  `translateX(calc(50% - ${currentIndex.value * STEP.value + CARD_W.value / 2}px + ${dragDelta.value}px))`,
    transition: isDragging.value ? 'none' : 'transform 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94)',
    willChange: 'transform',
    cursor:     isDragging.value ? 'grabbing' : 'grab',
    userSelect: 'none',
}));

function cardWrapStyle(i) {
    const dist = Math.abs(i - currentIndex.value);
    return {
        width:          `${CARD_W.value}px`,
        flexShrink:     0,
        position:       'relative',
        transform:      `scale(${dist === 0 ? 1 : dist === 1 ? 0.87 : 0.75})`,
        opacity:        dist === 0 ? 1 : dist === 1 ? 0.58 : 0.22,
        transition:     'transform 0.55s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.55s ease',
        transformOrigin:'center bottom',
        cursor:         dist === 0 ? 'default' : 'pointer',
        pointerEvents:  dist > 2 ? 'none' : 'auto',
    };
}

function cardInnerStyle(i) {
    const active = i === currentIndex.value;
    return {
        boxShadow: active
            ? '0 28px 80px rgba(200,40,48,0.3), 0 8px 32px rgba(0,0,0,0.55)'
            : '0 8px 24px rgba(0,0,0,0.25)',
        transform: active
            ? `perspective(1200px) rotateX(${tiltX.value}deg) rotateY(${tiltY.value}deg)`
            : 'none',
        transition: isDragging.value ? 'none' : 'transform 0.1s ease, box-shadow 0.4s ease',
    };
}

function onCardMouseMove(e) {
    const r = e.currentTarget.getBoundingClientRect();
    tiltY.value =  ((e.clientX - r.left)  / r.width  - 0.5) * 16;
    tiltX.value = -((e.clientY - r.top)   / r.height - 0.5) * 10;
}
function resetTilt() { tiltX.value = 0; tiltY.value = 0; }

function goTo(i) {
    currentIndex.value = Math.max(0, Math.min(i, allKits.value.length - 1));
    resetTilt();
}
function prev() { goTo(currentIndex.value - 1); }
function next() { goTo(currentIndex.value < allKits.value.length - 1 ? currentIndex.value + 1 : 0); }

function onDragStart(e) {
    isDragging.value = true;
    dragStartX       = e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX;
    dragDelta.value  = 0;

    function move(e) {
        dragDelta.value = (e.type.startsWith('touch') ? e.touches[0].clientX : e.clientX) - dragStartX;
    }
    function end() {
        isDragging.value = false;
        if      (dragDelta.value < -60) next();
        else if (dragDelta.value >  60) prev();
        dragDelta.value = 0;
        document.removeEventListener('mousemove', move);
        document.removeEventListener('mouseup',   end);
        document.removeEventListener('touchmove', move);
        document.removeEventListener('touchend',  end);
    }
    document.addEventListener('mousemove', move);
    document.addEventListener('mouseup',   end);
    document.addEventListener('touchmove', move, { passive: true });
    document.addEventListener('touchend',  end);
}

function startAuto() {
    clearInterval(autoTimer);
    autoTimer = setInterval(() => next(), 4500);
}
function pauseAuto() { clearInterval(autoTimer); }
function resumeAuto() { startAuto(); }

onMounted(async () => {
    try {
        const res = await axios.get('/api/kits');
        kits.value = res.data;
    } catch {}
    startAuto();
    window.addEventListener('resize', () => { CARD_W.value = getCardW(); });
});
onUnmounted(() => {
    clearInterval(autoTimer);
    window.removeEventListener('resize', () => { CARD_W.value = getCardW(); });
});

const fmt = v => Number(v).toFixed(2).replace('.', ',');

function kitSlotsSummary(kit) {
    return (kit.slots ?? []).map(s => `${s.quantity}× ${s.size}`).join(' + ');
}
function openKitSelector(kit) {
    router.push({ name: 'kit.selector', params: { slug: kit.slug ?? kit.id } });
}
function openCustomKit() {
    router.push({ name: 'custom.kit' });
}
</script>

<template>
    <div>
        <!-- Hero Section -->
        <section class="bg-mn-red text-white text-center relative overflow-hidden">
            <div class="flex justify-center pt-12">
                <div class="relative inline-block">
                    <img
                        src="https://madnutz.com.br/wp-content/uploads/2026/01/Ativo-1@1400x.png"
                        alt="MadNutz mascot"
                        class="max-h-[55vh] w-auto object-contain block"
                    />
                </div>
            </div>

            <h1
                class="text-6xl md:text-8xl font-black uppercase leading-none py-8 px-4"
                style="font-family: 'Passion One', sans-serif;"
            >
                SÓ MAIS UMA, TÁ?
            </h1>

            <div class="overflow-hidden bg-mn-yellow" style="padding-top:20px; padding-bottom:20px;">
                <div class="ticker-track text-mn-black uppercase" style="font-family:'Passion One',sans-serif; font-size:45px; font-weight:600;">
                    <span v-for="i in 8" :key="i">
                        MELHOR COMPANHIA &bull; CROCÂNCIA &bull; + ENERGIA &bull; ZERO TÉDIO &bull;
                    </span>
                </div>
            </div>
        </section>

        <!-- Quem somos -->
        <section id="quem-somos" class="bg-mn-red text-white py-20 px-4">
            <div class="max-w-6xl mx-auto space-y-20">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div class="relative">
                        <img
                            src="https://madnutz.com.br/wp-content/uploads/2026/01/Star-3.png"
                            alt=""
                            class="absolute -top-8 right-0 w-32 opacity-60 pointer-events-none"
                        />
                        <h2
                            class="text-5xl md:text-6xl font-semibold uppercase leading-tight mb-6"
                            style="font-family: 'Passion One', sans-serif;"
                        >
                            NÃO É SÓ SNACK.<br>
                            É MADNUTZ: OUSADO,<br>
                            INTENSO, SEM MEIO-TERMO
                        </h2>
                        <p class="text-white/80 text-lg leading-relaxed">
                            MadNutz é pra quem não aceita snack sem graça. Se o básico já não te satisfaz,
                            você acabou de encontrar outra coisa. Mais intenso. Mais viciante. Muito mais Mad.
                        </p>
                    </div>
                    <div class="flex justify-center">
                        <img
                            src="https://madnutz.com.br/wp-content/uploads/2026/02/super-lemon.webp"
                            alt="MadNutz Super Lemon"
                            class="w-full max-w-xs object-contain drop-shadow-2xl"
                            style="transform: rotate(-12deg);"
                        />
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div class="flex justify-center order-2 md:order-1">
                        <img
                            src="https://madnutz.com.br/wp-content/uploads/2026/01/Group-7-e1769696498413.png"
                            alt="MadNutz produto na bandeja"
                            class="w-full max-w-md object-contain drop-shadow-2xl"
                        />
                    </div>
                    <div class="order-1 md:order-2">
                        <h2
                            class="text-5xl md:text-6xl font-semibold uppercase leading-tight mb-6"
                            style="font-family: 'Passion One', sans-serif;"
                        >
                            FEITO PRA QUEM QUEBRA PADRÕES
                        </h2>
                        <p class="text-white/80 text-lg leading-relaxed mb-4">
                            Esqueça o que você achava que era um snack normal. MadNutz não
                            nasceu pra ser só mais uma opção. Chega do sem graça, do previsível,
                            do que não provoca nada.
                        </p>
                        <p class="text-white/80 text-lg leading-relaxed">
                            Isso aqui é sabor com atitude. Daqueles que chegam sem pedir licença.
                            Experimente e descubra por que, depois de MadNutz, o básico já não basta.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Elevando momentos -->
        <section class="relative overflow-hidden bg-mn-black" style="min-height:60vh;">
            <img
                src="https://madnutz.com.br/wp-content/uploads/2026/02/super-lemon.webp"
                alt=""
                class="absolute left-0 bottom-0 h-full max-h-[90%] object-contain pointer-events-none hidden md:block"
                style="transform: rotate(-8deg); transform-origin: bottom left; opacity:.95;"
            />
            <img
                src="https://madnutz.com.br/wp-content/uploads/2026/02/super-lemon.webp"
                alt=""
                class="absolute right-0 bottom-0 h-full max-h-[90%] object-contain pointer-events-none hidden md:block"
                style="transform: rotate(12deg); transform-origin: bottom right; opacity:.95;"
            />
            <div class="relative z-10 flex flex-col items-center justify-center text-center px-6 py-24">
                <h2
                    class="text-5xl md:text-7xl font-black uppercase text-mn-yellow leading-tight mb-4"
                    style="font-family:'Passion One',sans-serif;"
                >
                    ELEVANDO TODOS<br>OS SEUS MOMENTOS
                </h2>
                <p class="text-white/70 text-lg max-w-xl mb-10">
                    Esqueça o snack comum. MadNutz transforma qualquer pausa em experiência.
                </p>
                <a href="#produtos1" class="btn-red inline-block text-base px-10 py-4">
                    GARANTA O SEU!
                </a>
            </div>
            <div class="absolute bottom-0 left-0 right-0 leading-none">
                <svg viewBox="0 0 1440 70" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" class="w-full h-16 md:h-20 block">
                    <path d="M0,40 C240,80 480,0 720,40 C960,80 1200,0 1440,40 L1440,70 L0,70 Z" fill="#C82830"/>
                </svg>
            </div>
        </section>

        <!-- Explore sabores banner -->
        <section class="bg-mn-red py-10 px-4 text-center">
            <p
                class="text-white text-3xl md:text-4xl font-black uppercase"
                style="font-family: 'Passion One', sans-serif;"
            >
                explore todos os sabores &mdash; 99% nuts, 1% malícia!
            </p>
        </section>

        <!-- ──────────── KITS: dark carousel ──────────── -->
        <section id="produtos1" class="relative overflow-hidden" style="background:#0f0f0f;">

            <!-- Progress line -->
            <div style="height:3px;background:rgba(255,255,255,0.06);">
                <div :style="`width:${progress}%;background:linear-gradient(to right,#FF003C,#ff8c00);height:100%;transition:width 0.7s ease`" />
            </div>

            <!-- Atmospheric glow -->
            <div class="pointer-events-none absolute inset-0"
                 style="background:radial-gradient(ellipse 90% 70% at 50% 105%, rgba(200,40,48,0.25) 0%, transparent 60%);" />

            <div class="relative max-w-7xl mx-auto px-4 pt-14">

                <!-- Title -->
                <div class="text-center mb-0">
                    <p style="color:#ff525c;font-size:11px;font-weight:900;letter-spacing:0.35em;text-transform:uppercase;margin-bottom:10px;">
                        Escolha o seu
                    </p>
                    <h2 style="font-family:'Passion One',sans-serif;font-size:clamp(3.5rem,9vw,6rem);color:#fff;font-weight:900;text-transform:uppercase;line-height:0.95;">
                        NOSSOS KITS
                    </h2>
                </div>

                <!-- Carousel -->
                <div class="relative"
                     @mouseenter="pauseAuto"
                     @mouseleave="resumeAuto">

                    <!-- Viewport -->
                    <div class="overflow-hidden" style="padding:52px 0 36px;">

                        <!-- Track -->
                        <div :style="trackStyle"
                             @mousedown="onDragStart"
                             @touchstart.passive="onDragStart">

                            <div v-for="(kit, i) in allKits" :key="i"
                                 :style="cardWrapStyle(i)"
                                 @click="i !== currentIndex ? goTo(i) : null">

                                <!-- Red glow blob behind active card -->
                                <div v-if="i === currentIndex"
                                     class="absolute pointer-events-none"
                                     style="inset:-10px 30px -16px;background:rgba(200,40,48,0.18);filter:blur(48px);border-radius:50%;z-index:0;" />

                                <!-- The card itself -->
                                <div class="relative rounded-2xl overflow-hidden w-full"
                                     style="background:#fff;z-index:1;"
                                     :style="cardInnerStyle(i)"
                                     @mousemove="i === currentIndex ? onCardMouseMove($event) : null"
                                     @mouseleave="i === currentIndex ? resetTilt() : null">

                                    <!-- Badge -->
                                    <div v-if="kit.badge"
                                         class="absolute top-3 left-3 z-10 text-xs font-bold uppercase px-3 py-1"
                                         style="background:#FFDF00;border-radius:20px;color:#000;letter-spacing:0.05em;">
                                        🔥 {{ kit.badge }}
                                    </div>

                                    <!-- Favorite -->
                                    <FavoriteButton v-if="!kit.__custom"
                                                    type="kit" :item-id="kit.id"
                                                    size="sm"
                                                    class="absolute top-3 right-3 z-10"
                                                    @click.stop />

                                    <!-- Image / custom area -->
                                    <div class="flex items-center justify-center"
                                         :style="kit.__custom
                                             ? 'background:#EF841A;min-height:220px;'
                                             : 'background:#fff;min-height:220px;'">
                                        <template v-if="kit.__custom">
                                            <div class="flex flex-col items-center gap-3 py-10 text-white">
                                                <div class="w-16 h-16 rounded-full border-2 border-white/50 flex items-center justify-center">
                                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                                    </svg>
                                                </div>
                                                <span style="font-family:'Passion One',sans-serif;font-size:1.2rem;letter-spacing:0.12em;text-transform:uppercase;">
                                                    Crie o seu
                                                </span>
                                            </div>
                                        </template>
                                        <img v-else
                                             :src="kit.image" :alt="kit.name"
                                             class="object-contain"
                                             style="height:200px;"
                                             draggable="false" />
                                    </div>

                                    <!-- Info -->
                                    <div class="px-5 pb-5 pt-3 text-center">
                                        <h3 class="uppercase leading-tight mb-1"
                                            style="font-family:'Passion One',sans-serif;font-size:2rem;color:#111;font-weight:600;">
                                            {{ kit.__custom ? 'PERSONALIZE SEU KIT' : kit.name }}
                                        </h3>

                                        <p v-if="!kit.__custom && kit.slots?.length"
                                           class="text-sm mb-1" style="color:#999;">
                                            {{ kitSlotsSummary(kit) }}
                                        </p>

                                        <p v-if="kit.description"
                                           class="text-sm mb-2 line-clamp-2" style="color:#aaa;">
                                            {{ kit.description }}
                                        </p>

                                        <div class="my-3">
                                            <template v-if="!kit.__custom">
                                                <span v-if="kit.sale_price"
                                                      class="line-through text-sm mr-1" style="color:#ccc;">
                                                    R${{ fmt(kit.price) }}
                                                </span>
                                                <span style="font-family:'Passion One',sans-serif;font-size:2rem;color:#C82830;font-weight:600;">
                                                    R${{ fmt(kit.effective_price) }}
                                                </span>
                                            </template>
                                            <span v-else style="font-family:'Passion One',sans-serif;font-size:1.5rem;color:#EF841A;font-weight:600;">
                                                MONTE DO SEU JEITO
                                            </span>
                                        </div>

                                        <button
                                            class="w-full font-black uppercase py-3.5 rounded-xl transition-all duration-150 hover:opacity-80 active:scale-95"
                                            style="background:#000;color:#fff;font-family:'Passion One',sans-serif;font-size:1.05rem;letter-spacing:0.04em;"
                                            @click.stop="kit.__custom ? openCustomKit() : openKitSelector(kit)">
                                            {{ kit.__custom ? '+ MONTAR MEU KIT' : 'ESCOLHER SABORES' }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prev arrow -->
                    <button @click="prev"
                            class="absolute left-0 top-1/2 -translate-y-1/2 z-20 flex items-center px-3 py-4 transition-all duration-200"
                            style="color:rgba(255,255,255,0.3);"
                            @mouseenter="$event.currentTarget.style.color='rgba(255,255,255,0.9)'"
                            @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.3)'">
                        <i class="pi pi-angle-left" style="font-size:34px;" />
                    </button>

                    <!-- Next arrow -->
                    <button @click="next"
                            class="absolute right-0 top-1/2 -translate-y-1/2 z-20 flex items-center px-3 py-4 transition-all duration-200"
                            style="color:rgba(255,255,255,0.3);"
                            @mouseenter="$event.currentTarget.style.color='rgba(255,255,255,0.9)'"
                            @mouseleave="$event.currentTarget.style.color='rgba(255,255,255,0.3)'">
                        <i class="pi pi-angle-right" style="font-size:34px;" />
                    </button>
                </div>

                <!-- Counter + pills -->
                <div class="flex flex-col items-center gap-4 pb-14">

                    <!-- Kit name pills -->
                    <div class="flex flex-wrap justify-center gap-2">
                        <button v-for="(kit, i) in allKits" :key="i"
                                @click="goTo(i)"
                                class="text-xs font-black uppercase tracking-widest px-4 py-2 rounded-full transition-all duration-300"
                                :style="i === currentIndex
                                    ? 'background:#C82830;color:#fff;box-shadow:0 4px 18px rgba(200,40,48,0.5);'
                                    : 'background:rgba(255,255,255,0.07);color:rgba(255,255,255,0.4);'">
                            {{ kit.__custom ? 'Personalizado' : kit.name }}
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Contact section -->
        <section id="contato" class="bg-mn-orange text-white">
            <div class="grid grid-cols-1 md:grid-cols-2 min-h-[520px]">
                <div class="hidden md:flex items-stretch" style="background:#EF841A;">
                    <img
                        src="https://madnutz.com.br/wp-content/uploads/2026/04/Untitled-design-27.png"
                        alt="MadNutz produtos"
                        class="w-full h-full object-cover"
                    />
                </div>

                <div class="flex flex-col justify-center px-8 py-12 max-w-2xl mx-auto w-full">
                    <h2
                        class="uppercase leading-tight mb-3"
                        style="font-family:'Passion One',sans-serif; font-size:54px; font-weight:600;"
                    >
                        CURTIU? ENTÃO NÃO FICA NO BÁSICO.
                    </h2>
                    <p class="text-white/90 mb-6">
                        Fala com a gente. Dúvidas, pedidos ou só vontade de provar algo diferente?
                        A MadNutz responde.
                    </p>

                    <form class="bg-white rounded-2xl p-6 flex flex-col gap-4 text-left" @submit.prevent>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="flex flex-col gap-1">
                                <label class="text-mn-black text-sm font-semibold">Nome completo</label>
                                <input type="text" placeholder="Digite seu nome" class="border border-gray-200 rounded-lg px-4 py-2.5 text-mn-black text-sm outline-none focus:ring-2 focus:ring-mn-orange" />
                            </div>
                            <div class="flex flex-col gap-1">
                                <label class="text-mn-black text-sm font-semibold">Whatsapp</label>
                                <input type="text" placeholder="Digite seu whatsapp" class="border border-gray-200 rounded-lg px-4 py-2.5 text-mn-black text-sm outline-none focus:ring-2 focus:ring-mn-orange" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-mn-black text-sm font-semibold">Seu melhor e-mail</label>
                            <input type="email" placeholder="Digite seu email" class="border border-gray-200 rounded-lg px-4 py-2.5 text-mn-black text-sm outline-none focus:ring-2 focus:ring-mn-orange" />
                        </div>
                        <div class="flex flex-col gap-1">
                            <label class="text-mn-black text-sm font-semibold">Mensagem</label>
                            <textarea placeholder="Digite seu texto aqui" rows="4" class="border border-gray-200 rounded-lg px-4 py-2.5 text-mn-black text-sm outline-none focus:ring-2 focus:ring-mn-orange resize-none"></textarea>
                        </div>
                        <button type="submit" class="bg-mn-black text-white font-black uppercase py-3 rounded-lg tracking-widest hover:bg-mn-gray transition-colors">
                            ENVIAR
                        </button>
                    </form>
                    <p class="text-white/80 text-sm text-center mt-3">Envie sua mensagem que rapidinho vamos te responder!</p>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
@keyframes marquee {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.ticker-track {
    display: flex;
    white-space: nowrap;
    gap: 2.5rem;
    animation: marquee 20s linear infinite;
}
</style>
