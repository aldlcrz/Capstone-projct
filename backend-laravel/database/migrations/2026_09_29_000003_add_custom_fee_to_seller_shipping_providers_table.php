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
        if (Schema::hasTable('seller_shipping_providers') && !Schema::hasColumn('seller_shipping_providers', 'custom_fee')) {
            Schema::table('seller_shipping_providers', function (Blueprint $table) {
                $table->decimal('custom_fee', 8, 2)->nullable()->after('is_default');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('seller_shipping_providers') && Schema::hasColumn('seller_shipping_providers', 'custom_fee')) {
            Schema::table('seller_shipping_providers', function (Blueprint $table) {
                $table->dropColumn('custom_fee');
            });
        }
    }
};
