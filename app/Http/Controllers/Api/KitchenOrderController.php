<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;

class KitchenOrderController extends Controller
{
    /**
     * Get all orders currently in the kitchen (status = processing).
     */
    public function index(): JsonResponse
    {
        $orders = Order::query()
            ->whereIn('status', ['processing'])
            ->with(['items.variant.item', 'table'])
            ->orderBy('created_at', 'asc') // FIFO: oldest order first
            ->get();

        return response()->json([
            'data' => $orders,
            'message' => 'ok',
        ]);
    }

    /**
     * Mark an order as ready (kitchen finished cooking).
     */
    public function markReady(Order $order): JsonResponse
    {
        if ($order->status !== 'processing') {
            return response()->json([
                'message' => 'Only processing orders can be marked as ready.',
            ], 422);
        }

        $order->update(['status' => 'ready']);

        return response()->json([
            'data' => $order->load(['items.variant.item', 'table']),
            'message' => 'Order marked as ready.',
        ]);
    }

    /**
     * Get all orders that are ready (for cashier/admin to see).
     */
    public function ready(): JsonResponse
    {
        $orders = Order::query()
            ->where('status', 'ready')
            ->with(['items.variant.item', 'table'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'data' => $orders,
            'message' => 'ok',
        ]);
    }
}
