<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('refund_transactions', 'notes')) {
                    $table->text('notes')->nullable()->after('failure_reason');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('refund_transactions')) {
            Schema::table('refund_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('refund_transactions', 'notes')) {
                    $table->dropColumn('notes');
                }
            });
        }
    }
};
