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
        if (!Schema::hasColumn('reviews', 'video')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->string('video')->nullable()->after('images');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('reviews', 'video')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->dropColumn('video');
            });
        }
    }
};
