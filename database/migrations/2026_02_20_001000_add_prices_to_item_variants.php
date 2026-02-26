<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            $table->decimal('purchase_price', 15, 2)
                ->default(0)
                ->after('minimum_stock');
            $table->decimal('selling_price', 15, 2)
                ->default(0)
                ->after('purchase_price');
        });
    }

    public function down(): void
    {
        Schema::table('item_variants', function (Blueprint $table) {
            $table->dropColumn(['purchase_price', 'selling_price']);
        });
    }
};

