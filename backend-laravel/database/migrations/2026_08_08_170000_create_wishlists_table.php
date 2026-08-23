<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('wishlists')) {
            Schema::create('wishlists', function (Blueprint $table) {
                $table->id();
                $userCol = $table->uuid('user_id');
                $prodCol = $table->uuid('product_id');
                if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
                    $userCol->collation('utf8mb4_general_ci');
                    $prodCol->collation('utf8mb4_bin');
                }
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->unique(['user_id', 'product_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
