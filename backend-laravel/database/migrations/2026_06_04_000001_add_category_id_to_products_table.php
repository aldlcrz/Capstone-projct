<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Only add the column if it doesn't already exist
        if (!Schema::hasColumn('products', 'CategoryId')) {
            // Use raw SQL to match the exact char(36) utf8mb4_bin collation
            // used by categories.id, so the foreign key forms correctly
            DB::statement("ALTER TABLE `products` ADD COLUMN `CategoryId` CHAR(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL AFTER `sellerId`");
            DB::statement("ALTER TABLE `products` ADD CONSTRAINT `products_categoryid_foreign` FOREIGN KEY (`CategoryId`) REFERENCES `categories` (`id`) ON DELETE SET NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'CategoryId')) {
            DB::statement("ALTER TABLE `products` DROP FOREIGN KEY `products_categoryid_foreign`");
            DB::statement("ALTER TABLE `products` DROP COLUMN `CategoryId`");
        }
    }
};
