<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_movements', function (Blueprint $table) {
            $table->foreignId('variant_id')
                ->nullable()
                ->after('item_id')
                ->constrained('item_variants')
                ->nullOnDelete();

            $table->decimal('cost_per_unit', 15, 2)
                ->nullable()
                ->after('quantity');

            $table->enum('reference_type', ['purchase', 'sale', 'manual'])
                ->nullable()
                ->after('cost_per_unit');

            $table->unsignedBigInteger('reference_id')
                ->nullable()
                ->after('reference_type');
        });

        DB::statement("ALTER TABLE item_movements MODIFY COLUMN type ENUM('in','out','adjustment') NOT NULL");
        DB::statement("ALTER TABLE item_movements CHANGE quantity qty INT UNSIGNED NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE item_movements CHANGE qty quantity INT UNSIGNED NOT NULL");
        DB::statement("ALTER TABLE item_movements MODIFY COLUMN type ENUM('in','out') NOT NULL");

        Schema::table('item_movements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('variant_id');
            $table->dropColumn(['cost_per_unit', 'reference_type', 'reference_id']);
        });
    }
};

