<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('commission_records')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                // Ensure the status column supports 'verification_pending' (or convert to string for future proofing)
                DB::statement("ALTER TABLE `commission_records` MODIFY COLUMN `status` VARCHAR(50) NOT NULL DEFAULT 'unpaid'");
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('commission_records')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `commission_records` MODIFY COLUMN `status` ENUM('unpaid', 'paid', 'waived', 'verification_pending') NOT NULL DEFAULT 'unpaid'");
            }
        }
    }
};
