<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItemMovement;
use App\Models\ItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ItemMovementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $movements = ItemMovement::query()
            ->with(['item', 'variant', 'referenceType'])
            ->orderByDesc('movement_at')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'data' => $movements,
            'message' => 'ok',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:item_variants,id'],
            'type' => ['required', 'in:in,out,adjustment'],
            'qty' => ['required', 'integer', 'min:1'],
            'cost_per_unit' => ['nullable', 'numeric', 'min:0'],
            'reference_id' => ['nullable', 'integer', 'exists:reference_types,id'],
            'note' => ['nullable', 'string'],
            'movement_at' => ['nullable', 'date'],
        ]);

        $movement = null;

        DB::transaction(function () use ($request, $validated, &$movement) {
            $variant = ItemVariant::lockForUpdate()->findOrFail($validated['variant_id']);

            $delta = match ($validated['type']) {
                'in' => $validated['qty'],
                'out' => -$validated['qty'],
                'adjustment' => $validated['qty'], // Default adjustment to positive for now
                default => 0,
            };

            $newStock = $variant->stock + $delta;

            if ($newStock < 0) {
                abort(422, 'Insufficient stock for this movement.');
            }

            $variant->stock = $newStock;
            $variant->save();

            $movement = ItemMovement::create([
                'item_id' => $variant->item_id,
                'variant_id' => $variant->id,
                'type' => $validated['type'],
                'qty' => $validated['qty'],
                'cost_per_unit' => $validated['cost_per_unit'] ?? null,
                'reference_id' => $validated['reference_id'] ?? null,
                'note' => $validated['note'] ?? null,
                'movement_at' => $validated['movement_at'] ?? now(),
                'created_by' => $request->user()->id,
            ]);
        });

        $movement->load(['item', 'variant', 'referenceType']);

        return response()->json([
            'data' => $movement,
            'message' => 'ok',
        ], 201);
    }

    public function update(Request $request, ItemMovement $movement): JsonResponse
    {
        $validated = $request->validate([
            'reference_id' => ['nullable', 'integer', 'exists:reference_types,id'],
            'note' => ['nullable', 'string'],
            'movement_at' => ['nullable', 'date'],
        ]);

        $movement->update($validated);
        $movement->load(['item', 'variant', 'referenceType']);

        return response()->json([
            'data' => $movement,
            'message' => 'ok',
        ]);
    }

    public function destroy(ItemMovement $movement): JsonResponse
    {
        DB::transaction(function () use ($movement) {
            $variant = ItemVariant::lockForUpdate()->findOrFail($movement->variant_id);

            $delta = $movement->type === 'in'
                ? -$movement->qty
                : ($movement->type === 'out' ? $movement->qty : -$movement->qty);

            $newStock = $variant->stock + $delta;

            if ($newStock < 0) {
                abort(422, 'Cannot delete movement because it would make stock negative.');
            }

            $variant->stock = $newStock;
            $variant->save();

            $movement->delete();
        });

        return response()->json([
            'data' => null,
            'message' => 'ok',
        ]);
    }
}
