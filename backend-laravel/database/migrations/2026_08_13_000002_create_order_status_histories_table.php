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
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->char('orderId', 36)->collation('utf8mb4_bin');
            $table->foreign('orderId')->references('id')->on('orders')->onDelete('cascade');
            $table->string('previousStatus')->nullable();
            $table->string('newStatus');
            $table->char('updatedBy', 36)->collation('utf8mb4_bin')->nullable();
            $table->foreign('updatedBy')->references('id')->on('users')->onDelete('set null');
            $table->string('userRole')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
