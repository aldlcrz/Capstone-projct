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
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'isMayaAvailable')) {
                $table->boolean('isMayaAvailable')->default(false);
            }
            if (!Schema::hasColumn('users', 'isGcashAvailable')) {
                $table->boolean('isGcashAvailable')->default(true);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'size_stocks')) {
                $table->json('size_stocks')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'isMayaAvailable')) {
                $table->dropColumn('isMayaAvailable');
            }
            if (Schema::hasColumn('users', 'isGcashAvailable')) {
                $table->dropColumn('isGcashAvailable');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'size_stocks')) {
                $table->dropColumn('size_stocks');
            }
        });
    }
};
