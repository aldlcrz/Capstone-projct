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
            if (!Schema::hasColumn('products', 'sale_duration')) {
                $table->string('sale_duration', 50)->nullable()->after('discount_percentage');
            }
            if (!Schema::hasColumn('products', 'sale_ends_at')) {
                $table->timestamp('sale_ends_at')->nullable()->after('sale_duration');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'sale_ends_at')) {
                $table->dropColumn('sale_ends_at');
            }
            if (Schema::hasColumn('products', 'sale_duration')) {
                $table->dropColumn('sale_duration');
            }
        });
    }
};
