import http from './http';

export function fetchItemVariants(page = 1) {
    return http.get('/item-variants', {
        params: { page },
    });
}

export function fetchItemVariantsByItem(itemId) {
    return http.get('/item-variants', {
        params: { item_id: itemId },
    });
}

export function createItemVariant(payload) {
    return http.post('/item-variants', payload);
}

export function updateItemVariant(id, payload) {
    return http.put(`/item-variants/${id}`, payload);
}

export function deleteItemVariant(id) {
    return http.delete(`/item-variants/${id}`);
}
