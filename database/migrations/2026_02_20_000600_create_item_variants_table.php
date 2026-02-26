<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('sku')->unique();
            $table->string('size')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
        });

        $now = now();

        DB::table('items')
            ->select('id', 'name', 'sku')
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($now) {
                $rows = [];

                foreach ($items as $item) {
                    if (! $item->sku) {
                        continue;
                    }

                    $rows[] = [
                        'item_id' => $item->id,
                        'name' => $item->name,
                        'sku' => $item->sku,
                        'size' => null,
                        'color' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                if ($rows) {
                    DB::table('item_variants')->insert($rows);
                }
            });

        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('sku');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->string('sku')->unique()->nullable();
        });

        Schema::dropIfExists('item_variants');
    }
};

