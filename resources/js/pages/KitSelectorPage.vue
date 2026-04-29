<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useCart } from '../stores/cart.js';

const route  = useRoute();
const router = useRouter();

const { items, addSlottedKit, updateKitSelections } = useCart();

const slug        = computed(() => route.params.slug);
const editMode    = computed(() => route.query.edit === 'true');
const cartItemKey = computed(() => route.query.key ?? '');

const kit               = ref(null);
const loadingKit        = ref(true);
const loadingProducts   = ref(false);
const availableProducts = ref([]);
const selections        = ref([]);
const imageIdx          = ref(0);
const kitQty            = ref(1);
const currentSlot       = ref(0);

const cartItem          = computed(() =>
    editMode.value ? items.value.find(i => i.key === cartItemKey.value) : null
);
const initialSelections = computed(() => cartItem.value?.kit_selections ?? []);

const allImages = computed(() => {
    if (!kit.value) return [];
    const imgs = (kit.value.images ?? []).map(i => i.url).filter(Boolean);
    if (imgs.length === 0 && kit.value.image) return [kit.value.image];
    return imgs;
});
const currentImage = computed(() => allImages.value[imageIdx.value] ?? kit.value?.image ?? '');

const currentSlotObj = computed(() => kit.value?.slots?.[currentSlot.value] ?? null);

const productsForCurrentSlot = computed(() => {
    if (!currentSlotObj.value) return [];
    return availableProducts.value.filter(p => p.size === currentSlotObj.value.size);
});

function slotCount(si) {
    return Object.values(selections.value[si] ?? {}).reduce((a, b) => a + b, 0);
}
function canAdd(si, max) { return slotCount(si) < max; }

function increment(si, productId, max) {
    if (!canAdd(si, max)) return;
    if (!selections.value[si]) selections.value[si] = {};
    selections.value[si][productId] = (selections.value[si][productId] ?? 0) + 1;
    spawnCashews();
}
function decrement(si, productId) {
    if (!selections.value[si]) return;
    const cur = selections.value[si][productId] ?? 0;
    if (cur <= 0) return;
    selections.value[si][productId] = cur - 1;
}

const currentSlotCount    = computed(() => slotCount(currentSlot.value));
const currentSlotComplete = computed(() =>
    !!currentSlotObj.value && currentSlotCount.value === currentSlotObj.value.quantity
);
const isLastSlot = computed(() =>
    currentSlot.value === (kit.value?.slots?.length ?? 1) - 1
);
const allSlotsComplete = computed(() => {
    if (!kit.value?.slots?.length) return false;
    return kit.value.slots.every((slot, si) => slotCount(si) === slot.quantity);
});

const ctaLabel = computed(() => {
    if (editMode.value && allSlotsComplete.value) return 'SALVAR ALTERAÇÕES';
    if (!currentSlotComplete.value) {
        const rem = (currentSlotObj.value?.quantity ?? 0) - currentSlotCount.value;
        return `SELECIONE MAIS ${rem} SABOR${rem !== 1 ? 'ES' : ''}`;
    }
    if (!isLastSlot.value) return 'PRÓXIMA ETAPA';
    return 'ADICIONAR AO CARRINHO';
});

function prevImage() { imageIdx.value = (imageIdx.value - 1 + allImages.value.length) % allImages.value.length; }
function nextImage() { imageIdx.value = (imageIdx.value + 1) % allImages.value.length; }

const fmt = v => Number(v).toFixed(2).replace('.', ',');

