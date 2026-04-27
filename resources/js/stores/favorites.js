import { ref, computed } from 'vue';
import { useCustomer } from './customer.js';

const favoriteKeys = ref(new Set());
let loaded = false;

export function useFavorites() {
    const { api, isLoggedIn } = useCustomer();

    const isFavorited = (type, id) => favoriteKeys.value.has(`${type}:${id}`);

    async function load() {
        if (!isLoggedIn.value || loaded) return;
        try {
            const res = await api().get('/customer/favorites');
            const keys = res.data.map(f => `${f.type.toLowerCase()}:${f.favoritable_id}`);
            favoriteKeys.value = new Set(keys);
            loaded = true;
        } catch {}
    }

    async function toggle(type, id) {
        if (!isLoggedIn.value) return false;
        const key = `${type}:${id}`;
        const wasOn = favoriteKeys.value.has(key);
        // Optimistic update
        const next = new Set(favoriteKeys.value);
        wasOn ? next.delete(key) : next.add(key);
        favoriteKeys.value = next;
        try {
            await api().post('/customer/favorites/toggle', { type, id });
        } catch {
            // Revert on error
            const reverted = new Set(favoriteKeys.value);
            wasOn ? reverted.add(key) : reverted.delete(key);
            favoriteKeys.value = reverted;
        }
        return !wasOn;
    }

    function reset() {
        favoriteKeys.value = new Set();
        loaded = false;
    }

    return { isFavorited, load, toggle, reset };
}
