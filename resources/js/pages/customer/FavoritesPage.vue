<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useCustomer } from '../../stores/customer.js';
import { useFavorites } from '../../stores/favorites.js';

const router = useRouter();
const { api } = useCustomer();
const { isFavorited, toggle, load } = useFavorites();

const favorites = ref([]);
const loading   = ref(true);

const fmt = v => Number(v).toFixed(2).replace('.', ',');

const products = computed(() => favorites.value.filter(f => f.type === 'Product'));
const kits     = computed(() => favorites.value.filter(f => f.type === 'Kit'));

async function fetchFavorites() {
    loading.value = true;
    try {
        const res = await api().get('/customer/favorites');
        favorites.value = res.data;
        // Sync the global favorites store with fresh data
        await load();
    } catch {}
    finally { loading.value = false; }
}

async function remove(type, id) {
    await toggle(type.toLowerCase(), id);
    favorites.value = favorites.value.filter(
        f => !(f.type === type && f.favoritable_id === id)
    );
}

onMounted(fetchFavorites);
</script>

<template>
    <div>
        <h1 class="text-3xl font-black uppercase tracking-tighter mb-6"
            style="font-family:'Passion One',sans-serif;color:#fff;">
            Favoritos
        </h1>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-20">
            <div class="w-10 h-10 rounded-full border-2 animate-spin"
                 style="border-color:#FF003C;border-top-color:transparent;" />
        </div>

        <!-- Vazio -->
        <div v-else-if="favorites.length === 0"
             class="flex flex-col items-center justify-center py-20 gap-4 text-center rounded-2xl"
             style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
            <i class="pi pi-heart" style="font-size:48px;color:rgba(255,255,255,0.15);" />
            <p class="text-lg font-bold text-white/50">Nenhum favorito ainda</p>
            <router-link to="/"
                         class="px-6 py-3 rounded-lg font-black uppercase tracking-widest text-sm transition-all"
                         style="background:#FF003C;color:#fff;">
                Ver produtos
            </router-link>
        </div>

        <div v-else class="flex flex-col gap-8">
            <!-- Produtos -->
            <section v-if="products.length">
                <h2 class="text-xs font-black uppercase tracking-widest mb-3"
                    style="color:rgba(255,255,255,0.4);">
                    Produtos ({{ products.length }})
                </h2>
                <div class="flex flex-col gap-3">
                    <div v-for="fav in products" :key="fav.id"
                         class="rounded-xl flex items-center gap-4 px-4 py-3 transition-all"
                         style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
                        <!-- Thumbnail -->
                        <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0"
                             style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
                            <img v-if="fav.item?.thumbnail"
                                 :src="fav.item.thumbnail" :alt="fav.item?.name"
                                 class="w-full h-full object-contain p-1" />
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-white text-sm truncate">{{ fav.item?.name }}</p>
                            <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.4);">
                                {{ fav.item?.brand }}
                                <template v-if="fav.item?.size"> · {{ fav.item.size }}</template>
                            </p>
                            <p class="font-black mt-1" style="color:#ff525c;font-size:15px;">
                                R$ {{ fmt(fav.item?.price ?? 0) }}
                            </p>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <router-link :to="`/produto/${fav.item?.slug}`"
                                         class="text-xs font-bold uppercase tracking-widest px-3 py-2 rounded-lg transition-all"
                                         style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.1);">
                                Ver
                            </router-link>
                            <button @click="remove('Product', fav.favoritable_id)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-all"
                                    style="background:rgba(255,0,60,0.08);border:1px solid rgba(255,0,60,0.2);color:#ff525c;">
                                <i class="pi pi-heart-fill" style="font-size:13px;" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Kits -->
            <section v-if="kits.length">
                <h2 class="text-xs font-black uppercase tracking-widest mb-3"
                    style="color:rgba(255,255,255,0.4);">
                    Kits ({{ kits.length }})
                </h2>
                <div class="flex flex-col gap-3">
                    <div v-for="fav in kits" :key="fav.id"
                         class="rounded-xl flex items-center gap-4 px-4 py-3 transition-all"
                         style="background:rgba(18,18,18,0.7);border:1px solid rgba(255,255,255,0.08);">
                        <!-- Thumbnail -->
                        <div class="w-14 h-14 rounded-lg overflow-hidden flex-shrink-0"
                             style="background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.08);">
                            <img v-if="fav.item?.image"
                                 :src="fav.item.image" :alt="fav.item?.name"
                                 class="w-full h-full object-contain p-1" />
                            <div v-else class="w-full h-full flex items-center justify-center"
                                 style="color:rgba(255,255,255,0.15);">
                                <i class="pi pi-box" style="font-size:20px;" />
                            </div>
                        </div>
                        <!-- Info -->
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-white text-sm truncate">{{ fav.item?.name }}</p>
                            <p class="text-xs mt-0.5" style="color:rgba(255,255,255,0.4);">
                                Kit · {{ fav.item?.slots_count ?? '?' }} sabores
                            </p>
                            <p class="font-black mt-1" style="color:#ff525c;font-size:15px;">
                                R$ {{ fmt(fav.item?.effective_price ?? fav.item?.price ?? 0) }}
                            </p>
                        </div>
                        <!-- Actions -->
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <router-link v-if="fav.item?.slug"
                                         :to="`/kits/${fav.item.slug}/sabores`"
                                         class="text-xs font-bold uppercase tracking-widest px-3 py-2 rounded-lg transition-all"
                                         style="background:rgba(255,255,255,0.06);color:rgba(255,255,255,0.6);border:1px solid rgba(255,255,255,0.1);">
                                Montar
                            </router-link>
                            <button @click="remove('Kit', fav.favoritable_id)"
                                    class="w-8 h-8 rounded-lg flex items-center justify-center transition-all"
                                    style="background:rgba(255,0,60,0.08);border:1px solid rgba(255,0,60,0.2);color:#ff525c;">
                                <i class="pi pi-heart-fill" style="font-size:13px;" />
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
