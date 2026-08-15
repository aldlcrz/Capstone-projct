<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'shopName')) {
            DB::statement("ALTER TABLE `users` ADD COLUMN `shopName` VARCHAR(255) NULL AFTER `name`");
        }
        if (!Schema::hasColumn('users', 'shopDescription')) {
            DB::statement("ALTER TABLE `users` ADD COLUMN `shopDescription` TEXT NULL AFTER `shopName`");
        }
        if (!Schema::hasColumn('users', 'businessPermit')) {
            // Add after validId if it exists, otherwise just append
            if (Schema::hasColumn('users', 'validId')) {
                DB::statement("ALTER TABLE `users` ADD COLUMN `businessPermit` VARCHAR(255) NULL AFTER `validId`");
            } else {
                DB::statement("ALTER TABLE `users` ADD COLUMN `businessPermit` VARCHAR(255) NULL");
            }
        }
    }

    public function down(): void
    {
        foreach (['shopName', 'shopDescription', 'businessPermit'] as $col) {
            if (Schema::hasColumn('users', $col)) {
                DB::statement("ALTER TABLE `users` DROP COLUMN `$col`");
            }
        }
    }
};
