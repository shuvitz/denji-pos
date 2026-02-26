import http from './http';

export function fetchItems(page = 1) {
    return http.get('/items', {
        params: { page },
    });
}

export function createItem(payload) {
    return http.post('/items', payload);
}

export function updateItem(id, payload) {
    return http.put(`/items/${id}`, payload);
}

export function deleteItem(id) {
    return http.delete(`/items/${id}`);
}

