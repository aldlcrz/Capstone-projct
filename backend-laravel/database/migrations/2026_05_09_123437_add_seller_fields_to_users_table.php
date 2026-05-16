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
            if (!Schema::hasColumn('users', 'shopName')) {
                $table->string('shopName')->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'shopDescription')) {
                $table->text('shopDescription')->nullable()->after('shopName');
            }
            if (!Schema::hasColumn('users', 'businessPermit')) {
                $table->string('businessPermit')->nullable()->after('validId');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shopName', 'shopDescription', 'businessPermit']);
        });
    }
};
