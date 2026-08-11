<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'fabric_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('fabric_type')->nullable()->after('description');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'fabric_type')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('fabric_type');
            });
        }
    }
};
