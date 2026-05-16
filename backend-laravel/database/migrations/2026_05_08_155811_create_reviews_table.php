<?php
/* Reviews Migration */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('productId');
            $table->uuid('customerId');
            $table->uuid('orderId')->nullable();
            $table->foreign('productId')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('orderId')->references('id')->on('orders')->onDelete('set null');
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->text('images')->nullable();
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
            
            $table->unique(['productId', 'customerId', 'orderId'], 'unique_order_product_review');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
