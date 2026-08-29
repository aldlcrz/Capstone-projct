<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            DB::statement("ALTER TABLE `products` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            DB::statement("ALTER TABLE `products` MODIFY `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'");
        });
    }
};
