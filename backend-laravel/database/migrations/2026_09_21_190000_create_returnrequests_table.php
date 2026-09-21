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
        if (!Schema::hasTable('returnrequests')) {
            Schema::create('returnrequests', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('orderId');
                $table->foreign('orderId')->references('id')->on('orders')->onDelete('cascade');
                $table->text('reason');
                $table->longText('proofImages')->nullable();
                $table->string('status')->default('Pending');
                $table->text('adminComment')->nullable();
                $table->timestamp('createdAt')->nullable();
                $table->timestamp('updatedAt')->nullable();

                $table->index('orderId');
                $table->index('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('returnrequests');
    }
};
