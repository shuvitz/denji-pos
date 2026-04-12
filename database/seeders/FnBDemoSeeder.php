<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FnBDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();
        if (!$user) {
            $user = \App\Models\User::create([
                'name' => 'Admin F&B',
                'email' => 'admin@fnb.com',
                'password' => bcrypt('password'),
            ]);
        }

        $categoryId = \Illuminate\Support\Facades\DB::table('categories')->insertGetId([
            'name' => 'Japanese Food',
            'code' => 'JAP01',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $items = [
            'Takoyaki' => 20000,
            'Okonomiyaki' => 25000,
        ];

        $variants = ['Gurita', 'Keju', 'Sosis', 'Mix'];

        foreach ($items as $itemName => $basePrice) {
            $itemId = \Illuminate\Support\Facades\DB::table('items')->insertGetId([
                'name' => $itemName,
                'category_id' => $categoryId,
                'description' => 'Delicious ' . $itemName,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($variants as $variantName) {
                $price = $basePrice + ($variantName === 'Mix' ? 5000 : 0);

                \Illuminate\Support\Facades\DB::table('item_variants')->insert([
                    'item_id' => $itemId,
                    'name' => $variantName,
                    'sku' => strtoupper(substr($itemName, 0, 3)) . '-' . strtoupper(substr($variantName, 0, 3)),
                    'size' => 'Regular',
                    'color' => null,
                    'stock' => 9999,
                    'minimum_stock' => 10,
                    'purchase_price' => $price * 0.4,
                    'selling_price' => $price,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        for ($i = 1; $i <= 5; $i++) {
            \Illuminate\Support\Facades\DB::table('tables')->insert([
                'name' => 'Table ' . $i,
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
