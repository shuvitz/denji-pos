<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ItemVariantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = ItemVariant::query()->with('item');

        if ($request->filled('item_id')) {
            $variants = $query
                ->where('item_id', $request->integer('item_id'))
                ->orderBy('name')
                ->orderBy('id')
                ->get();

            return response()->json([
                'data' => $variants,
                'message' => 'ok',
            ]);
        }

        $variants = $query
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'data' => $variants,
            'message' => 'ok',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:item_variants,sku'],
            'size' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $variant = ItemVariant::create($validated);
        $variant->load('item');

        return response()->json([
            'data' => $variant,
            'message' => 'ok',
        ], 201);
    }

    public function update(Request $request, ItemVariant $itemVariant): JsonResponse
    {
        $validated = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:item_variants,sku,' . $itemVariant->id],
            'size' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:100'],
            'stock' => ['required', 'integer', 'min:0'],
            'minimum_stock' => ['required', 'integer', 'min:0'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        $itemVariant->update($validated);
        $itemVariant->load('item');

        return response()->json([
            'data' => $itemVariant,
            'message' => 'ok',
        ]);
    }

    public function destroy(ItemVariant $itemVariant): JsonResponse
    {
        $itemVariant->delete();

        return response()->json([
            'data' => null,
            'message' => 'ok',
        ]);
    }
}
