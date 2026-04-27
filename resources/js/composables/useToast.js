import { ref } from 'vue';

const toasts = ref([]);
let nextId = 0;

export function useToast() {
    function add({ severity = 'info', summary = '', detail = '', life = 4000 }) {
        const id = ++nextId;
        toasts.value.push({ id, severity, summary, detail });
        if (life > 0) setTimeout(() => remove(id), life);
    }

    function remove(id) {
        const idx = toasts.value.findIndex(t => t.id === id);
        if (idx !== -1) toasts.value.splice(idx, 1);
    }

    // Atalhos
    const success = (summary, detail = '', life = 3000) => add({ severity: 'success', summary, detail, life });
    const error   = (summary, detail = '', life = 5000) => add({ severity: 'error',   summary, detail, life });
    const warning = (summary, detail = '', life = 4000) => add({ severity: 'warning', summary, detail, life });
    const info    = (summary, detail = '', life = 3000) => add({ severity: 'info',    summary, detail, life });

    return { toasts, add, remove, success, error, warning, info };
}
