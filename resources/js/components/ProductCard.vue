<script setup>
import { computed } from 'vue';
import FavoriteButton from './FavoriteButton.vue';

const props = defineProps({
    product: { type: Object, required: true },
});

const discount = computed(() => {
    if (props.product.original_price && props.product.original_price > props.product.price) {
        return Math.round((1 - props.product.price / props.product.original_price) * 100);
    }
    return null;
});

const stars = computed(() => Math.round(props.product.rating ?? 0));
</script>

<template>
    <router-link
        :to="`/produto/${product.slug}`"
        class="card-brutal flex flex-col overflow-hidden group hover:translate-x-[2px] hover:translate-y-[2px] hover:shadow-none transition-all duration-100"
    >
        <!-- Image -->
        <div class="relative bg-gray-100 aspect-square overflow-hidden">
            <img
                v-if="product.thumbnail"
                :src="product.thumbnail"
                :alt="product.name"
                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="w-full h-full flex items-center justify-center text-6xl bg-brand-yellow/20">
                💪
            </div>
            <span v-if="discount" class="badge-discount absolute top-2 left-2">
                -{{ discount }}%
            </span>
            <span v-if="product.featured && !discount" class="absolute top-2 left-2 bg-brand-yellow text-black font-black text-xs px-2 py-1 rounded-lg border border-black">
                DESTAQUE
            </span>
            <FavoriteButton type="product" :item-id="product.id"
                            size="sm"
                            class="absolute top-2 right-2" />
        </div>

        <!-- Info -->
        <div class="p-4 flex flex-col gap-2 flex-1">
            <p class="text-xs font-black uppercase text-gray-500 tracking-widest">{{ product.brand }}</p>
            <h3 class="font-black text-sm leading-tight line-clamp-2">{{ product.name }}</h3>

            <!-- Stars -->
            <div v-if="product.rating" class="flex items-center gap-1">
                <span v-for="i in 5" :key="i" class="text-xs" :class="i <= stars ? 'text-brand-yellow' : 'text-gray-300'">★</span>
                <span class="text-xs text-gray-500 font-semibold">({{ product.reviews_count }})</span>
            </div>

            <!-- Price -->
            <div class="mt-auto pt-2 flex items-end gap-2">
                <span class="font-black text-xl">
                    R$ {{ product.price.toFixed(2).replace('.', ',') }}
                </span>
                <span v-if="product.original_price" class="text-sm text-gray-400 line-through mb-0.5">
                    R$ {{ product.original_price.toFixed(2).replace('.', ',') }}
                </span>
            </div>

            <button
                class="btn-primary w-full text-sm py-2 mt-1"
                @click.prevent="$router.push(`/produto/${product.slug}`)"
            >
                Ver produto
            </button>
        </div>
    </router-link>
</template>
