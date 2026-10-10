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
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (!Schema::hasColumn('orders', 'appointment_date')) {
                    $table->date('appointment_date')->nullable()->after('status');
                }
                if (!Schema::hasColumn('orders', 'appointment_time')) {
                    $table->string('appointment_time', 50)->nullable()->after('appointment_date');
                }
                if (!Schema::hasColumn('orders', 'appointment_notes')) {
                    $table->text('appointment_notes')->nullable()->after('appointment_time');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (Schema::hasColumn('orders', 'appointment_notes')) {
                    $table->dropColumn('appointment_notes');
                }
                if (Schema::hasColumn('orders', 'appointment_time')) {
                    $table->dropColumn('appointment_time');
                }
                if (Schema::hasColumn('orders', 'appointment_date')) {
                    $table->dropColumn('appointment_date');
                }
            });
        }
    }
};
