<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'courierName')) {
                $table->string('courierName')->nullable()->after('shippingAddress');
            }
            if (!Schema::hasColumn('orders', 'trackingNumber')) {
                $table->string('trackingNumber')->nullable()->after('courierName');
            }
            if (!Schema::hasColumn('orders', 'trackingLink')) {
                $table->string('trackingLink')->nullable()->after('trackingNumber');
            }
        });

        // Change status column from enum to varchar(255) so new status strings can be stored
        DB::statement("ALTER TABLE `orders` MODIFY `status` VARCHAR(255) NOT NULL DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['courierName', 'trackingNumber', 'trackingLink']);
        });
    }
};
