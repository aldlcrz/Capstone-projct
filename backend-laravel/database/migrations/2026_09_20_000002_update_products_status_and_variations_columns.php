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
        if (!Schema::hasTable('products')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'has_variants')) {
                $table->boolean('has_variants')->default(false);
            }
            if (!Schema::hasColumn('products', 'variations')) {
                $table->json('variations')->nullable();
            }
        });

        // Ensure status column accepts all valid states ('draft', 'pending', 'approved', 'rejected', 'archived')
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            try {
                DB::statement("ALTER TABLE `products` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {
                // Fallback using schema table change
                Schema::table('products', function (Blueprint $table) {
                    $table->string('status', 50)->default('pending')->change();
                });
            }
        } else {
            Schema::table('products', function (Blueprint $table) {
                $table->string('status', 50)->default('pending')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            try {
                DB::statement("ALTER TABLE `products` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'pending'");
            } catch (\Throwable $e) {}
        }
    }
};
