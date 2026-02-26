<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReferenceType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferenceTypeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $types = ReferenceType::query()
            ->orderBy('name')
            ->paginate(10);

        return response()->json([
            'data' => $types,
            'message' => 'ok',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:reference_types,name'],
        ]);

        $type = ReferenceType::create($validated);

        return response()->json([
            'data' => $type,
            'message' => 'ok',
        ], 201);
    }

    public function update(Request $request, ReferenceType $referenceType): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:reference_types,name,' . $referenceType->id],
        ]);

        $referenceType->update($validated);

        return response()->json([
            'data' => $referenceType,
            'message' => 'ok',
        ]);
    }

    public function destroy(ReferenceType $referenceType): JsonResponse
    {
        $referenceType->delete();

        return response()->json([
            'data' => null,
            'message' => 'ok',
        ]);
    }
}

