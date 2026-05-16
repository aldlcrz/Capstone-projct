<?php
/* Product Migration */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            $table->string('fabric_type')->nullable();
            $table->string('collar_type')->nullable();
            $table->string('artisan_region')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('costPerPiece', 10, 2)->default(0);
            $table->json('sizes')->nullable();
            $table->json('categories')->nullable();
            $table->json('image')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('shippingFee', 10, 2)->default(0);
            $table->integer('shippingDays')->default(3);
            $table->uuid('sellerId');
            $table->uuid('CategoryId')->nullable();
            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('CategoryId')->references('id')->on('categories')->onDelete('set null');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejectionReason')->nullable();
            $table->integer('views')->default(0);
            $table->timestamp('createdAt')->useCurrent();
            $table->timestamp('updatedAt')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
