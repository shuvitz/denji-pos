<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_movements', function (Blueprint $table) {
            // Optional link to orders — nullable for backward compatibility.
            // Manual movements will have order_id = null (which is fine).
            // Future order-based movements will populate this.
            $table->foreignId('order_id')
                ->nullable()
                ->after('reference_id')
                ->constrained('orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('order_id');
        });
    }
};
