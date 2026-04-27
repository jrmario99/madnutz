import axios from 'axios';

const api = axios.create({ baseURL: '/api' });

export function useApi() {
    const getCategories = () => api.get('/categories').then(r => r.data);

    const getProducts = (params = {}) => api.get('/products', { params }).then(r => r.data);

    const getProduct = (slug) => api.get(`/products/${slug}`).then(r => r.data);

    return { getCategories, getProducts, getProduct };
}
