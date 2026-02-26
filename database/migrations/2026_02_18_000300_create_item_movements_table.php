<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained('items');
            $table->enum('type', ['in', 'out']);
            $table->unsignedInteger('quantity');
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->timestamp('movement_at')->useCurrent();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_movements');
    }
};

