import { ref } from 'vue';
import axios from 'axios';

const customKitEnabled = ref(true);
let loaded = false;

export function useSettingsStore() {
    async function load(force = false) {
        if (loaded && !force) return;
        try {
            const { data } = await axios.get('/api/settings');
            customKitEnabled.value = data.custom_kit_enabled !== '0';
        } catch {}
        loaded = true;
    }

    function set(key, value) {
        if (key === 'custom_kit_enabled') customKitEnabled.value = value !== '0' && value !== false;
    }

    return { customKitEnabled, load, set };
}
