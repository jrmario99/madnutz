<script setup>
import { computed } from 'vue';
import { useFavorites } from '../stores/favorites.js';
import { useCustomer } from '../stores/customer.js';

const props = defineProps({
    type:  { type: String, required: true },  // 'product' | 'kit'
    itemId: { type: Number, required: true },
    size:  { type: String, default: 'md' },   // 'sm' | 'md'
    dark:  { type: Boolean, default: false },
});

const { isLoggedIn } = useCustomer();
const { isFavorited, toggle } = useFavorites();

const active = computed(() => isFavorited(props.type, props.itemId));

async function handleClick(e) {
    e.preventDefault();
    e.stopPropagation();
    if (!isLoggedIn.value) return;
    await toggle(props.type, props.itemId);
}

const btnSize  = computed(() => props.size === 'sm' ? 'w-7 h-7' : 'w-9 h-9');
const iconSize = computed(() => props.size === 'sm' ? 'font-size:11px' : 'font-size:14px');
</script>

<template>
    <button v-if="isLoggedIn"
            @click="handleClick"
            :class="[btnSize, 'rounded-full flex items-center justify-center transition-all duration-200']"
            :style="active
                ? (dark
                    ? 'background:rgba(255,0,60,0.2);border:1px solid rgba(255,0,60,0.5);color:#ff525c'
                    : 'background:rgba(255,0,60,0.15);border:1px solid rgba(255,0,60,0.4);color:#FF003C')
                : (dark
                    ? 'background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:rgba(255,255,255,0.4)'
                    : 'background:rgba(0,0,0,0.35);border:1px solid rgba(255,255,255,0.2);color:rgba(255,255,255,0.7)')"
            :title="active ? 'Remover dos favoritos' : 'Adicionar aos favoritos'">
        <i :class="['pi', active ? 'pi-heart-fill' : 'pi-heart']" :style="iconSize" />
    </button>
</template>
