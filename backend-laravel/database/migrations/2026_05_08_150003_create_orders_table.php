<?php
/* Orders Migration */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customerId');
            $table->uuid('sellerId');
            $table->foreign('customerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
            $table->decimal('totalAmount', 10, 2);
            $table->string('status')->default('pending');
            $table->string('paymentMethod')->default('GCash');
            $table->string('paymentReference')->nullable();
            $table->string('paymentProof')->nullable();
            $table->string('paymentStatus')->default('pending');
            $table->json('shippingAddress');
            $table->text('cancellationReason')->nullable();
            $table->string('visitorSessionId')->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('orderId');
            $table->uuid('productId');
            $table->foreign('orderId')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('productId')->references('id')->on('products')->onDelete('cascade');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->string('size')->nullable();
            $table->string('variation')->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
