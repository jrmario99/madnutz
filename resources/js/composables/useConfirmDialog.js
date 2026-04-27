import { ref } from 'vue';

const state = ref(null);
// { title, message, confirmLabel, cancelLabel, danger, resolve, reject }

export function useConfirmDialog() {
    // API promise-based
    function confirm({ title = 'Confirmar', message = '', confirmLabel = 'Confirmar', cancelLabel = 'Cancelar', danger = false } = {}) {
        return new Promise((resolve, reject) => {
            state.value = { title, message, confirmLabel, cancelLabel, danger, resolve, reject };
        });
    }

    // API compatível com PrimeVue (accept/reject callbacks)
    function require({ header = 'Confirmar', message = '', accept, reject: onReject, acceptClass = '' } = {}) {
        const danger = acceptClass.includes('danger');
        confirm({ title: header, message, danger })
            .then(() => accept?.())
            .catch(() => onReject?.());
    }

    function _accept() {
        state.value?.resolve();
        state.value = null;
    }

    function _cancel() {
        state.value?.reject();
        state.value = null;
    }

    return { state, confirm, require, _accept, _cancel };
}
