<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipping_providers') && !Schema::hasColumn('shipping_providers', 'is_platform_default')) {
            Schema::table('shipping_providers', function (Blueprint $table) {
                $table->boolean('is_platform_default')->default(false)->after('is_active');
            });

            // Set explicit initial platform default provider if active providers exist
            $firstActive = DB::table('shipping_providers')->where('is_active', true)->orderBy('created_at', 'asc')->first();
            if ($firstActive) {
                DB::table('shipping_providers')->where('id', $firstActive->id)->update(['is_platform_default' => true]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shipping_providers') && Schema::hasColumn('shipping_providers', 'is_platform_default')) {
            Schema::table('shipping_providers', function (Blueprint $table) {
                $table->dropColumn('is_platform_default');
            });
        }
    }
};
