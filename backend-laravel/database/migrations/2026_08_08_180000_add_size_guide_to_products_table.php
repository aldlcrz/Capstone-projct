<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'size_guide_image')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('size_guide_image')->nullable()->after('size_stocks');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'size_guide_image')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('size_guide_image');
            });
        }
    }
};
