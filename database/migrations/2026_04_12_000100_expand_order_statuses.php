<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('unpaid','processing','ready','completed','paid','cancelled') NOT NULL DEFAULT 'processing'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status ENUM('unpaid','paid','cancelled') NOT NULL DEFAULT 'unpaid'");
    }
};
