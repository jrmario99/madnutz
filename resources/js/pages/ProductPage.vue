<script setup>
import { ref, computed, onMounted, watch } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '../composables/useApi.js';
import { useCart } from '../stores/cart.js';

const route = useRoute();
const { getProduct } = useApi();
const { add } = useCart();

const product = ref(null);
const loading = ref(true);
const selectedVariant = ref(null);
const qty = ref(1);
const activeImage = ref(0);
const added = ref(false);

const finalPrice = computed(() => {
    if (!product.value) return 0;
    return product.value.price + (selectedVariant.value?.price_modifier ?? 0);
});

const discount = computed(() => {
    if (!product.value) return null;
    if (product.value.original_price && product.value.original_price > product.value.price) {
        return Math.round((1 - product.value.price / product.value.original_price) * 100);
    }
    return null;
});

const variantsByLabel = computed(() => {
    if (!product.value?.variants) return {};
    return product.value.variants.reduce((acc, v) => {
        if (!acc[v.label]) acc[v.label] = [];
        acc[v.label].push(v);
        return acc;
    }, {});
});

async function load(slug) {
    loading.value = true;
    product.value = await getProduct(slug);
    selectedVariant.value = product.value.variants?.[0] ?? null;
    activeImage.value = 0;
    loading.value = false;
}

function addToCart() {
    add(product.value, selectedVariant.value, qty.value);
    added.value = true;
    setTimeout(() => added.value = false, 2000);
}

onMounted(() => load(route.params.slug));
watch(() => route.params.slug, load);
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <div class="text-xs font-semibold text-gray-500 mb-6">
            <router-link to="/" class="hover:text-black">Home</router-link>
            <span class="mx-1">/</span>
            <router-link v-if="product" :to="`/categoria/${product.category?.slug}`" class="hover:text-black uppercase">
                {{ product.category?.name }}
            </router-link>
            <span class="mx-1">/</span>
            <span class="text-black font-black">{{ product?.name }}</span>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="card-brutal animate-pulse aspect-square bg-gray-100" />
            <div class="space-y-4">
                <div class="h-6 bg-gray-100 rounded-xl w-1/3 animate-pulse" />
                <div class="h-10 bg-gray-100 rounded-xl animate-pulse" />
                <div class="h-4 bg-gray-100 rounded-xl w-1/2 animate-pulse" />
            </div>
        </div>

        <!-- Product detail -->
        <div v-else-if="product" class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-12">
            <!-- Gallery -->
            <div class="flex flex-col gap-3">
                <div class="card-brutal aspect-square overflow-hidden relative">
                    <img
                        v-if="product.images?.length"
                        :src="product.images[activeImage]?.url"
                        :alt="product.name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full bg-brand-yellow/20 flex items-center justify-center text-8xl">
                        💪
                    </div>
                    <span v-if="discount" class="badge-discount absolute top-4 left-4 text-sm px-3 py-1">
                        -{{ discount }}%
                    </span>
                </div>
                <div v-if="product.images?.length > 1" class="flex gap-2">
                    <button
                        v-for="(img, idx) in product.images"
                        :key="idx"
                        class="w-20 h-20 border-2 rounded-xl overflow-hidden transition-all"
                        :class="idx === activeImage ? 'border-black shadow-brutal' : 'border-gray-200'"
                        @click="activeImage = idx"
                    >
                        <img :src="img.url" :alt="`${product.name} ${idx + 1}`" class="w-full h-full object-cover" />
                    </button>
                </div>
            </div>

            <!-- Info -->
            <div class="flex flex-col gap-5">
                <div>
                    <p class="text-xs font-black uppercase text-gray-500 tracking-widest mb-1">{{ product.brand }}</p>
                    <h1 class="font-black text-3xl md:text-4xl uppercase leading-tight">{{ product.name }}</h1>
                </div>

                <!-- Rating -->
                <div v-if="product.rating" class="flex items-center gap-2">
                    <div class="flex">
                        <span v-for="i in 5" :key="i" class="text-lg" :class="i <= Math.round(product.rating) ? 'text-brand-yellow' : 'text-gray-200'">★</span>
                    </div>
                    <span class="font-black text-sm">{{ product.rating }}</span>
                    <span class="text-sm text-gray-500">({{ product.reviews_count }} avaliações)</span>
                </div>

                <!-- Price -->
                <div class="flex items-end gap-3">
                    <span class="font-black text-5xl">
                        R$ {{ finalPrice.toFixed(2).replace('.', ',') }}
                    </span>
                    <span v-if="product.original_price" class="text-lg text-gray-400 line-through mb-1">
                        R$ {{ product.original_price.toFixed(2).replace('.', ',') }}
                    </span>
                </div>

                <!-- Variants -->
                <div v-for="(variants, label) in variantsByLabel" :key="label">
                    <p class="font-black uppercase text-sm mb-2">{{ label }}:</p>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="v in variants"
                            :key="v.id"
                            class="px-4 py-2 border-2 border-black rounded-xl font-bold text-sm transition-all"
                            :class="selectedVariant?.id === v.id
                                ? 'bg-black text-brand-yellow shadow-brutal translate-x-[2px] translate-y-[2px]'
                                : 'bg-white hover:bg-brand-yellow'"
                            @click="selectedVariant = v"
                        >
                            {{ v.value }}
                            <span v-if="v.price_modifier > 0" class="text-xs"> +R${{ v.price_modifier.toFixed(2) }}</span>
                        </button>
                    </div>
                </div>

                <!-- Qty + Add to cart -->
                <div class="flex gap-3 items-center">
                    <div class="flex border-2 border-black rounded-xl overflow-hidden shadow-brutal">
                        <button class="px-4 py-3 font-black text-lg hover:bg-brand-yellow transition-colors" @click="qty = Math.max(1, qty - 1)">−</button>
                        <span class="px-5 py-3 font-black text-lg border-x-2 border-black">{{ qty }}</span>
                        <button class="px-4 py-3 font-black text-lg hover:bg-brand-yellow transition-colors" @click="qty++">+</button>
                    </div>
                    <button
                        class="btn-primary flex-1 text-base py-3 transition-all"
                        :class="added ? '!bg-green-400' : ''"
                        @click="addToCart"
                    >
                        {{ added ? '✓ Adicionado!' : '🛒 Adicionar ao Carrinho' }}
                    </button>
                </div>

                <!-- Description -->
                <div v-if="product.description" class="card-brutal p-4">
                    <h3 class="font-black uppercase text-sm mb-2">Descrição</h3>
                    <p class="text-sm text-gray-700 leading-relaxed">{{ product.description }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
