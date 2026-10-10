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
        if (Schema::hasTable('products') && !Schema::hasColumn('products', 'inventory_mode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('inventory_mode', 30)->default('available_stock')->after('stock');
            });
        }

        if (Schema::hasTable('order_items') && !Schema::hasColumn('order_items', 'inventory_mode')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->string('inventory_mode', 30)->default('available_stock')->after('variation');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'inventory_mode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('inventory_mode');
            });
        }

        if (Schema::hasTable('order_items') && Schema::hasColumn('order_items', 'inventory_mode')) {
            Schema::table('order_items', function (Blueprint $table) {
                $table->dropColumn('inventory_mode');
            });
        }
    }
};