// Cashew rain
const cashewParticles = ref([]);
let _pid = 0;
function spawnCashews() {
    for (let i = 0; i < 22; i++) {
        const id    = _pid++;
        const delay = Math.random() * 0.8;
        const dur   = 1.2 + Math.random() * 1.1;
        cashewParticles.value.push({
            id,
            style: [
                `left:${4 + Math.random() * 92}%`,
                `width:${20 + Math.random() * 24}px`,
                `--dur:${dur}s`,
                `--delay:${delay}s`,
                `--rot0:${Math.random() * 360}deg`,
                `--rot1:${Math.random() * 360 + 200}deg`,
                `--drift:${(Math.random() - 0.5) * 80}px`,
            ].join(';'),
        });
        setTimeout(() => {
            cashewParticles.value = cashewParticles.value.filter(x => x.id !== id);
        }, (delay + dur + 0.4) * 1000);
    }
}

// Animated balls
const animatingBalls = ref({});
watch(selections, (newSel, oldSel) => {
    kit.value?.slots?.forEach((slot, si) => {
        const countOf = sel => Object.values(sel[si] ?? {}).reduce((a, b) => a + b, 0);
        if (countOf(newSel) > countOf(oldSel)) {
            const key = `${si}-${countOf(newSel) - 1}`;
            animatingBalls.value[key] = true;
            setTimeout(() => { delete animatingBalls.value[key]; }, 600);
        }
    });
}, { deep: true });

function expandedSelections(si) {
    const expanded = [];
    for (const [productId, qty] of Object.entries(selections.value[si] ?? {})) {
        const product = availableProducts.value.find(p => p.id === Number(productId));
        for (let i = 0; i < qty; i++) expanded.push(product ?? null);
    }
    return expanded;
}

function ballFillPercent(ballIdx, total) {
    if (total <= 1) return 100;
    return Math.round(38 + (ballIdx / (total - 1)) * 62);
}

function flavorColor(name) {
    const n = (name ?? '').toLowerCase();
    if (/cocobomb|coco.?bomb|chocolate|cacau|choco/.test(n)) return { fill: '#7B4028', glow: 'rgba(123,64,40,0.55)', border: '#9B5030' };
    if (/pistach/.test(n))                                    return { fill: '#4E7A2C', glow: 'rgba(78,122,44,0.55)', border: '#6A9A3C' };
    if (/lim[aã]o|lemon/.test(n))                            return { fill: '#A89000', glow: 'rgba(168,144,0,0.55)',  border: '#D4B800' };
    if (/morango|strawberry/.test(n))                        return { fill: '#B03060', glow: 'rgba(176,48,96,0.55)', border: '#D04070' };
    if (/\bmel\b|honey/.test(n))                             return { fill: '#B07820', glow: 'rgba(176,120,32,0.55)', border: '#D09830' };
    return { fill: '#C82830', glow: 'rgba(200,40,48,0.55)', border: '#E03840' };
}

function resetSelections() {
    const slots   = kit.value?.slots ?? [];
    const initial = initialSelections.value;
    if (editMode.value && initial.length) {
        const hasSlotIdx = initial.some(s => s.slotIdx !== undefined);
        if (hasSlotIdx) {
            selections.value = slots.map((_, si) => {
                const obj = {};
                initial.filter(s => s.slotIdx === si).forEach(s => { obj[s.product_id] = s.qty; });
                return obj;
            });
        } else {
            const remaining = initial.map(s => ({ ...s }));
            selections.value = slots.map(slot => {
                const obj = {};
                let filled = 0;
                for (const sel of remaining) {
                    if (sel.size === slot.size && filled < slot.quantity) {
                        obj[sel.product_id] = sel.qty;
                        filled += sel.qty;
                    }
                }
                return obj;
            });
        }
    } else {
        selections.value = slots.map(() => ({}));
    }
}

async function loadProducts() {
    loadingProducts.value = true;
    try {
        const res = await axios.get(`/api/kits/${slug.value}/products`);
        availableProducts.value = res.data;
        resetSelections();
    } catch {
        availableProducts.value = [];
    } finally {
        loadingProducts.value = false;
    }
}

onMounted(async () => {
    try {
        const res = await axios.get(`/api/kits/${slug.value}`);
        kit.value = res.data;
        await loadProducts();
    } catch {
        router.replace({ name: 'home' });
    } finally {
        loadingKit.value = false;
    }
});

