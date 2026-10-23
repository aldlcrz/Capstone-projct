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
            if (!Schema::hasColumn('products', 'color')) {
                $table->string('color', 100)->nullable()->after('target_group')->index();
            }
            if (!Schema::hasColumn('products', 'colors')) {
                $table->json('colors')->nullable()->after('color');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'colors')) {
                $table->dropColumn('colors');
            }
            if (Schema::hasColumn('products', 'color')) {
                $table->dropIndex(['color']);
                $table->dropColumn('color');
            }
        });
    }
};
