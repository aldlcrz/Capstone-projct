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
        Schema::table('banners', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->uuid('userId')->nullable()->collation('utf8mb4_bin')->after('id');
            } else {
                $table->uuid('userId')->nullable()->after('id');
            }
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->string('status')->default('approved')->after('is_active'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['userId']);
            $table->dropColumn(['userId', 'status', 'rejection_reason']);
        });
    }
};