function handleCta() {
    if (!currentSlotComplete.value) return;
    if (!isLastSlot.value) {
        currentSlot.value++;
        return;
    }
    const selectedItems = [];
    kit.value.slots.forEach((slot, si) => {
        Object.entries(selections.value[si] ?? {}).forEach(([productId, qty]) => {
            if (qty > 0) {
                const product = availableProducts.value.find(p => p.id === Number(productId));
                if (product) selectedItems.push({ slotIdx: si, product_id: product.id, name: product.name, size: product.size, qty });
            }
        });
    });
    if (editMode.value && cartItemKey.value) {
        updateKitSelections(cartItemKey.value, selectedItems);
    } else {
        addSlottedKit(kit.value, selectedItems, kitQty.value);
    }
    router.push({ name: 'cart' });
}
</script>

<template>
    <!-- Loading -->
    <div v-if="loadingKit" class="min-h-screen flex items-center justify-center" style="background:#131313;">
        <div class="flex flex-col items-center gap-4">
            <div class="w-10 h-10 rounded-full border-2 animate-spin" style="border-color:#FF003C;border-top-color:transparent;" />
            <p class="text-sm uppercase tracking-widest font-bold" style="color:rgba(255,255,255,0.4);">Carregando...</p>
        </div>
    </div>

    <div v-else-if="kit" class="min-h-screen" style="background:#131313;color:#e5e2e1;">
        <!-- Psychedelic background glow -->
        <div class="pointer-events-none fixed inset-0" style="z-index:0;background:radial-gradient(circle at center,rgba(255,0,60,0.15) 0%,rgba(0,0,0,1) 70%);" />

        <!-- Page body -->
        <main class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 pt-8 pb-20
                     grid grid-cols-1 lg:grid-cols-2 gap-12 lg:gap-16 items-start">

            <!-- ── LEFT: Image (sticky on desktop) ── -->
            <div class="relative w-full aspect-[4/3] sm:aspect-square rounded-xl overflow-hidden group lg:sticky lg:top-24"
                 style="background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);">

                <div class="absolute inset-0 z-0 pointer-events-none"
                     style="background:linear-gradient(to top right,rgba(255,82,92,0.2),rgba(230,235,0,0.1));opacity:0.5;mix-blend-mode:overlay;" />

                <img v-if="currentImage"
                     :src="currentImage" :alt="kit.name"
                     class="w-full h-full object-cover relative z-10 scale-95 group-hover:scale-100 transition-transform duration-300"
                     style="filter:drop-shadow(0 0 40px rgba(200,40,48,0.35));" />
                <div v-else class="w-full h-full flex items-center justify-center" style="color:rgba(255,255,255,0.1);">
                    <i class="pi pi-box" style="font-size:80px;" />
                </div>

                <!-- Carousel nav -->
                <button v-if="allImages.length > 1"
                        class="absolute left-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/20 transition-colors"
                        style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);"
                        @click="prevImage">
                    <i class="pi pi-chevron-left text-white" style="font-size:10px;" />
                </button>
                <button v-if="allImages.length > 1"
                        class="absolute right-3 top-1/2 -translate-y-1/2 z-30 w-8 h-8 rounded-full flex items-center justify-center hover:bg-white/20 transition-colors"
                        style="background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.12);"
                        @click="nextImage">
                    <i class="pi pi-chevron-right text-white" style="font-size:10px;" />
                </button>

                <!-- Carousel dots -->
                <div v-if="allImages.length > 1" class="absolute bottom-3 left-0 right-0 flex justify-center gap-1.5 z-30">
                    <button v-for="(_, i) in allImages" :key="i"
                            class="rounded-full transition-all"
                            :style="i === imageIdx
                                ? 'width:20px;height:6px;background:#FF003C'
                                : 'width:6px;height:6px;background:rgba(255,255,255,0.3)'"
                            @click="imageIdx = i" />
                </div>

                <!-- Inset ring overlay -->
                <div class="absolute inset-0 rounded-xl pointer-events-none z-20"
                     style="box-shadow:inset 0 0 0 1px rgba(255,255,255,0.1);" />
            </div>

            <!-- ── RIGHT: Config ── -->
            <div class="flex flex-col gap-8 w-full">

                <!-- Kit header -->
                <div class="flex flex-col gap-2">
                    <button @click="router.back()"
                            class="flex items-center gap-2 w-fit mb-1 text-white/40 hover:text-white transition-colors">
                        <i class="pi pi-arrow-left" style="font-size:11px;" />
                        <span class="text-xs font-bold uppercase tracking-widest">Voltar</span>
                        <span v-if="editMode"
                              class="ml-1 text-xs px-2 py-0.5 rounded-full font-bold"
                              style="background:#FF003C;color:#fff;">
                            Editando
                        </span>
                    </button>

                    <h1 class="font-black uppercase leading-none tracking-tighter text-transparent bg-clip-text"
                        style="font-family:'Passion One',sans-serif;
                               font-size:clamp(2.5rem,6vw,4rem);
                               background-image:linear-gradient(to right,#fff,#c6c6c7);
                               -webkit-background-clip:text;background-clip:text;">
                        {{ kit.name }}
                    </h1>
                    <p v-if="kit.description" style="color:rgba(255,255,255,0.5);font-size:15px;line-height:1.6;">
                        {{ kit.description }}
                    </p>
                    <div class="mt-1">
                        <span v-if="kit.sale_price" class="line-through mr-2 text-sm" style="color:rgba(255,255,255,0.3);">
                            R$ {{ fmt(kit.price) }}
                        </span>
                        <span class="text-2xl font-bold" style="color:#ff525c;">
                            R$ {{ fmt(kit.effective_price) }}
                        </span>
                    </div>
                </div>

                <!-- Step 1: Quantidade (esconde em modo edição) -->
                <div v-if="!editMode" class="flex flex-col gap-3">
                    <h2 class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-white">
                        <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px]"
                              style="background:rgba(255,255,255,0.1);">1</span>
                        Quantidade
                    </h2>
                    <div class="grid grid-cols-3 gap-1.5">
                        <label v-for="n in [1, 2, 3]" :key="n" class="cursor-pointer relative">
                            <input type="radio" :value="n" v-model="kitQty" class="sr-only" />
                            <div class="rounded-lg border-2 flex flex-col items-center justify-center gap-1 py-4 transition-all duration-300"
                                 :style="kitQty === n
                                     ? 'background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border-color:#FF003C;box-shadow:0 0 15px rgba(255,0,60,0.6)'
                                     : 'background:rgba(18,18,18,0.7);backdrop-filter:blur(20px);border-color:rgba(255,255,255,0.1)'">
                                <span class="text-2xl font-bold text-white">{{ n }}</span>
                                <span class="text-xs font-bold uppercase" style="color:rgba(255,255,255,0.5);">
                                    {{ n === 1 ? 'Kit' : 'Kits' }}
                                </span>
                            </div>
                            <div v-if="kitQty === n" class="absolute top-2 right-2 text-xs" style="color:#e6eb00;">
                                <i class="pi pi-check-circle" />
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 2: Sabores (paginado por slot) -->
                <div class="flex flex-col gap-3">
                    <!-- Step header + paginação por dots -->
                    <div class="flex items-center justify-between">
                        <h2 class="flex items-center gap-2 text-xs font-black uppercase tracking-widest text-white">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-[10px]"
                                  style="background:rgba(255,255,255,0.1);">
                                {{ editMode ? 1 : 2 }}
                            </span>
                            Escolher Sabores
                        </h2>
                        <!-- Dots de navegação entre slots -->
                        <div v-if="(kit.slots?.length ?? 0) > 1" class="flex items-center gap-1.5">
                            <button v-for="(_, si) in kit.slots" :key="si"
                                    @click="currentSlot = si"
                                    class="rounded-full transition-all duration-200"
                                    :style="si === currentSlot
                                        ? 'width:20px;height:8px;background:#FF003C'
                                        : si < currentSlot
                                            ? 'width:8px;height:8px;background:rgba(74,222,128,0.6)'
                                            : 'width:8px;height:8px;background:rgba(255,255,255,0.2)'" />
                        </div>
                    </div>


                    <!-- Flavor list para o slot atual -->
                    <div v-if="loadingProducts" class="flex items-center justify-center py-8">
                        <div class="w-8 h-8 rounded-full border-2 animate-spin"
                             style="border-color:#FF003C;border-top-color:transparent;" />
                    </div>
                    <div v-else class="flex flex-col gap-2">
                        <div v-for="product in productsForCurrentSlot" :key="product.id"
                             class="rounded-lg flex items-center justify-between gap-2 sm:gap-4 cursor-pointer transition-all duration-300"
                             style="padding:12px;backdrop-filter:blur(20px);"
                             :style="(selections[currentSlot]?.[product.id] || 0) > 0
                                 ? 'background:rgba(18,18,18,0.7);border:1px solid rgba(255,82,92,0.4);box-shadow:0 0 12px rgba(255,0,60,0.1)'
                                 : 'background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.1)'"
                             @click="(selections[currentSlot]?.[product.id] || 0) === 0
                                 ? increment(currentSlot, product.id, currentSlotObj.quantity)
                                 : null">

                            <div class="flex items-center gap-2 sm:gap-3">
                                <div class="w-12 h-12 sm:w-16 sm:h-16 rounded overflow-hidden flex-shrink-0"
                                     style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
                                    <img v-if="product.thumbnail"
                                         :src="product.thumbnail" :alt="product.name"
                                         class="w-full h-full object-contain p-1" />
                                </div>
                                <div>
                                    <div class="flex items-baseline gap-2 flex-wrap">
                                        <h3 class="font-bold text-white text-sm leading-tight">{{ product.name }}</h3>
                                        <span class="text-xs" style="color:rgba(255,255,255,0.4);">{{ product.brand }} · {{ product.size }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Contador +/- -->
                            <div class="flex items-center gap-2 flex-shrink-0" @click.stop>
                                <button v-if="(selections[currentSlot]?.[product.id] || 0) > 0"
                                        class="w-8 h-8 rounded-full border flex items-center justify-center font-black text-base transition-all"
                                        style="border-color:rgba(255,0,60,0.4);color:#ff525c;"
                                        @click="decrement(currentSlot, product.id)">
                                    −
                                </button>
                                <span v-if="(selections[currentSlot]?.[product.id] || 0) > 0"
                                      class="w-6 text-center font-black text-sm"
                                      style="color:#e6eb00;">
                                    {{ selections[currentSlot]?.[product.id] }}
                                </span>
                                <button class="w-10 h-10 rounded-full border flex items-center justify-center transition-all"
                                        :style="canAdd(currentSlot, currentSlotObj?.quantity ?? 0)
                                            ? 'border-color:rgba(255,255,255,0.2);color:#fff'
                                            : 'border-color:rgba(255,255,255,0.06);color:rgba(255,255,255,0.15);cursor:not-allowed'"
                                        :disabled="!canAdd(currentSlot, currentSlotObj?.quantity ?? 0)"
                                        @click="increment(currentSlot, product.id, currentSlotObj?.quantity ?? 0)">
                                    <i class="pi pi-plus" style="font-size:14px;" />
                                </button>
                            </div>
                        </div>

                        <p v-if="!loadingProducts && productsForCurrentSlot.length === 0"
                           class="text-sm italic py-4" style="color:rgba(255,255,255,0.3);">
                            Nenhum produto disponível neste tamanho.
                        </p>
                    </div>
                </div>

                <!-- CTA Button -->
                <button class="w-full py-5 font-black uppercase tracking-widest text-lg rounded-lg transition-all duration-300"
                        style="font-family:'Passion One',sans-serif;"
                        :class="currentSlotComplete ? 'hover:ring-2 hover:ring-[#e6eb00] hover:shadow-[0_0_20px_rgba(230,235,0,0.5)]' : ''"
                        :style="currentSlotComplete
                            ? 'background:#FF003C;color:#fff;box-shadow:0 4px 20px rgba(255,0,60,0.4)'
                            : 'background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.2);cursor:not-allowed'"
                        :disabled="!currentSlotComplete"
                        @click="handleCta">
                    {{ ctaLabel }}
                </button>

                <!-- Voltar etapa anterior -->
                <button v-if="currentSlot > 0"
                        class="flex items-center justify-center gap-2 w-full py-2 text-xs font-bold uppercase tracking-widest transition-colors text-white/30 hover:text-white/60"
                        @click="currentSlot--">
                    <i class="pi pi-arrow-left" style="font-size:10px;" />
                    Etapa anterior
                </button>
            </div>
        </main>
    </div>

    <!-- Cashew rain -->
    <Teleport to="body">
        <div v-if="cashewParticles.length"
             class="fixed inset-0 pointer-events-none overflow-hidden"
             style="z-index:99999;">
            <div v-for="p in cashewParticles" :key="p.id"
                 class="cashew-particle absolute"
                 :style="p.style">
                <svg viewBox="0 0 56 78" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">
                    <defs>
                        <filter id="cf" x="-20%" y="-20%" width="140%" height="140%">
                            <feGaussianBlur in="SourceAlpha" stdDeviation="1.5" result="blur"/>
                            <feOffset dx="1" dy="2" result="shadow"/>
                            <feFlood flood-color="rgba(0,0,0,0.25)" result="color"/>
                            <feComposite in="color" in2="shadow" operator="in" result="shadow-color"/>
                            <feMerge><feMergeNode in="shadow-color"/><feMergeNode in="SourceGraphic"/></feMerge>
                        </filter>
                    </defs>
                    <path d="M29,5 C44,4 54,16 53,30 C52,44 44,58 34,66 C25,73 13,73 8,63 C3,54 7,42 12,33 C17,24 17,16 14,10 C11,5 18,2 29,5 Z"
                          fill="#ECD89A" stroke="#C4A060" stroke-width="0.8" filter="url(#cf)"/>
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
    </Teleport>
</template>

<style scoped>
.slot-ball {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.04);
    border: 1.5px solid rgba(255, 255, 255, 0.1);
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}
.ball-fill {
    transition: height 0.55s cubic-bezier(0.4, 0, 0.2, 1), background 0.3s ease;
    border-radius: 0 0 50% 50%;
}
.slot-ball.ball-pop {
    animation: ball-pop 0.55s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}
@keyframes ball-pop {
    0%   { transform: scale(0.75); }
    55%  { transform: scale(1.22); }
    100% { transform: scale(1); }
}
.cashew-particle {
    top: -70px;
    aspect-ratio: 56 / 78;
    animation: cashew-fall var(--dur, 1.5s) var(--delay, 0s) ease-in forwards;
    will-change: transform, opacity;
}
@keyframes cashew-fall {
    0% {
        transform: translateY(0) translateX(0) rotate(var(--rot0, 0deg));
        opacity: 1;
    }
    50% {
        transform: translateY(50vh) translateX(calc(var(--drift, 0px) * 0.6)) rotate(calc((var(--rot0, 0deg) + var(--rot1, 360deg)) / 2));
    }
    85% { opacity: 1; }
    100% {
        transform: translateY(108vh) translateX(var(--drift, 0px)) rotate(var(--rot1, 360deg));
        opacity: 0;
    }
}
</style>
