<template>
    <Dialog v-model:visible="visible" modal :closable="true" :style="{ width: '940px', maxWidth: '95vw' }"
            :pt="{
                header:      { style: 'padding:0' },
                content:     { style: 'padding:0' },
                footer:      { style: 'padding:0' },
                closeButton: { style: 'width:auto;height:auto;background:none;border:none;box-shadow:none;padding:8px;' },
                closeIcon:   { style: 'width:16px;height:16px;color:#6b7280;' }
            }">

        <template #header>
            <div class="w-full px-6 pt-5 pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <span v-if="editMode" class="text-xs px-2 py-0.5 rounded-full font-bold uppercase"
                          style="background:#FEF3C7;color:#92400E;">Editando</span>
                    <h2 class="uppercase text-mn-black leading-none"
                        style="font-family:'Passion One',sans-serif; font-size:28px; font-weight:600;">
                        {{ kit?.name }}
                    </h2>
                </div>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ editMode ? 'Altere os sabores do seu kit' : 'Escolha os sabores do seu kit' }}
                </p>
            </div>
        </template>

        <div v-if="kit" class="flex flex-col md:flex-row min-h-[440px]">

            <!-- Left: image carousel -->
            <div class="md:w-2/5 flex flex-col items-center justify-center bg-gray-50 p-6 relative"
                 style="border-right:1px solid #f3f4f6;">

                <div v-if="kit.badge"
                     class="absolute top-4 left-4 text-mn-black text-xs font-bold uppercase px-3 py-1 tracking-wide"
                     style="font-family:'Readex Pro',sans-serif; background:#FFDF00; border-radius:20px;">
                    🔥 {{ kit.badge }}
                </div>

                <div class="relative w-full flex items-center justify-center" style="min-height:240px;">
                    <button v-if="allImages.length > 1"
                            class="absolute left-0 z-10 p-2 rounded-full bg-white shadow hover:bg-gray-100 transition"
                            @click="prevImage">
                        <i class="pi pi-chevron-left text-xs text-gray-600" />
                    </button>

                    <img :src="currentImage" :alt="kit.name"
                         class="object-contain max-h-56 w-full transition-opacity duration-200"
                         style="max-width:260px;" />

                    <button v-if="allImages.length > 1"
                            class="absolute right-0 z-10 p-2 rounded-full bg-white shadow hover:bg-gray-100 transition"
                            @click="nextImage">
                        <i class="pi pi-chevron-right text-xs text-gray-600" />
                    </button>
                </div>

                <div v-if="allImages.length > 1" class="flex gap-1.5 mt-3">
                    <button v-for="(_, i) in allImages" :key="i"
                            class="w-2 h-2 rounded-full transition-colors"
                            :style="i === imageIdx ? 'background:#C82830' : 'background:#d1d5db'"
                            @click="imageIdx = i" />
                </div>

                <div class="mt-4 w-full">
                    <p class="text-xs text-gray-400 uppercase tracking-widest font-semibold mb-1">Composição</p>
                    <div class="flex flex-wrap gap-1">
                        <span v-for="slot in kit.slots" :key="slot.id"
                              class="text-xs px-2 py-0.5 rounded-full bg-white border border-gray-200 text-gray-600">
                            {{ slot.quantity }}× {{ slot.size }}
                        </span>
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <span v-if="kit.sale_price" class="line-through text-gray-400 text-sm mr-2">
                        R$ {{ fmt(kit.price) }}
                    </span>
                    <span class="font-bold" style="font-family:'Passion One',sans-serif; font-size:36px; color:#C82830;">
                        R$ {{ fmt(kit.effective_price) }}
                    </span>
                </div>
            </div>

            <!-- Right: slot selectors -->
            <div class="md:w-3/5 flex flex-col overflow-y-auto" style="max-height:540px;">

                <div v-if="loadingProducts" class="flex-1 flex items-center justify-center p-8">
                    <i class="pi pi-spinner pi-spin text-2xl text-gray-400" />
                </div>

                <div v-else class="p-5 space-y-6 flex-1">
                    <div v-for="(slot, si) in kit.slots" :key="si">

                        <div class="flex items-center justify-between mb-3">
                            <p class="font-bold text-mn-black uppercase"
                               style="font-family:'Passion One',sans-serif; font-size:17px;">
                                {{ slot.quantity }}× {{ slot.size }} — Escolha {{ slot.quantity }} sabor{{ slot.quantity > 1 ? 'es' : '' }}
                            </p>
                            <span class="text-xs px-2 py-0.5 rounded-full font-bold"
                                  :style="slotCount(si) === slot.quantity
                                      ? 'background:#D1FAE5;color:#065F46'
                                      : 'background:#FEF3C7;color:#92400E'">
                                {{ slotCount(si) }}/{{ slot.quantity }}
                            </span>
                        </div>

                        <div v-if="productsBySize[slot.size]?.length" class="space-y-2">
                            <div v-for="product in productsBySize[slot.size]" :key="product.id"
                                 class="flex items-center gap-3 p-2.5 rounded-xl border transition-colors"
                                 :style="(selections[si]?.[product.id] || 0) > 0
                                     ? 'border-color:#C82830;background:#fff5f5'
                                     : 'border-color:#f3f4f6;background:#fafafa'">

                                <img v-if="product.thumbnail" :src="product.thumbnail" :alt="product.name"
                                     class="w-12 h-12 object-contain rounded-lg flex-shrink-0 bg-white border border-gray-100" />
                                <div v-else class="w-12 h-12 rounded-lg bg-gray-200 flex-shrink-0" />

                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-mn-black truncate">{{ product.name }}</p>
                                    <p class="text-xs text-gray-400">{{ product.brand }} · {{ product.size }}</p>
                                </div>

                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-sm transition-colors"
                                            :style="(selections[si]?.[product.id] || 0) > 0
                                                ? 'background:#C82830;color:#fff'
                                                : 'background:#e5e7eb;color:#6b7280'"
                                            @click="decrement(si, product.id)">
                                        −
                                    </button>
                                    <span class="w-6 text-center text-sm font-bold text-mn-black">
                                        {{ selections[si]?.[product.id] || 0 }}
                                    </span>
                                    <button class="w-7 h-7 rounded-lg flex items-center justify-center font-bold text-sm transition-colors"
                                            :style="canAdd(si, slot.quantity)
                                                ? 'background:#C82830;color:#fff'
                                                : 'background:#e5e7eb;color:#9ca3af;cursor:not-allowed'"
                                            :disabled="!canAdd(si, slot.quantity)"
                                            @click="increment(si, product.id, slot.quantity)">
                                        +
                                    </button>
                                </div>
                            </div>
                        </div>

                        <p v-else class="text-sm text-gray-400 italic py-2">Nenhum produto disponível neste tamanho.</p>
                    </div>
                </div>

                <!-- Footer CTA -->
                <div class="p-4 border-t border-gray-100 bg-white sticky bottom-0">
                    <p v-if="!allSlotsComplete" class="text-xs text-amber-600 mb-2 text-center">
                        Complete todos os slots para continuar.
                    </p>
                    <button
                        class="w-full uppercase font-bold text-white transition-opacity"
                        style="font-family:'Passion One',sans-serif; font-size:20px; background:#C82830; padding:14px; border-radius:10px;"
                        :style="!allSlotsComplete ? 'opacity:.45;cursor:not-allowed' : ''"
                        :disabled="!allSlotsComplete"
                        @click="confirm">
                        <i :class="editMode ? 'pi pi-check' : 'pi pi-shopping-cart'" class="mr-2 text-base" />
                        {{ editMode ? 'SALVAR ALTERAÇÕES' : 'ADICIONAR AO CARRINHO' }}
                    </button>
                </div>
            </div>
        </div>
    </Dialog>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import Dialog from 'primevue/dialog';
