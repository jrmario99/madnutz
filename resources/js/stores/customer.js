import { ref, computed } from 'vue';
import axios from 'axios';

const token    = ref(localStorage.getItem('customer_token') ?? null);
const customer = ref(JSON.parse(localStorage.getItem('customer_data') ?? 'null'));

function persist() {
    if (token.value) {
        localStorage.setItem('customer_token', token.value);
        localStorage.setItem('customer_data', JSON.stringify(customer.value));
    } else {
        localStorage.removeItem('customer_token');
        localStorage.removeItem('customer_data');
    }
}

// Instância axios compartilhada com interceptor 401
const http = axios.create({ baseURL: '/api' });

http.interceptors.request.use(cfg => {
    if (token.value) cfg.headers.Authorization = `Bearer ${token.value}`;
    return cfg;
});

http.interceptors.response.use(
    r => r,
    err => {
        if (err.response?.status === 401) {
            token.value    = null;
            customer.value = null;
            persist();
        }
        return Promise.reject(err);
    }
);

export function useCustomer() {
    const isLoggedIn = computed(() => !!token.value);

    function login(newToken, data) {
        token.value    = newToken;
        customer.value = data;
        persist();
    }

    function logout() {
        if (token.value) {
            http.post('/customer/auth/logout').catch(() => {});
        }
        token.value    = null;
        customer.value = null;
        persist();
    }

    async function fetchMe() {
        if (!token.value) return;
        try {
            const res  = await http.get('/customer/auth/me');
            customer.value = res.data;
            persist();
        } catch {
            // interceptor já faz logout no 401
        }
    }

    function api() {
        return http;
    }

    return { token, customer, isLoggedIn, login, logout, fetchMe, api };
}
