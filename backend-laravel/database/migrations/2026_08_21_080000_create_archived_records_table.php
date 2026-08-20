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
        Schema::create('archived_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('item_type'); // 'product', 'category', 'customer', 'seller'
            $table->string('item_id')->nullable();
            $table->string('name');
            $table->string('identifier')->nullable(); // email, SKU, tag, etc.
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable(); // full snapshot of deleted entity
            $table->string('archived_by')->nullable(); // Admin name
            $table->timestamps();

            $table->index(['item_type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_records');
    }
};
