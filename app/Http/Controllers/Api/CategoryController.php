<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $categories = Category::query()
            ->orderByDesc('id')
            ->paginate(10);

        return response()->json([
            'data' => $categories,
            'message' => 'ok',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:categories,code'],
            'description' => ['nullable', 'string'],
        ]);

        $category = Category::create([
            ...$validated,
            'created_by' => $request->user()->id,
        ]);

        return response()->json([
            'data' => $category,
            'message' => 'ok',
        ], 201);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:categories,code,' . $category->id],
            'description' => ['nullable', 'string'],
        ]);

        $category->update($validated);

        return response()->json([
            'data' => $category,
            'message' => 'ok',
        ]);
    }

    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json([
            'data' => null,
            'message' => 'ok',
        ]);
    }
}

