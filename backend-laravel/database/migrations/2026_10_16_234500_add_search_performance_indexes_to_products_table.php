<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Composite index to accelerate approved marketplace catalog searches and audience filtering
            try {
                $table->index(['status', 'target_group'], 'idx_products_status_target_group');
            } catch (\Throwable $e) {
                // Index already exists or composite indexing unsupported in target environment
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            try {
                $table->dropIndex('idx_products_status_target_group');
            } catch (\Throwable $e) {
                // Index already dropped or nonexistent
            }
        });
    }
};
