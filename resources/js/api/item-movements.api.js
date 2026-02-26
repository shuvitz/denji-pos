import http from './http';

export function fetchItemMovements(page = 1) {
    return http.get('/item-movements', {
        params: { page },
    });
}

export function createItemMovement(payload) {
    return http.post('/item-movements', payload);
}

export function updateItemMovement(id, payload) {
    return http.put(`/item-movements/${id}`, payload);
}

export function deleteItemMovement(id) {
    return http.delete(`/item-movements/${id}`);
}

