<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ItemVariant;
use App\Models\Customer;
use App\Models\Table;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::create([
            'name' => 'Admin',
            'email' => 'admin@email.com',
            'password' => bcrypt('password'),
        ]);

        $variants = ItemVariant::all();
        if ($variants->isEmpty()) {
            $this->command->info('No item variants found. Please seed items and variants first.');
            return;
        }

        // Seed some tables if none exist
        if (Table::count() === 0) {
            foreach (range(1, 5) as $i) {
                Table::create([
                    'name' => "T-0$i",
                    'status' => 'available',
                ]);
            }
        }

        $tables = Table::all();
        $customers = Customer::all();

        $statuses = ['paid', 'processing', 'ready', 'unpaid', 'cancelled'];
        $orderTypes = ['dine-in', 'takeaway'];

        // Create 10 sample orders
        for ($i = 0; $i < 10; $i++) {
            $status = $statuses[array_rand($statuses)];
            $orderType = $orderTypes[array_rand($orderTypes)];
            
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
            $table = ($orderType === 'dine-in' && $tables->isNotEmpty()) ? $tables->random() : null;
            $customer = $customers->isNotEmpty() ? $customers->random() : null;

            $order = Order::create([
                'order_number' => $orderNumber,
                'order_type' => $orderType,
                'table_id' => $table?->id,
                'status' => $status,
                'subtotal' => 0,
                'tax' => 0,
                'discount' => 0,
                'total' => 0,
                'payment_method' => $status === 'paid' ? 'Cash' : null,
                'customer_id' => $customer?->id,
                'created_by' => $user->id,
            ]);

            $subtotal = 0;
            $itemCount = rand(1, 4);
            $selectedVariants = $variants->random(min($itemCount, $variants->count()));

            foreach ($selectedVariants as $variant) {
                $qty = rand(1, 3);
                $price = $variant->selling_price;
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_id' => $variant->item_id,
                    'variant_id' => $variant->id,
                    'qty' => $qty,
                    'price' => $price,
                    'note' => rand(0, 1) ? 'Sample note' : null,
                ]);
            }

            $tax = $subtotal * 0.1; // 10% tax
            $discount = rand(0, 1) ? 5000 : 0;
            $total = $subtotal + $tax - $discount;

            $order->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
            ]);

            // Update table status if dine-in and processing/ready
            if ($table && in_array($status, ['processing', 'ready'])) {
                $table->update(['status' => 'occupied']);
            }
        }

        $this->command->info('10 sample orders seeded successfully.');
    }
}
