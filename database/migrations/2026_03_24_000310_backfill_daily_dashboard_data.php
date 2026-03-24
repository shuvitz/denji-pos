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
        if (! DB::getSchemaBuilder()->hasTable('reference_types')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('item_variants')) {
            return;
        }
        if (! DB::getSchemaBuilder()->hasTable('item_movements')) {
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

        DB::table('reference_types')->upsert([
            ['name' => 'purchase', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'sale', 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'manual', 'created_at' => $now, 'updated_at' => $now],
        ], ['name'], ['updated_at']);

        $refPurchaseId = DB::table('reference_types')->where('name', 'purchase')->value('id');
        $refSaleId = DB::table('reference_types')->where('name', 'sale')->value('id');

        $customerId = DB::table('customers')->orderBy('id')->value('id');
        if (! $customerId) {
            $customerId = DB::table('customers')->insertGetId([
                'name' => 'Walk-in Customer',
                'phone' => null,
                'email' => 'walkin@example.com',
                'gender' => null,
                'birth_date' => null,
                'address' => null,
                'city' => null,
                'postal_code' => null,
                'notes' => '[seed]',
                'is_active' => true,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $variant = DB::table('item_variants')
            ->select('id', 'item_id', 'stock', 'purchase_price', 'selling_price')
            ->orderBy('id')
            ->first();

        if (! $variant) {
            return;
        }

        $variantId = (int) $variant->id;
        $itemId = (int) $variant->item_id;
        $stock = (int) ($variant->stock ?? 0);
        $purchasePrice = (float) ($variant->purchase_price ?? 0);
        $sellingPrice = (float) ($variant->selling_price ?? 0);

        $start = Carbon::now()->subDays(29)->startOfDay();
        $end = Carbon::now()->startOfDay();

        $date = $start->copy();
        while ($date->lte($end)) {
            $day = $date->toDateString();

            $hasIn = DB::table('item_movements')
                ->whereDate('movement_at', $day)
                ->where('type', 'in')
                ->exists();

            $hasOut = DB::table('item_movements')
                ->whereDate('movement_at', $day)
                ->where('type', 'out')
                ->exists();

            if (! $hasIn) {
                $qty = random_int(8, 20);
                $movementAt = $date->copy()->setTime(9, random_int(0, 59), 0);
                DB::table('item_movements')->insert([
                    'item_id' => $itemId,
                    'variant_id' => $variantId,
                    'type' => 'in',
                    'qty' => $qty,
                    'cost_per_unit' => $purchasePrice,
                    'reference_id' => $refPurchaseId,
                    'customer_id' => null,
                    'note' => '[seed] daily purchase',
                    'movement_at' => $movementAt,
                    'created_by' => $userId,
                    'created_at' => $movementAt,
                    'updated_at' => $movementAt,
                ]);

                $stock += $qty;
            }

            if (! $hasOut) {
                $qty = random_int(1, 6);
                if ($stock < $qty) {
                    $extra = ($qty - $stock) + random_int(5, 15);
                    $purchaseAt = $date->copy()->setTime(8, random_int(0, 59), 0);
                    DB::table('item_movements')->insert([
                        'item_id' => $itemId,
                        'variant_id' => $variantId,
                        'type' => 'in',
                        'qty' => $extra,
                        'cost_per_unit' => $purchasePrice,
                        'reference_id' => $refPurchaseId,
                        'customer_id' => null,
                        'note' => '[seed] daily auto-restock',
                        'movement_at' => $purchaseAt,
                        'created_by' => $userId,
                        'created_at' => $purchaseAt,
                        'updated_at' => $purchaseAt,
                    ]);
                    $stock += $extra;
                }

                $movementAt = $date->copy()->setTime(15, random_int(0, 59), 0);
                DB::table('item_movements')->insert([
                    'item_id' => $itemId,
                    'variant_id' => $variantId,
                    'type' => 'out',
                    'qty' => $qty,
                    'cost_per_unit' => $sellingPrice,
                    'reference_id' => $refSaleId,
                    'customer_id' => $customerId,
                    'note' => '[seed] daily sale',
                    'movement_at' => $movementAt,
                    'created_by' => $userId,
                    'created_at' => $movementAt,
                    'updated_at' => $movementAt,
                ]);

                $stock -= $qty;
            }

            $date->addDay();
        }

        DB::table('item_variants')
            ->where('id', $variantId)
            ->update([
                'stock' => max(0, $stock),
                'updated_at' => $now,
            ]);
    }

    public function down(): void
    {
        DB::table('item_movements')->where('note', 'like', '[seed] daily%')->delete();
    }
};

