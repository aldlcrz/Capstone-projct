<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seller_shipping_providers') && !Schema::hasColumn('seller_shipping_providers', 'is_default')) {
            Schema::table('seller_shipping_providers', function (Blueprint $table) {
                $table->boolean('is_default')->default(false)->after('is_enabled');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('seller_shipping_providers') && Schema::hasColumn('seller_shipping_providers', 'is_default')) {
            Schema::table('seller_shipping_providers', function (Blueprint $table) {
                $table->dropColumn('is_default');
            });
        }
    }
};
