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
        if (!Schema::hasTable('seller_special_delivery_rates')) {
            Schema::create('seller_special_delivery_rates', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('seller_id', 36)->index();
                $table->string('municipality_key', 50)->index();
                $table->string('municipality_name', 100);
                $table->decimal('surcharge', 10, 2)->default(0.00);
                $table->boolean('is_enabled')->default(true);
                $table->timestamps();

                $table->unique(['seller_id', 'municipality_key'], 'seller_special_delivery_muni_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_special_delivery_rates');
    }
};
