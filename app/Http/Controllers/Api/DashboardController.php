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

        $days = (int) request()->integer('days', 30);
        if ($days < 1) {
            $days = 30;
        }
        if ($days > 3650) {
            $days = 3650;
        }

        $endParam = request()->query('end');
        $endDate = null;
        if (is_string($endParam) && $endParam !== '') {
            try {
                $endDate = Carbon::createFromFormat('Y-m-d', $endParam);
            } catch (\Throwable) {
                try {
                    $endDate = Carbon::parse($endParam);
                } catch (\Throwable) {
                    $endDate = null;
                }
            }
        }
        $endAt = ($endDate ?? Carbon::now())->endOfDay();
        $startAt = $endAt->copy()->subDays($days - 1)->startOfDay();

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

        // Sales based on item_movements (since order_items are not used)
        $sales = DB::table('item_movements as im')
            ->join('item_variants as v', 'v.id', '=', 'im.variant_id')
            ->where('im.type', '=', 'out')
            ->whereBetween('im.movement_at', [$startAt, $endAt])
            ->selectRaw('COALESCE(SUM(im.qty), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(im.qty * v.selling_price), 0) as total_sales_amount')
            ->first();

        // Cost based on manual daily_costs table
        $totalCapital = 0;
        if (\Illuminate\Support\Facades\Schema::hasTable('daily_costs')) {
            $totalCapital = (float) DB::table('daily_costs')
                ->whereBetween('date', [$startAt->toDateString(), $endAt->toDateString()])
                ->sum('amount');
        }

        $totals['total_sales_qty'] = (int) $sales->total_qty;
        $totals['total_sales_amount'] = (float) $sales->total_sales_amount;
        $totals['total_capital_amount'] = (float) $totalCapital;
        $totals['total_margin_amount'] = (float) ($sales->total_sales_amount - $totalCapital);

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
        if ($days > 3650) {
            $days = 3650;
        }

        $endParam = request()->query('end');
        $endDate = null;
        if (is_string($endParam) && $endParam !== '') {
            try {
                $endDate = Carbon::createFromFormat('Y-m-d', $endParam);
            } catch (\Throwable) {
                try {
                    $endDate = Carbon::parse($endParam);
                } catch (\Throwable) {
                    $endDate = null;
                }
            }
        }
        $end = ($endDate ?? Carbon::now())->startOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();
        $endAt = $end->copy()->endOfDay();

        $salesRows = DB::table('item_movements as im')
            ->join('item_variants as v', 'v.id', '=', 'im.variant_id')
            ->where('im.type', '=', 'out')
            ->whereBetween('im.movement_at', [$start, $endAt])
            ->selectRaw('DATE(im.movement_at) as d')
            ->selectRaw('COALESCE(SUM(im.qty), 0) as sales_qty')
            ->selectRaw('COALESCE(SUM(im.qty * v.selling_price), 0) as sales')
            ->groupBy('d')
            ->get()
            ->keyBy('d');

        $purchaseRows = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('daily_costs')) {
            $purchaseRows = DB::table('daily_costs')
                ->whereBetween('date', [$start->toDateString(), $endAt->toDateString()])
                ->selectRaw('date as d')
                ->selectRaw('COALESCE(SUM(amount), 0) as purchase')
                ->groupBy('d')
                ->get()
                ->keyBy('d');
        }

        $series = [];
        $date = $start->copy();
        while ($date->lte($end)) {
            $key = $date->toDateString();
            $sRow = $salesRows->get($key);
            $pRow = $purchaseRows->get($key);
            $series[] = [
                'date' => $key,
                'sales' => (float) ($sRow->sales ?? 0),
                'sales_qty' => (int) ($sRow->sales_qty ?? 0),
                'purchase' => (float) ($pRow->purchase ?? 0),
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
        if ($days > 3650) {
            $days = 3650;
        }

        $limit = (int) request()->integer('limit', 10);
        if ($limit < 1) {
            $limit = 10;
        }
        if ($limit > 50) {
            $limit = 50;
        }

        $endParam = request()->query('end');
        $endDate = null;
        if (is_string($endParam) && $endParam !== '') {
            try {
                $endDate = Carbon::createFromFormat('Y-m-d', $endParam);
            } catch (\Throwable) {
                try {
                    $endDate = Carbon::parse($endParam);
                } catch (\Throwable) {
                    $endDate = null;
                }
            }
        }
        $end = ($endDate ?? Carbon::now())->endOfDay();
        $start = $end->copy()->subDays($days - 1)->startOfDay();

        $rows = DB::table('item_movements as im')
            ->join('item_variants as v', 'v.id', '=', 'im.variant_id')
            ->join('items as i', 'i.id', '=', 'v.item_id')
            ->where('im.type', '=', 'out')
            ->whereBetween('im.movement_at', [$start, $end])
            ->selectRaw('v.id as variant_id')
            ->selectRaw('i.name as item_name')
            ->selectRaw('v.name as variant_name')
            ->selectRaw('v.sku as sku')
            ->selectRaw('COALESCE(SUM(im.qty), 0) as qty')
            ->selectRaw('COALESCE(SUM(im.qty * v.selling_price), 0) as amount')
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
    /**
     * Daily operational report.
     *
     * Revenue  → SUM(orders.total) for paid orders on the given date
     * Sales    → order_items grouped by item/variant for paid orders on the given date
     * Cost     → daily_costs.amount (manual) if exists, ELSE item_movements fallback
     * Profit   → revenue - cost
     */
    public function dailyReport(): JsonResponse
    {
        $dateParam = request()->query('date');
        $date = null;
        if (is_string($dateParam) && $dateParam !== '') {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $dateParam);
            } catch (\Throwable) {
                $date = null;
            }
        }
        $day = ($date ?? Carbon::now())->startOfDay();
        $targetDate = $day->toDateString();

        // 1. Revenue (Omzet) from variant selling price for item movements
        $revenue = (float) DB::table('item_movements as im')
            ->join('item_variants as v', 'v.id', '=', 'im.variant_id')
            ->where('im.type', '=', 'out')
            ->whereDate('im.movement_at', $targetDate)
            ->sum(DB::raw('im.qty * v.selling_price'));

        // 2. Sales breakdown by item/variant from item movements
        $salesBreakdown = DB::table('item_movements as im')
            ->join('item_variants as v', 'v.id', '=', 'im.variant_id')
            ->join('items as i', 'i.id', '=', 'im.item_id')
            ->where('im.type', '=', 'out')
            ->whereDate('im.movement_at', $targetDate)
            ->selectRaw('i.name as item_name')
            ->selectRaw('v.name as variant_name')
            ->selectRaw('COALESCE(SUM(im.qty), 0) as total_qty')
            ->selectRaw('COALESCE(SUM(im.qty * v.selling_price), 0) as total_amount')
            ->groupBy('i.name', 'v.name')
            ->orderByDesc('total_qty')
            ->get()
            ->map(function ($row) {
                $label = $row->item_name;
                if (!empty($row->variant_name)) {
                    $label .= ' - ' . $row->variant_name;
                }
                return [
                    'label' => $label,
                    'qty' => (int) $row->total_qty,
                    'amount' => (float) $row->total_amount,
                ];
            })
            ->values();

        // 3. Cost — strictly user manual input
        $dailyCost = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('daily_costs')) {
            $dailyCost = DB::table('daily_costs')
                ->where('date', $targetDate)
                ->first();
        }

        if ($dailyCost) {
            $cost = (float) $dailyCost->amount;
            $costSource = 'manual';
            $dailyCostData = [
                'id' => (int) $dailyCost->id,
                'amount' => (float) $dailyCost->amount,
                'note' => $dailyCost->note,
            ];
        } else {
            $cost = 0;
            $costSource = 'manual';
            $dailyCostData = null;
        }

        // 4. Profit
        $profit = $revenue - $cost;

        // 5. Transaction count (based on unique movements)
        $orderCount = (int) DB::table('item_movements')
            ->where('type', 'out')
            ->whereDate('movement_at', $targetDate)
            ->count();

        return response()->json([
            'data' => [
                'date' => $targetDate,
                'revenue' => $revenue,
                'cost' => $cost,
                'cost_source' => $costSource,
                'daily_cost' => $dailyCostData,
                'profit' => $profit,
                'order_count' => $orderCount,
                'sales_breakdown' => $salesBreakdown,
            ],
            'message' => 'ok',
        ]);
    }
}
