/**
 * Interceptor global do axios para tratar erros HTTP de infraestrutura.
 * Aplicado no default axios — cobre todas as páginas que usam axios diretamente.
 * As stores (customer, admin) tratam 401 por conta própria nos seus interceptors.
 */
import axios from 'axios';
import { useToast } from '../composables/useToast.js';

const errorMessages = {
    403: 'Você não tem permissão para realizar esta ação.',
    404: 'Recurso não encontrado.',
    500: 'Erro interno do servidor. Tente novamente em instantes.',
    503: 'Serviço temporariamente indisponível. Tente novamente em breve.',
};

export function setupHttpInterceptors(router) {
    axios.interceptors.response.use(
        r => r,
        err => {
            const status = err.response?.status;
            const { add } = useToast();

            // 401 não tratado aqui — cada store cuida do seu próprio fluxo
            if (status === 403) {
                add({ severity: 'error', summary: 'Acesso negado', detail: errorMessages[403], life: 5000 });
            } else if (status === 404) {
                // 404 de API: só toast, não redireciona (recurso não encontrado, não página)
                add({ severity: 'warning', summary: 'Não encontrado', detail: errorMessages[404], life: 4000 });
            } else if (status === 500) {
                add({ severity: 'error', summary: 'Erro no servidor', detail: errorMessages[500], life: 6000 });
            } else if (status === 503) {
                router.push({ name: 'error', params: { code: '503' } });
            }

            return Promise.reject(err);
        }
    );
}
