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
            if (!Schema::hasColumn('products', 'is_gcash_available')) {
                $table->boolean('is_gcash_available')->nullable()->default(null);
            }
            if (!Schema::hasColumn('products', 'gcash_number')) {
                $table->string('gcash_number')->nullable();
            }
            if (!Schema::hasColumn('products', 'gcash_qr_code')) {
                $table->string('gcash_qr_code')->nullable();
            }
            if (!Schema::hasColumn('products', 'is_maya_available')) {
                $table->boolean('is_maya_available')->nullable()->default(null);
            }
            if (!Schema::hasColumn('products', 'maya_number')) {
                $table->string('maya_number')->nullable();
            }
            if (!Schema::hasColumn('products', 'maya_qr_code')) {
                $table->string('maya_qr_code')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $cols = ['is_gcash_available', 'gcash_number', 'gcash_qr_code', 'is_maya_available', 'maya_number', 'maya_qr_code'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
