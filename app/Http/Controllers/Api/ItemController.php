<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ItemController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $items = Item::query()
            ->with('category')
            ->withSum('variants', 'stock')
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'data' => $items,
            'message' => 'ok',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $item = Item::create($validated + [
            'created_by' => $request->user()->id,
        ]);

        $item->load('category');

        return response()->json([
            'data' => $item,
            'message' => 'ok',
        ], 201);
    }

    public function update(Request $request, Item $item): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $item->update($validated);

        $item->load('category');

        return response()->json([
            'data' => $item,
            'message' => 'ok',
        ]);
    }

    public function destroy(Item $item): JsonResponse
    {
        $item->delete();

        return response()->json([
            'data' => null,
            'message' => 'ok',
        ]);
    }
}
