import http from './http';

export function fetchCustomers(page = 1, params = {}) {
    return http.get('/customers', {
        params: { page, ...params },
    });
}

export function createCustomer(payload) {
    return http.post('/customers', payload);
}

export function updateCustomer(id, payload) {
    return http.put(`/customers/${id}`, payload);
}

export function deleteCustomer(id) {
    return http.delete(`/customers/${id}`);
}

