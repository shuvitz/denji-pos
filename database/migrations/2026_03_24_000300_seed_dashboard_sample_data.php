<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (! DB::getSchemaBuilder()->hasTable('users')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('customers')) {
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

        $existingMovements = (int) DB::table('item_movements')->count();
        if ($existingMovements > 0) {
            return;
        }

        $userId = DB::table('users')->orderBy('id')->value('id');
        if (! $userId) {
            $userId = DB::table('users')->insertGetId([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $refPurchaseId = DB::table('reference_types')->where('name', 'purchase')->value('id');
        $refSaleId = DB::table('reference_types')->where('name', 'sale')->value('id');
        $refManualId = DB::table('reference_types')->where('name', 'manual')->value('id');

        if (! $refPurchaseId || ! $refSaleId || ! $refManualId) {
            DB::table('reference_types')->upsert([
                ['name' => 'purchase', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'sale', 'created_at' => $now, 'updated_at' => $now],
                ['name' => 'manual', 'created_at' => $now, 'updated_at' => $now],
            ], ['name'], ['updated_at']);

            $refPurchaseId = DB::table('reference_types')->where('name', 'purchase')->value('id');
            $refSaleId = DB::table('reference_types')->where('name', 'sale')->value('id');
            $refManualId = DB::table('reference_types')->where('name', 'manual')->value('id');
        }

        DB::table('categories')->upsert([
            [
                'name' => 'Demo Beverage',
                'code' => 'DEMO-BEV',
                'description' => 'Seeded category',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Demo Snack',
                'code' => 'DEMO-SNK',
                'description' => 'Seeded category',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Demo Household',
                'code' => 'DEMO-HOU',
                'description' => 'Seeded category',
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['code'], ['name', 'description', 'updated_at']);

        $categoryIds = DB::table('categories')
            ->whereIn('code', ['DEMO-BEV', 'DEMO-SNK', 'DEMO-HOU'])
            ->pluck('id')
            ->values()
            ->all();

        $categoryId = $categoryIds[0] ?? null;
        if (! $categoryId) {
            return;
        }

        DB::table('customers')->upsert([
            [
                'name' => 'Demo Customer A',
                'phone' => '081234567890',
                'email' => 'demo-customer-a@example.com',
                'gender' => 'male',
                'birth_date' => '1995-04-10',
                'address' => 'Demo Street 1',
                'city' => 'Jakarta',
                'postal_code' => '10110',
                'notes' => '[seed]',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Demo Customer B',
                'phone' => '089876543210',
                'email' => 'demo-customer-b@example.com',
                'gender' => 'female',
                'birth_date' => '1997-09-22',
                'address' => 'Demo Street 2',
                'city' => 'Bandung',
                'postal_code' => '40111',
                'notes' => '[seed]',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Demo Customer C',
                'phone' => '082211223344',
                'email' => 'demo-customer-c@example.com',
                'gender' => 'other',
                'birth_date' => '2000-01-15',
                'address' => 'Demo Street 3',
                'city' => 'Surabaya',
                'postal_code' => '60111',
                'notes' => '[seed]',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['email'], ['name', 'phone', 'gender', 'birth_date', 'address', 'city', 'postal_code', 'notes', 'is_active', 'updated_at']);

        $customerIds = DB::table('customers')
            ->whereIn('email', ['demo-customer-a@example.com', 'demo-customer-b@example.com', 'demo-customer-c@example.com'])
            ->pluck('id')
            ->values()
            ->all();

        $suffix = Carbon::now()->format('ymdHis');

        $itemsToCreate = [
            ['name' => 'Demo Cola', 'category_code' => 'DEMO-BEV', 'variants' => [['name' => '330ml', 'sku' => "DEMO-COLA-330-$suffix", 'purchase' => 4500, 'sell' => 7000], ['name' => '500ml', 'sku' => "DEMO-COLA-500-$suffix", 'purchase' => 6500, 'sell' => 9500]]],
            ['name' => 'Demo Mineral Water', 'category_code' => 'DEMO-BEV', 'variants' => [['name' => '600ml', 'sku' => "DEMO-WTR-600-$suffix", 'purchase' => 1800, 'sell' => 3000], ['name' => '1500ml', 'sku' => "DEMO-WTR-1500-$suffix", 'purchase' => 4200, 'sell' => 6500]]],
            ['name' => 'Demo Chips', 'category_code' => 'DEMO-SNK', 'variants' => [['name' => 'Original', 'sku' => "DEMO-CHP-ORG-$suffix", 'purchase' => 6000, 'sell' => 9000], ['name' => 'Spicy', 'sku' => "DEMO-CHP-SPY-$suffix", 'purchase' => 6200, 'sell' => 9200]]],
            ['name' => 'Demo Chocolate', 'category_code' => 'DEMO-SNK', 'variants' => [['name' => 'Small', 'sku' => "DEMO-CHO-S-$suffix", 'purchase' => 5000, 'sell' => 8000], ['name' => 'Large', 'sku' => "DEMO-CHO-L-$suffix", 'purchase' => 9000, 'sell' => 14000]]],
            ['name' => 'Demo Detergent', 'category_code' => 'DEMO-HOU', 'variants' => [['name' => '500g', 'sku' => "DEMO-DTR-500-$suffix", 'purchase' => 10000, 'sell' => 14500], ['name' => '1kg', 'sku' => "DEMO-DTR-1K-$suffix", 'purchase' => 18500, 'sell' => 26000]]],
        ];

        $categoryMap = DB::table('categories')
            ->whereIn('code', ['DEMO-BEV', 'DEMO-SNK', 'DEMO-HOU'])
            ->pluck('id', 'code')
            ->all();

        $variantInfo = [];

        DB::transaction(function () use ($itemsToCreate, $categoryMap, $userId, $now, &$variantInfo) {
            foreach ($itemsToCreate as $itemRow) {
                $catId = $categoryMap[$itemRow['category_code']] ?? null;
                if (! $catId) {
                    continue;
                }

                $itemId = DB::table('items')->insertGetId([
                    'name' => $itemRow['name'],
                    'description' => '[seed]',
                    'category_id' => $catId,
                    'created_by' => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                foreach ($itemRow['variants'] as $v) {
                    $initialStock = random_int(25, 60);
                    $variantId = DB::table('item_variants')->insertGetId([
                        'item_id' => $itemId,
                        'name' => $v['name'],
                        'sku' => $v['sku'],
                        'size' => null,
                        'color' => null,
                        'stock' => $initialStock,
                        'minimum_stock' => 8,
                        'purchase_price' => $v['purchase'],
                        'selling_price' => $v['sell'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $variantInfo[$variantId] = [
                        'item_id' => $itemId,
                        'stock' => $initialStock,
                        'purchase_price' => (float) $v['purchase'],
                        'selling_price' => (float) $v['sell'],
                    ];
                }
            }
        });

        if (! $variantInfo) {
            return;
        }

        $variantIds = array_keys($variantInfo);
        $stock = [];
        foreach ($variantInfo as $variantId => $info) {
            $stock[$variantId] = (int) $info['stock'];
        }

        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->startOfDay();

        $movements = [];

        $pickVariant = function () use ($variantIds) {
            return $variantIds[array_rand($variantIds)];
        };

        $pickCustomer = function () use ($customerIds) {
            if (! $customerIds) {
                return null;
            }
            return $customerIds[array_rand($customerIds)];
        };

        $pushMovement = function (array $row) use (&$movements) {
            $movements[] = $row;
            if (count($movements) >= 500) {
                DB::table('item_movements')->insert($movements);
                $movements = [];
            }
        };

        $date = $start->copy();
        while ($date->lte($end)) {
            $purchaseCount = random_int(1, 2);
            for ($i = 0; $i < $purchaseCount; $i += 1) {
                $variantId = $pickVariant();
                $qty = random_int(6, 18);
                $info = $variantInfo[$variantId];
                $movementAt = $date->copy()->setTime(random_int(8, 10), random_int(0, 59), 0);

                $stock[$variantId] += $qty;

                $pushMovement([
                    'item_id' => $info['item_id'],
                    'variant_id' => $variantId,
                    'type' => 'in',
                    'qty' => $qty,
                    'cost_per_unit' => $info['purchase_price'],
                    'reference_id' => $refPurchaseId,
                    'customer_id' => null,
                    'note' => '[seed] purchase',
                    'movement_at' => $movementAt,
                    'created_by' => $userId,
                    'created_at' => $movementAt,
                    'updated_at' => $movementAt,
                ]);
            }

            $saleCount = random_int(1, 3);
            for ($i = 0; $i < $saleCount; $i += 1) {
                $variantId = $pickVariant();
                $qty = random_int(1, 6);
                $info = $variantInfo[$variantId];
                $movementAt = $date->copy()->setTime(random_int(12, 20), random_int(0, 59), 0);

                if ($stock[$variantId] < $qty) {
                    $extra = ($qty - $stock[$variantId]) + random_int(5, 15);
                    $stock[$variantId] += $extra;
                    $purchaseAt = $date->copy()->setTime(9, random_int(0, 59), 0);

                    $pushMovement([
                        'item_id' => $info['item_id'],
                        'variant_id' => $variantId,
                        'type' => 'in',
                        'qty' => $extra,
                        'cost_per_unit' => $info['purchase_price'],
                        'reference_id' => $refPurchaseId,
                        'customer_id' => null,
                        'note' => '[seed] auto-restock',
                        'movement_at' => $purchaseAt,
                        'created_by' => $userId,
                        'created_at' => $purchaseAt,
                        'updated_at' => $purchaseAt,
                    ]);
                }

                $stock[$variantId] -= $qty;

                $pushMovement([
                    'item_id' => $info['item_id'],
                    'variant_id' => $variantId,
                    'type' => 'out',
                    'qty' => $qty,
                    'cost_per_unit' => $info['selling_price'],
                    'reference_id' => $refSaleId,
                    'customer_id' => $pickCustomer(),
                    'note' => '[seed] sale',
                    'movement_at' => $movementAt,
                    'created_by' => $userId,
                    'created_at' => $movementAt,
                    'updated_at' => $movementAt,
                ]);
            }

            $date->addDay();
        }

        if ($movements) {
            DB::table('item_movements')->insert($movements);
        }

        foreach ($stock as $variantId => $finalStock) {
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
        DB::table('item_movements')->where('note', 'like', '[seed]%')->delete();
        DB::table('item_variants')->where('sku', 'like', 'DEMO-%')->delete();
        DB::table('items')->where('description', '[seed]')->delete();
        DB::table('customers')->where('notes', '[seed]')->delete();
        DB::table('categories')->where('code', 'like', 'DEMO-%')->delete();
    }
};

