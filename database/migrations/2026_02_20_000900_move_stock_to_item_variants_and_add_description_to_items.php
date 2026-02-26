<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0)->after('color');
            $table->unsignedInteger('minimum_stock')->default(0)->after('stock');
        });

        $now = now();

        DB::table('items')
            ->select('id', 'stock', 'minimum_stock')
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($now) {
                foreach ($items as $item) {
                    DB::table('item_variants')
                        ->where('item_id', $item->id)
                        ->orderBy('id')
                        ->limit(1)
                        ->update([
                            'stock' => $item->stock,
                            'minimum_stock' => $item->minimum_stock,
                            'updated_at' => $now,
                        ]);
                }
            });

        Schema::table('items', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->dropColumn(['stock', 'minimum_stock']);
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('minimum_stock')->default(0);
            $table->dropColumn('description');
        });

        Schema::table('item_variants', function (Blueprint $table) {
            $table->dropColumn(['stock', 'minimum_stock']);
        });
    }
};

