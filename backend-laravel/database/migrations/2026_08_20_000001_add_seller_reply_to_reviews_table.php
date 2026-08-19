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
        Schema::table('reviews', function (Blueprint $table) {
            if (!Schema::hasColumn('reviews', 'seller_reply')) {
                $table->text('seller_reply')->nullable()->after('comment');
            }
            if (!Schema::hasColumn('reviews', 'seller_reply_at')) {
                $table->timestamp('seller_reply_at')->nullable()->after('seller_reply');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'seller_reply_at')) {
                $table->dropColumn('seller_reply_at');
            }
            if (Schema::hasColumn('reviews', 'seller_reply')) {
                $table->dropColumn('seller_reply');
            }
        });
    }
};
