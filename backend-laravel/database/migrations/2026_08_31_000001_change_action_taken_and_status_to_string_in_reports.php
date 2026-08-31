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
        try {
            DB::statement("ALTER TABLE `reports` MODIFY `actionTaken` VARCHAR(100) NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `reports` MODIFY `status` VARCHAR(50) NOT NULL DEFAULT 'Pending'");
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            DB::statement("ALTER TABLE `reports` MODIFY `actionTaken` ENUM('None', 'Warning', 'Restricted', 'Suspended') NULL");
        } catch (\Throwable $e) {}

        try {
            DB::statement("ALTER TABLE `reports` MODIFY `status` ENUM('Pending', 'In Review', 'Resolved', 'Dismissed') NOT NULL DEFAULT 'Pending'");
        } catch (\Throwable $e) {}
    }
};
