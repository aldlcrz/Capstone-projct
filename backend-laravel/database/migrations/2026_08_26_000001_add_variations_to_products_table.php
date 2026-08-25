<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (!Schema::hasColumn('products', 'has_variants')) {
                    $table->boolean('has_variants')->default(false)->after('target_group');
                }
                if (!Schema::hasColumn('products', 'variations')) {
                    $table->json('variations')->nullable()->after('has_variants');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (Schema::hasColumn('products', 'variations')) {
                    $table->dropColumn('variations');
                }
                if (Schema::hasColumn('products', 'has_variants')) {
                    $table->dropColumn('has_variants');
                }
            });
        }
    }
};
