<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('categories', 'target_group')) {
            DB::statement("ALTER TABLE `categories` ADD COLUMN `target_group` VARCHAR(255) NULL AFTER `description`");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('categories', 'target_group')) {
            DB::statement("ALTER TABLE `categories` DROP COLUMN `target_group`");
        }
    }
};
