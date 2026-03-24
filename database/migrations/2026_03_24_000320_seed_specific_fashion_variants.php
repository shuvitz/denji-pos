<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('users')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('categories')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('items')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('item_variants')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('item_movements')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('reference_types')) {
            return;
        }

        $now = now();
        $userId = DB::table('users')->orderBy('id')->value('id');
        if (! $userId) {
            return;
        }

        $refSaleId = DB::table('reference_types')->where('name', 'sale')->value('id');
        if (! $refSaleId) {
            $refSaleId = DB::table('reference_types')->insertGetId([
                'name' => 'sale',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('categories')->upsert([
            [
                'name' => 'Fashion',
                'code' => 'FASHION',
                'description' => 'Fashion items',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], ['name', 'description', 'updated_at']);

        $categoryId = DB::table('categories')->where('code', 'FASHION')->value('id');
        if (! $categoryId) {
            return;
        }

        $wanted = [
            [
                'item_name' => 'One Set',
                'variant_name' => 'Tela Series',
                'sku' => 'SKU003',
                'purchase_price' => 70000,
                'selling_price' => 100000,
                'initial_stock' => 80,
            ],
            [
                'item_name' => 'Long Dress',
                'variant_name' => 'Cela Series',
                'sku' => 'SKU002',
                'purchase_price' => 65000,
                'selling_price' => 95000,
                'initial_stock' => 70,
            ],
        ];

        $variants = [];

        DB::transaction(function () use ($wanted, $categoryId, $userId, $now, &$variants) {
            foreach ($wanted as $w) {
                $existingVariant = DB::table('item_variants')->where('sku', $w['sku'])->first();
                if ($existingVariant) {
                    $variants[] = [
                        'id' => (int) $existingVariant->id,
                        'item_id' => (int) $existingVariant->item_id,
                        'purchase_price' => (float) ($existingVariant->purchase_price ?? $w['purchase_price']),
                        'selling_price' => (float) ($existingVariant->selling_price ?? $w['selling_price']),
                        'stock' => (int) ($existingVariant->stock ?? 0),
                    ];
                    continue;
                }

                $itemId = DB::table('items')->where('name', $w['item_name'])->value('id');
                if (! $itemId) {
                    $itemId = DB::table('items')->insertGetId([
                        'name' => $w['item_name'],
                        'description' => null,
                        'category_id' => $categoryId,
                        'created_by' => $userId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $variantId = DB::table('item_variants')->insertGetId([
                    'item_id' => $itemId,
                    'name' => $w['variant_name'],
                    'sku' => $w['sku'],
                    'size' => null,
                    'color' => null,
                    'stock' => $w['initial_stock'],
                    'minimum_stock' => 5,
                    'purchase_price' => $w['purchase_price'],
                    'selling_price' => $w['selling_price'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $variants[] = [
                    'id' => (int) $variantId,
                    'item_id' => (int) $itemId,
                    'purchase_price' => (float) $w['purchase_price'],
                    'selling_price' => (float) $w['selling_price'],
                    'stock' => (int) $w['initial_stock'],
                ];
            }
        });

        if (! $variants) {
            return;
        }

        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->startOfDay();

        $movements = [];
        $push = function (array $row) use (&$movements) {
            $movements[] = $row;
            if (count($movements) >= 500) {
                DB::table('item_movements')->insert($movements);
                $movements = [];
            }
        };

        $stocks = [];
        foreach ($variants as $v) {
            $stocks[$v['id']] = (int) $v['stock'];
        }

        $date = $start->copy();
        while ($date->lte($end)) {
            foreach ($variants as $v) {
                $variantId = $v['id'];
                $qty = random_int(0, 3);
                if ($qty === 0) {
                    continue;
                }
                if (($stocks[$variantId] ?? 0) < $qty) {
                    $restockQty = ($qty - ($stocks[$variantId] ?? 0)) + random_int(10, 25);
                    $restockAt = $date->copy()->setTime(10, random_int(0, 59), 0);
                    $push([
                        'item_id' => $v['item_id'],
                        'variant_id' => $variantId,
                        'type' => 'in',
                        'qty' => $restockQty,
                        'cost_per_unit' => $v['purchase_price'],
                        'reference_id' => null,
                        'customer_id' => null,
                        'note' => '[seed] fashion restock',
                        'movement_at' => $restockAt,
                        'created_by' => $userId,
                        'created_at' => $restockAt,
                        'updated_at' => $restockAt,
                    ]);
                    $stocks[$variantId] = ($stocks[$variantId] ?? 0) + $restockQty;
                }

                $saleAt = $date->copy()->setTime(14, random_int(0, 59), 0);
                $push([
                    'item_id' => $v['item_id'],
                    'variant_id' => $variantId,
                    'type' => 'out',
                    'qty' => $qty,
                    'cost_per_unit' => $v['selling_price'],
                    'reference_id' => $refSaleId,
                    'customer_id' => null,
                    'note' => '[seed] fashion sale',
                    'movement_at' => $saleAt,
                    'created_by' => $userId,
                    'created_at' => $saleAt,
                    'updated_at' => $saleAt,
                ]);
                $stocks[$variantId] = ($stocks[$variantId] ?? 0) - $qty;
            }
            $date->addDay();
        }

        if ($movements) {
            DB::table('item_movements')->insert($movements);
        }

        foreach ($stocks as $variantId => $finalStock) {
            DB::table('item_variants')
                ->where('id', $variantId)
                ->update([
                    'stock' => max(0, (int) $finalStock),
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        DB::table('item_movements')->where('note', 'like', '[seed] fashion%')->delete();
        DB::table('item_variants')->whereIn('sku', ['SKU003', 'SKU002'])->delete();
        DB::table('items')->whereIn('name', ['One Set', 'Long Dress'])->delete();
        DB::table('categories')->where('code', 'FASHION')->delete();
    }
};

