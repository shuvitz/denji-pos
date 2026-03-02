import http from './http';

export async function fetchDashboardStats() {
    const { data } = await http.get('/dashboard/stats');
    return data.data;
}

