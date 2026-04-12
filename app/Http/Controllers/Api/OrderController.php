<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemVariant;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * List orders with optional status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::query()
            ->with(['items.variant.item', 'table', 'customer', 'creator'])
            ->orderByDesc('created_at');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        return response()->json([
            'data' => $query->paginate(20),
            'message' => 'ok',
        ]);
    }

    /**
     * Create a new order → status goes directly to "processing" (kitchen flow).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'order_type' => ['required', 'in:dine-in,takeaway'],
            'table_id' => ['nullable', 'integer', 'exists:tables,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.variant_id' => ['required', 'integer', 'exists:item_variants,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.note' => ['nullable', 'string'],
        ]);

        $order = null;

        DB::transaction(function () use ($request, $validated, &$order) {
            // Generate unique order number: ORD-YYYYMMDD-XXXXX
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));

            // Calculate subtotal from variant selling prices
            $subtotal = 0;
            $itemRows = [];

            foreach ($validated['items'] as $row) {
                $variant = ItemVariant::findOrFail($row['variant_id']);
                $lineTotal = $variant->selling_price * $row['qty'];
                $subtotal += $lineTotal;

                $itemRows[] = [
                    'item_id' => $variant->item_id,
                    'variant_id' => $variant->id,
                    'qty' => $row['qty'],
                    'price' => $variant->selling_price,
                    'note' => $row['note'] ?? null,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;
            $total = $subtotal - $discount + $tax;

            $order = Order::create([
                'order_number' => $orderNumber,
                'order_type' => $validated['order_type'],
                'table_id' => $validated['table_id'] ?? null,
                'status' => 'processing', // Goes directly to kitchen
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'payment_method' => $validated['payment_method'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            foreach ($itemRows as $itemRow) {
                $order->items()->create($itemRow);
            }

            // Mark table as occupied if dine-in
            if ($order->table_id) {
                \App\Models\Table::where('id', $order->table_id)
                    ->update(['status' => 'occupied']);
            }
        });

        $order->load(['items.variant.item', 'table', 'customer']);

        return response()->json([
            'data' => $order,
            'message' => 'Order created and sent to kitchen.',
        ], 201);
    }

    /**
     * Show a single order.
     */
    public function show(Order $order): JsonResponse
    {
        $order->load(['items.variant.item', 'table', 'customer', 'creator']);

        return response()->json([
            'data' => $order,
            'message' => 'ok',
        ]);
    }

    /**
     * Mark order as paid → deduct stock via item movements.
     */
    public function pay(Request $request, Order $order): JsonResponse
    {
        if ($order->status === 'paid') {
            return response()->json(['message' => 'Order is already paid.'], 422);
        }

        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'Cannot pay a cancelled order.'], 422);
        }

        $validated = $request->validate([
            'payment_method' => ['required', 'string'],
        ]);

        DB::transaction(function () use ($order, $validated, $request) {
            $order->update([
                'status' => 'paid',
                'payment_method' => $validated['payment_method'],
            ]);

            // Auto-deduct stock for each order item
            foreach ($order->items as $orderItem) {
                if (!$orderItem->variant_id) continue;

                $variant = ItemVariant::lockForUpdate()->findOrFail($orderItem->variant_id);

                $newStock = $variant->stock - $orderItem->qty;
                if ($newStock < 0) $newStock = 0;

                $variant->stock = $newStock;
                $variant->save();

                // Record the stock movement automatically, linked to order
                \App\Models\ItemMovement::create([
                    'item_id' => $orderItem->item_id,
                    'variant_id' => $orderItem->variant_id,
                    'type' => 'out',
                    'qty' => $orderItem->qty,
                    'cost_per_unit' => $orderItem->price,
                    'order_id' => $order->id,
                    'note' => 'Auto-deducted from order ' . $order->order_number,
                    'movement_at' => now(),
                    'created_by' => $request->user()->id,
                ]);
            }

            // Release table if dine-in
            if ($order->table_id) {
                \App\Models\Table::where('id', $order->table_id)
                    ->update(['status' => 'available']);
            }
        });

        $order->load(['items.variant.item', 'table', 'customer']);

        return response()->json([
            'data' => $order,
            'message' => 'Order paid. Stock deducted.',
        ]);
    }

    /**
     * Cancel an order.
     * If order was already paid → create REVERSAL movements (stock back in).
     * If order was not yet paid → just cancel, no stock impact.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'Order is already cancelled.'], 422);
        }

        $wasPaid = $order->status === 'paid';

        DB::transaction(function () use ($order, $request, $wasPaid) {
            $order->update(['status' => 'cancelled']);

            // If the order was paid, we already deducted stock → create reversal movements
            if ($wasPaid) {
                foreach ($order->items as $orderItem) {
                    if (!$orderItem->variant_id) continue;

                    $variant = ItemVariant::lockForUpdate()->findOrFail($orderItem->variant_id);
                    $variant->stock += $orderItem->qty;
                    $variant->save();

                    // Reversal movement: stock goes back IN
                    \App\Models\ItemMovement::create([
                        'item_id' => $orderItem->item_id,
                        'variant_id' => $orderItem->variant_id,
                        'type' => 'in',
                        'qty' => $orderItem->qty,
                        'cost_per_unit' => $orderItem->price,
                        'order_id' => $order->id,
                        'note' => 'Reversal — cancelled order ' . $order->order_number,
                        'movement_at' => now(),
                        'created_by' => $request->user()->id,
                    ]);
                }
            }

            // Release table
            if ($order->table_id) {
                \App\Models\Table::where('id', $order->table_id)
                    ->update(['status' => 'available']);
            }
        });

        return response()->json([
            'data' => $order->fresh(['items.variant.item', 'table', 'customer']),
            'message' => $wasPaid
                ? 'Order cancelled. Stock reversed.'
                : 'Order cancelled.',
        ]);
    }
}
