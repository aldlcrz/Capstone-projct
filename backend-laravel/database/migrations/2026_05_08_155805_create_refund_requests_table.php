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
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('order_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('order_item_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('seller_id')->constrained('users')->onDelete('cascade');
            $table->enum('reason', ['Damaged Item', 'Wrong Size', 'Other']);
            $table->text('message')->nullable();
            $table->string('video_proof');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Resolved'])->default('Pending');
            $table->text('seller_comment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
