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

export function useCustomer() {
    const isLoggedIn = computed(() => !!token.value);

    function login(newToken, data) {
        token.value    = newToken;
        customer.value = data;
        persist();
    }

    function logout() {
        if (token.value) {
            api().post('/customer/auth/logout').catch(() => {});
        }
        token.value    = null;
        customer.value = null;
        persist();
    }

    async function fetchMe() {
        if (!token.value) return;
        try {
            const res  = await api().get('/customer/auth/me');
            customer.value = res.data;
            persist();
        } catch {
            logout();
        }
    }

    function api() {
        return axios.create({
            baseURL: '/api',
            headers: token.value ? { Authorization: `Bearer ${token.value}` } : {},
        });
    }

    return { token, customer, isLoggedIn, login, logout, fetchMe, api };
}
