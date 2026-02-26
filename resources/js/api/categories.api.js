import http from './http';

export function fetchCategories(page = 1) {
    return http.get('/categories', {
        params: { page },
    });
}

export function createCategory(payload) {
    return http.post('/categories', payload);
}

export function updateCategory(id, payload) {
    return http.put(`/categories/${id}`, payload);
}

export function deleteCategory(id) {
    return http.delete(`/categories/${id}`);
}

