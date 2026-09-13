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
        Schema::table('commission_records', function (Blueprint $table) {
            if (!Schema::hasColumn('commission_records', 'paymentMethod')) {
                $table->string('paymentMethod')->nullable()->after('status');
            }
            if (!Schema::hasColumn('commission_records', 'referenceNumber')) {
                $table->string('referenceNumber')->nullable()->after('paymentMethod');
            }
            if (!Schema::hasColumn('commission_records', 'paymentProof')) {
                $table->string('paymentProof')->nullable()->after('referenceNumber');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_records', function (Blueprint $table) {
            if (Schema::hasColumn('commission_records', 'paymentProof')) {
                $table->dropColumn('paymentProof');
            }
            if (Schema::hasColumn('commission_records', 'referenceNumber')) {
                $table->dropColumn('referenceNumber');
            }
            if (Schema::hasColumn('commission_records', 'paymentMethod')) {
                $table->dropColumn('paymentMethod');
            }
        });
    }
};
