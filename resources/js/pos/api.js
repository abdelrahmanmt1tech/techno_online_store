import axios from 'axios';

const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

export function createApi(baseUrl) {
    const client = axios.create({
        baseURL: baseUrl,
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        withCredentials: true,
    });

    client.interceptors.response.use(
        (r) => r,
        (error) => {
            const payload = error.response?.data;
            const message = payload?.message
                || (payload?.errors ? Object.values(payload.errors).flat()[0] : null)
                || error.message;
            return Promise.reject(new Error(message));
        },
    );

    return {
        bootstrap: (registerId) => client.get('/bootstrap', { params: { register_id: registerId || undefined } }).then((r) => r.data.data),
        products: (params) => client.get('/products', { params }).then((r) => r.data),
        barcode: (code) => client.get('/barcode', { params: { code } }).then((r) => r.data.data),
        customers: (q) => client.get('/customers', { params: { q } }).then((r) => r.data.data),
        createCustomer: (payload) => client.post('/customers', payload).then((r) => r.data.data),
        openSession: (payload) => client.post('/session/open', payload).then((r) => r.data.data),
        sessionStatus: (registerId) => client.get('/session/status', { params: { register_id: registerId } }).then((r) => r.data.data),
        closeSession: (payload) => client.post('/session/close', payload).then((r) => r.data.data),
        shiftSummary: (registerId) => client.get('/session/summary', { params: { register_id: registerId } }).then((r) => r.data.data),
        cashIn: (payload) => client.post('/cash-in', payload).then((r) => r.data.data),
        cashOut: (payload) => client.post('/cash-out', payload).then((r) => r.data.data),
        checkout: (payload) => client.post('/checkout', payload).then((r) => {
            const body = r.data;
            return body?.data ?? body;
        }),
        suspend: (payload) => client.post('/suspend', payload).then((r) => r.data.data),
        suspended: (registerId) => client.get('/suspended', { params: { register_id: registerId } }).then((r) => r.data.data),
        resume: (saleId) => client.post(`/suspended/${saleId}/resume`).then((r) => r.data.data),
        cancelSuspended: (saleId, reason) => client.post(`/suspended/${saleId}/cancel`, { reason }).then((r) => r.data.data),
    };
}
