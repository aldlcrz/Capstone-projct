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
        Schema::create('seller_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->uuid('userId')->collation('utf8mb4_bin');
            } else {
                $table->uuid('userId');
            }
            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, active, rejected, expired
            $table->string('planName');
            $table->decimal('amount', 10, 2);
            $table->string('paymentMethod');
            $table->string('paymentReference');
            $table->string('paymentProof')->nullable();
            $table->text('rejectionReason')->nullable();
            $table->timestamp('startsAt')->nullable();
            $table->timestamp('endsAt')->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_subscriptions');
    }
};
