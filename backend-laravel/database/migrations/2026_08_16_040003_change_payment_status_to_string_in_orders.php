<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        try {
            DB::statement("ALTER TABLE `orders` MODIFY `paymentStatus` VARCHAR(50) NOT NULL DEFAULT 'Pending'");
        } catch (\Throwable $e) {
            Schema::table('orders', function (Blueprint $table) {
                $table->string('paymentStatus', 50)->default('Pending')->change();
            });
        }
    }

    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `orders` MODIFY `paymentStatus` ENUM('pending','paid','failed') DEFAULT 'pending'");
        } catch (\Throwable $e) {}
    }
};
