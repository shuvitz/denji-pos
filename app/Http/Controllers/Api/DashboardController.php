<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemMovement;
use App\Models\ItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        $totals = [];

        // Inventory totals
        $totals['total_items'] = Item::query()->count();
        $totals['total_variants'] = ItemVariant::query()->count();
        $totals['total_stock'] = (int) ItemVariant::query()->sum('stock');
        $totals['low_stock_count'] = (int) ItemVariant::query()
            ->whereColumn('stock', '<=', 'minimum_stock')
            ->count();

        // Stock valuation
        $stockValuation = ItemVariant::query()
            ->selectRaw('COALESCE(SUM(stock * purchase_price), 0) as stock_value_cost')
            ->selectRaw('COALESCE(SUM(stock * selling_price), 0) as stock_value_sell')
            ->first();

        $totals['stock_value_cost'] = (float) $stockValuation->stock_value_cost;
        $totals['stock_value_sell'] = (float) $stockValuation->stock_value_sell;

        // Sales & margin based on movements of type "out"
        $sales = DB::table('item_movements as m')
            ->join('item_variants as v', 'v.id', '=', 'm.variant_id')
            ->where('m.type', '=', 'out')
            ->selectRaw('COALESCE(SUM(m.qty), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(m.qty * v.selling_price), 0) as total_sales_amount')
            ->selectRaw('COALESCE(SUM(m.qty * v.purchase_price), 0) as total_cost_amount')
            ->first();

        $totals['total_sales_qty'] = (int) $sales->total_qty;
        $totals['total_sales_amount'] = (float) $sales->total_sales_amount;
        $totals['total_cost_amount'] = (float) $sales->total_cost_amount;
        $totals['total_margin_amount'] = (float) ($sales->total_sales_amount - $sales->total_cost_amount);

        return response()->json([
            'data' => $totals,
            'message' => 'ok',
        ]);
    }

    public function trends(): JsonResponse
    {
        $days = (int) request()->integer('days', 30);
        if ($days < 1) {
            $days = 30;
        }

        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $rows = DB::table('item_movements as m')
            ->join('item_variants as v', 'v.id', '=', 'm.variant_id')
            ->where('m.movement_at', '>=', $start)
            ->selectRaw('DATE(m.movement_at) as d')
            ->selectRaw('COALESCE(SUM(CASE WHEN m.type = "out" THEN m.qty * v.selling_price ELSE 0 END), 0) as sales')
            ->selectRaw('COALESCE(SUM(CASE WHEN m.type = "in" THEN m.qty * v.purchase_price ELSE 0 END), 0) as purchase')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $series = [];
        $date = $start->copy();
        $end = Carbon::now()->startOfDay();
        while ($date->lte($end)) {
            $key = $date->toDateString();
            $row = $rows->get($key);
            $series[] = [
                'date' => $key,
                'sales' => (float) ($row->sales ?? 0),
                'purchase' => (float) ($row->purchase ?? 0),
            ];
            $date->addDay();
        }

        return response()->json([
            'data' => $series,
            'message' => 'ok',
        ]);
    }

    public function topSellingVariants(): JsonResponse
    {
        $days = (int) request()->integer('days', 30);
        if ($days < 1) {
            $days = 30;
        }

        $limit = (int) request()->integer('limit', 10);
        if ($limit < 1) {
            $limit = 10;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        $start = Carbon::now()->subDays($days - 1)->startOfDay();

        $rows = DB::table('item_movements as m')
            ->join('item_variants as v', 'v.id', '=', 'm.variant_id')
            ->join('items as i', 'i.id', '=', 'v.item_id')
            ->where('m.type', '=', 'out')
            ->where('m.movement_at', '>=', $start)
            ->selectRaw('v.id as variant_id')
            ->selectRaw('i.name as item_name')
            ->selectRaw('v.name as variant_name')
            ->selectRaw('v.sku as sku')
            ->selectRaw('COALESCE(SUM(m.qty), 0) as qty')
            ->selectRaw('COALESCE(SUM(m.qty * v.selling_price), 0) as amount')
            ->groupBy('v.id', 'i.name', 'v.name', 'v.sku')
            ->orderByDesc('qty')
            ->limit($limit)
            ->get();

        $data = $rows->map(function ($row) {
            $label = $row->item_name;
            if (! empty($row->variant_name)) {
                $label .= ' - '.$row->variant_name;
            } elseif (! empty($row->sku)) {
                $label .= ' - '.$row->sku;
            }

            return [
                'variant_id' => (int) $row->variant_id,
                'label' => $label,
                'qty' => (int) $row->qty,
                'amount' => (float) $row->amount,
            ];
        })->values();

        return response()->json([
            'data' => $data,
            'message' => 'ok',
        ]);
    }
}
