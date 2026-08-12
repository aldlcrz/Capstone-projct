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
        Schema::table('reviews', function (Blueprint $table) {
            $table->char('orderItemId', 36)->collation('utf8mb4_bin')->nullable()->after('orderId');
            $table->foreign('orderItemId')->references('id')->on('order_items')->onDelete('cascade');
            $table->unique('orderItemId', 'unique_order_item_review');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['orderItemId']);
            $table->dropUnique('unique_order_item_review');
            $table->dropColumn('orderItemId');
        });
    }
};
