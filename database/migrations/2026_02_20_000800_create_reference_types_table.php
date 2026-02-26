<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('reference_types')->insert([
            ['name' => 'purchase', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'sale', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'manual', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('item_movements', function (Blueprint $table) {
            $table->dropColumn('reference');
            $table->dropColumn('reference_type');

            $table->foreign('reference_id')
                ->references('id')
                ->on('reference_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('item_movements', function (Blueprint $table) {
            $table->dropForeign(['reference_id']);

            $table->string('reference')->nullable()->after('qty');
            $table->enum('reference_type', ['purchase', 'sale', 'manual'])
                ->nullable()
                ->after('reference');
        });

        Schema::dropIfExists('reference_types');
    }
};