import axios from 'axios';
import { useCart } from '../stores/cart.js';

const props = defineProps({
    modelValue:        Boolean,
    kit:               Object,
    editMode:          { type: Boolean, default: false },
    initialSelections: { type: Array,   default: () => [] },
    cartItemKey:       { type: String,  default: '' },
});
const emit = defineEmits(['update:modelValue', 'added']);

const { addSlottedKit, updateKitSelections } = useCart();

const visible = computed({
    get: () => props.modelValue,
    set: v => emit('update:modelValue', v),
});

const imageIdx          = ref(0);
const loadingProducts   = ref(false);
const availableProducts = ref([]);
const selections        = ref([]);

const allImages = computed(() => {
    if (!props.kit) return [];
    const imgs = (props.kit.images ?? []).map(i => i.url).filter(Boolean);
    if (imgs.length === 0 && props.kit.image) return [props.kit.image];
    return imgs;
});

const currentImage = computed(() => allImages.value[imageIdx.value] ?? props.kit?.image ?? '');

const productsBySize = computed(() => {
    const map = {};
    for (const p of availableProducts.value) {
        if (!map[p.size]) map[p.size] = [];
        map[p.size].push(p);
    }
    return map;
});

function slotCount(si) {
    return Object.values(selections.value[si] ?? {}).reduce((a, b) => a + b, 0);
}

