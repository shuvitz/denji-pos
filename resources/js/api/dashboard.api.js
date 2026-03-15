import http from './http';

export async function fetchDashboardStats() {
    const { data } = await http.get('/dashboard/stats');
    return data.data;
}

export async function fetchDashboardTrends(days = 30) {
    const { data } = await http.get('/dashboard/trends', { params: { days } });
    return data.data;
}
