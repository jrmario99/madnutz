import { ref, computed } from 'vue';
import axios from 'axios';

const token = ref(localStorage.getItem('admin_token') || null);
const user  = ref(JSON.parse(localStorage.getItem('admin_user') || 'null'));

export function useAuthStore() {
    const isAdmin = computed(() => !!token.value && user.value?.is_admin);

    const api = axios.create({ baseURL: '/api' });
    api.interceptors.request.use(cfg => {
        if (token.value) cfg.headers.Authorization = `Bearer ${token.value}`;
        return cfg;
    });

    async function login(email, password) {
        const res = await api.post('auth/login', { email, password });
        if (!res.data.user?.is_admin) throw new Error('Acesso negado.');
        token.value = res.data.token;
        user.value  = res.data.user;
        localStorage.setItem('admin_token', token.value);
        localStorage.setItem('admin_user', JSON.stringify(user.value));
    }

    async function logout() {
        try { await api.post('auth/logout'); } catch {}
        token.value = null;
        user.value  = null;
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_user');
    }

    return { token, user, isAdmin, login, logout };
}
