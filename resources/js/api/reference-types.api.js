import http from './http';

export function fetchReferenceTypes(page = 1) {
    return http.get('/reference-types', {
        params: { page },
    });
}

export function createReferenceType(payload) {
    return http.post('/reference-types', payload);
}

export function updateReferenceType(id, payload) {
    return http.put(`/reference-types/${id}`, payload);
}

export function deleteReferenceType(id) {
    return http.delete(`/reference-types/${id}`);
}

