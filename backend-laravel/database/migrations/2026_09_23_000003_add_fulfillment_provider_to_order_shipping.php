<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order_shipping')) {
            Schema::table('order_shipping', function (Blueprint $table) {
                if (!Schema::hasColumn('order_shipping', 'pricing_provider_id')) {
                    $table->uuid('pricing_provider_id')->nullable()->after('provider_name');
                }
                if (!Schema::hasColumn('order_shipping', 'pricing_provider_name')) {
                    $table->string('pricing_provider_name', 100)->nullable()->after('pricing_provider_id');
                }
                if (!Schema::hasColumn('order_shipping', 'fulfillment_provider_id')) {
                    $table->uuid('fulfillment_provider_id')->nullable()->after('pricing_provider_name');
                }
                if (!Schema::hasColumn('order_shipping', 'fulfillment_provider_name')) {
                    $table->string('fulfillment_provider_name', 100)->nullable()->after('fulfillment_provider_id');
                }
            });

            // Backfill pricing_provider fields from existing provider snapshot
            DB::statement('UPDATE order_shipping SET pricing_provider_id = provider_id WHERE pricing_provider_id IS NULL AND provider_id IS NOT NULL');
            DB::statement('UPDATE order_shipping SET pricing_provider_name = provider_name WHERE pricing_provider_name IS NULL AND provider_name IS NOT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order_shipping')) {
            Schema::table('order_shipping', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('order_shipping', 'fulfillment_provider_name')) $cols[] = 'fulfillment_provider_name';
                if (Schema::hasColumn('order_shipping', 'fulfillment_provider_id')) $cols[] = 'fulfillment_provider_id';
                if (Schema::hasColumn('order_shipping', 'pricing_provider_name')) $cols[] = 'pricing_provider_name';
                if (Schema::hasColumn('order_shipping', 'pricing_provider_id')) $cols[] = 'pricing_provider_id';
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