function canAdd(si, max) {
    return slotCount(si) < max;
}

function increment(si, productId, max) {
    if (!canAdd(si, max)) return;
    if (!selections.value[si]) selections.value[si] = {};
    selections.value[si][productId] = (selections.value[si][productId] ?? 0) + 1;
}

function decrement(si, productId) {
    if (!selections.value[si]) return;
    const cur = selections.value[si][productId] ?? 0;
    if (cur <= 0) return;
    selections.value[si][productId] = cur - 1;
}

const allSlotsComplete = computed(() => {
    if (!props.kit?.slots?.length) return false;
    return props.kit.slots.every((slot, si) => slotCount(si) === slot.quantity);
});

function prevImage() {
    imageIdx.value = (imageIdx.value - 1 + allImages.value.length) % allImages.value.length;
}
function nextImage() {
    imageIdx.value = (imageIdx.value + 1) % allImages.value.length;
}

const fmt = v => Number(v).toFixed(2).replace('.', ',');

async function loadProducts() {
    const identifier = props.kit?.slug ?? props.kit?.id;
    if (!identifier) return;
    loadingProducts.value = true;
    try {
        const res = await axios.get(`/api/kits/${identifier}/products`);
        availableProducts.value = res.data;
        // Re-apply selections after products load so quantities show correctly
        if (props.editMode && props.initialSelections?.length) {
            resetSelections();
        }
    } catch {
        availableProducts.value = [];
    } finally {
        loadingProducts.value = false;
    }
}

function resetSelections() {
    imageIdx.value = 0;
    const slots = props.kit?.slots ?? [];

    if (props.editMode && props.initialSelections?.length) {
        const hasSlotIdx = props.initialSelections.some(s => s.slotIdx !== undefined);

        if (hasSlotIdx) {
            // Rebuild by slotIdx (new format)
            selections.value = slots.map((_, si) => {
                const obj = {};
                props.initialSelections
                    .filter(s => s.slotIdx === si)
                    .forEach(s => { obj[s.product_id] = s.qty; });
                return obj;
            });
        } else {
            // Fallback: match by size (old format without slotIdx)
            const remaining = props.initialSelections.map(s => ({ ...s }));
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

watch(() => props.modelValue, open => {
    if (open && props.kit) {
        resetSelections();
        loadProducts();
    }
});

watch(() => props.kit, kit => {
    if (kit && props.modelValue) {
        resetSelections();
        loadProducts();
    }
});

function confirm() {
    if (!allSlotsComplete.value) return;

    const selectedItems = [];
    props.kit.slots.forEach((slot, si) => {
        Object.entries(selections.value[si] ?? {}).forEach(([productId, qty]) => {
            if (qty > 0) {
                const product = availableProducts.value.find(p => p.id === Number(productId));
                if (product) {
                    selectedItems.push({ slotIdx: si, product_id: product.id, name: product.name, size: product.size, qty });
                }
            }
        });
    });

    if (props.editMode && props.cartItemKey) {
        updateKitSelections(props.cartItemKey, selectedItems);
    } else {
        addSlottedKit(props.kit, selectedItems);
    }

    visible.value = false;
    emit('added');
}
</script>
