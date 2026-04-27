import axios from 'axios';
import { useAuthStore } from '../stores/auth.js';

export function useAdminApi() {
    const { token, logout } = useAuthStore();

    const api = axios.create({ baseURL: '/api' });

    api.interceptors.request.use(cfg => {
        if (token.value) cfg.headers.Authorization = `Bearer ${token.value}`;
        return cfg;
    });

    api.interceptors.response.use(
        r => r,
        err => {
            if (err.response?.status === 401) logout();
            return Promise.reject(err);
        }
    );

    return api;
}
