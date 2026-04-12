<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyCost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DailyCostController extends Controller
{
    /**
     * Get or store a daily cost entry.
     * GET  → returns entry for given date
     * POST → upserts entry for given date
     */
    public function show(Request $request): JsonResponse
    {
        $dateParam = $request->query('date');
        $date = null;
        if (is_string($dateParam) && $dateParam !== '') {
            try {
                $date = Carbon::createFromFormat('Y-m-d', $dateParam);
            } catch (\Throwable) {
                $date = null;
            }
        }
        $day = ($date ?? Carbon::now())->toDateString();

        $entry = DailyCost::where('date', $day)->first();

        return response()->json([
            'data' => $entry,
            'message' => 'ok',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $entry = DailyCost::updateOrCreate(
            ['date' => $validated['date']],
            [
                'amount' => $validated['amount'],
                'note' => $validated['note'] ?? null,
                'created_by' => $request->user()->id,
            ]
        );

        return response()->json([
            'data' => $entry,
            'message' => 'ok',
        ]);
    }
}
