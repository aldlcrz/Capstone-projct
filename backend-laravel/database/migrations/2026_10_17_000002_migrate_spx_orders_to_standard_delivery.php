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
        if (Schema::hasTable('orders')) {
            DB::table('orders')
                ->where('courierName', 'like', '%SPX%')
                ->orWhere('courierName', 'like', '%shopee%')
                ->update(['courierName' => 'Standard Delivery']);
        }

        if (Schema::hasTable('order_shipping')) {
            DB::table('order_shipping')
                ->where('provider_name', 'like', '%SPX%')
                ->orWhere('provider_name', 'like', '%shopee%')
                ->update(['provider_name' => 'Standard Delivery']);

            DB::table('order_shipping')
                ->where('pricing_provider_name', 'like', '%SPX%')
                ->orWhere('pricing_provider_name', 'like', '%shopee%')
                ->update(['pricing_provider_name' => 'Standard Delivery']);

            DB::table('order_shipping')
                ->where('fulfillment_provider_name', 'like', '%SPX%')
                ->orWhere('fulfillment_provider_name', 'like', '%shopee%')
                ->update(['fulfillment_provider_name' => 'Standard Delivery']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // One-way data sanitization migration
    }
};
