import http from './http';

export async function fetchDashboardStats(days = 30, end = null) {
    const params = { days };
    if (end) params.end = end;
    const { data } = await http.get('/dashboard/stats', { params });
    return data.data;
}

export async function fetchDashboardTrends(days = 30) {
    const { data } = await http.get('/dashboard/trends', { params: { days } });
    return data.data;
}

export async function fetchTopSellingVariants(days = 30, limit = 10) {
    const { data } = await http.get('/dashboard/top-selling-variants', { params: { days, limit } });
    return data.data;
}
