<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('wishlists')) {
            Schema::table('wishlists', function (Blueprint $table) {
                if (!Schema::hasColumn('wishlists', 'size')) {
                    $table->string('size', 50)->nullable()->after('product_id');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('wishlists')) {
            Schema::table('wishlists', function (Blueprint $table) {
                if (Schema::hasColumn('wishlists', 'size')) {
                    $table->dropColumn('size');
                }
            });
        }
    }
};
