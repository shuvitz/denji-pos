import http from './http';

export async function fetchKitchenOrders() {
    const { data } = await http.get('/kitchen/orders');
    return data.data;
}

export async function markOrderReady(orderId) {
    const { data } = await http.post(`/kitchen/orders/${orderId}/ready`);
    return data.data;
}

export async function fetchReadyOrders() {
    const { data } = await http.get('/kitchen/orders/ready');
    return data.data;
}
