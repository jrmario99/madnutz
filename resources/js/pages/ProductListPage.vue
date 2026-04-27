<script setup>
import { ref, watch, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '../composables/useApi.js';
import ProductCard from '../components/ProductCard.vue';

const route = useRoute();
const { getProducts, getCategories } = useApi();

const products = ref([]);
const meta = ref(null);
const loading = ref(true);
const categories = ref([]);

const sortOptions = [
    { label: 'Mais relevantes', value: '' },
    { label: 'Menor preço', value: 'price_asc' },
    { label: 'Maior preço', value: 'price_desc' },
    { label: 'Mais avaliados', value: 'rating' },
];
const sort = ref('');
const page = ref(1);

const currentCategory = computed(() => {
    return categories.value.find(c => c.slug === route.params.slug) ?? null;
});

async function load() {
    loading.value = true;
    const params = {
        per_page: 12,
        page: page.value,
    };
    if (route.params.slug) params.category = route.params.slug;
    if (route.query.q) params.search = route.query.q;

    const res = await getProducts(params);
    products.value = res.data;
    meta.value = res.meta;
    loading.value = false;
}

onMounted(async () => {
    categories.value = await getCategories();
    await load();
});

watch(() => [route.params.slug, route.query.q, page.value], () => {
    load();
});
</script>

<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Breadcrumb + title -->
        <div class="mb-6">
            <div class="text-xs font-semibold text-gray-500 mb-1">
                <router-link to="/" class="hover:text-black">Home</router-link>
                <span class="mx-1">/</span>
                <span class="text-black font-black uppercase">
                    {{ currentCategory?.name ?? (route.query.q ? `"${route.query.q}"` : 'Produtos') }}
                </span>
            </div>
            <div class="flex items-center justify-between gap-4">
                <h1 class="font-black text-3xl uppercase">
                    <span v-if="currentCategory">{{ currentCategory.icon }} {{ currentCategory.name }}</span>
                    <span v-else-if="route.query.q">Busca: "{{ route.query.q }}"</span>
                    <span v-else>Todos os produtos</span>
                </h1>
                <span v-if="meta" class="text-sm text-gray-500 font-semibold flex-shrink-0">
                    {{ meta.total }} produto{{ meta.total !== 1 ? 's' : '' }}
                </span>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-6">
            <!-- Sidebar: categories -->
            <aside class="md:w-56 flex-shrink-0">
                <div class="card-brutal p-4">
                    <h3 class="font-black uppercase text-sm mb-3 border-b-2 border-black pb-2">Categorias</h3>
                    <ul class="space-y-1">
                        <li v-for="cat in categories" :key="cat.slug">
                            <router-link
                                :to="`/categoria/${cat.slug}`"
                                class="flex items-center gap-2 px-2 py-1.5 rounded-lg font-bold text-sm hover:bg-brand-yellow transition-colors"
                                :class="{ 'bg-brand-yellow border-2 border-black': route.params.slug === cat.slug }"
                            >
                                <span>{{ cat.icon }}</span>
                                <span>{{ cat.name }}</span>
                            </router-link>
                        </li>
                    </ul>
                </div>
            </aside>

            <!-- Main content -->
            <div class="flex-1">
                <!-- Sort bar -->
                <div class="flex items-center justify-between mb-4 gap-4">
                    <div class="flex gap-2 flex-wrap">
                        <button
                            v-for="opt in sortOptions"
                            :key="opt.value"
                            class="text-xs font-black uppercase px-3 py-1.5 rounded-lg border-2 border-black transition-colors"
                            :class="sort === opt.value ? 'bg-black text-brand-yellow' : 'bg-white hover:bg-brand-yellow'"
                            @click="sort = opt.value"
                        >
                            {{ opt.label }}
                        </button>
                    </div>
                </div>

                <!-- Grid -->
                <div v-if="loading" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div v-for="i in 6" :key="i" class="card-brutal animate-pulse aspect-[3/4] bg-gray-100" />
                </div>

                <div v-else-if="products.length === 0" class="card-brutal p-12 text-center">
                    <p class="text-4xl mb-4">😢</p>
                    <p class="font-black text-xl uppercase">Nenhum produto encontrado</p>
                    <router-link to="/" class="btn-primary inline-block mt-6">Voltar ao início</router-link>
                </div>

                <div v-else class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <ProductCard v-for="product in products" :key="product.id" :product="product" />
                </div>

                <!-- Pagination -->
                <div v-if="meta && meta.last_page > 1" class="flex justify-center gap-2 mt-8">
                    <button
                        v-for="p in meta.last_page"
                        :key="p"
                        class="w-10 h-10 font-black border-2 border-black rounded-xl transition-colors"
                        :class="p === meta.current_page ? 'bg-black text-brand-yellow' : 'bg-white hover:bg-brand-yellow'"
                        @click="page = p"
                    >
                        {{ p }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
