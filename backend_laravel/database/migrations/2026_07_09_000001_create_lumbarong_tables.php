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
        // 1. Categories Table
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->uuid('parentId')->nullable();
            
            $table->foreign('parentId')->references('id')->on('categories')->onDelete('set null');
        });

        // 2. Products Table
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            $table->string('fabric_type')->nullable();
            $table->string('collar_type')->nullable();
            $table->string('artisan_region')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('costPerPiece', 10, 2)->default(0.00)->nullable();
            $table->json('sizes')->nullable();
            $table->json('categories')->nullable();
            $table->json('image')->nullable();
            $table->integer('stock')->default(0);
            $table->decimal('shippingFee', 10, 2)->default(0.00);
            $table->integer('shippingDays')->default(3);
            $table->uuid('sellerId');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejectionReason')->nullable();
            $table->integer('views')->default(0);
            $table->timestamps();

            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
        });

        // 3. Orders Table
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('customerId');
            $table->uuid('sellerId');
            $table->decimal('totalAmount', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'])->default('pending');
            $table->string('paymentMethod')->default('GCash');
            $table->string('paymentReference')->nullable();
            $table->string('paymentProof')->nullable();
            $table->enum('paymentStatus', ['pending', 'paid', 'failed'])->default('pending');
            $table->json('shippingAddress');
            $table->text('cancellationReason')->nullable();
            $table->timestamps();

            $table->foreign('customerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
            $table->index('customerId');
            $table->index('sellerId');
        });

        // 4. Order Items Table
        Schema::create('order_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('orderId');
            $table->uuid('productId');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 10, 2);
            $table->string('size')->nullable();
            $table->string('variation')->nullable();

            $table->foreign('orderId')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('productId')->references('id')->on('products')->onDelete('cascade');
        });

        // 5. Addresses Table
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId');
            $table->string('recipientName');
            $table->string('region')->nullable();
            $table->string('phone');
            $table->string('houseNo');
            $table->string('street');
            $table->string('barangay');
            $table->string('city');
            $table->string('province');
            $table->string('postalCode');
            $table->float('latitude')->nullable();
            $table->float('longitude')->nullable();
            $table->boolean('isDefault')->default(false);
            $table->timestamps();

            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
        });

        // 6. Messages Table
        Schema::create('messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('senderId');
            $table->uuid('receiverId');
            $table->text('content');
            $table->boolean('read')->default(false);
            $table->timestamps();

            $table->foreign('senderId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('receiverId')->references('id')->on('users')->onDelete('cascade');
        });

        // 7. Notifications Table
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('userId');
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['product_approved', 'product_rejected', 'order_update', 'account_verified', 'new_message', 'system'])->default('system');
            $table->string('link')->nullable();
            $table->boolean('isRead')->default(false);
            $table->string('targetRole')->default('customer');
            $table->timestamps();

            $table->foreign('userId')->references('id')->on('users')->onDelete('cascade');
            $table->index(['userId', 'created_at']);
            $table->index(['userId', 'isRead']);
            $table->index(['userId', 'targetRole', 'created_at']);
            $table->index(['userId', 'targetRole', 'isRead']);
        });

        // 8. Refund Requests Table
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('orderId');
            $table->uuid('orderItemId');
            $table->uuid('customerId');
            $table->uuid('sellerId');
            $table->enum('reason', ['Damaged Item', 'Wrong Size', 'Other']);
            $table->text('message')->nullable();
            $table->string('videoProof');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Resolved'])->default('Pending');
            $table->text('sellerComment')->nullable();
            $table->timestamps();

            $table->foreign('orderId')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('orderItemId')->references('id')->on('order_items')->onDelete('cascade');
            $table->foreign('customerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
        });

        // 9. Reports Table
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reporterId');
            $table->uuid('reportedId');
            $table->enum('type', ['CustomerReportingSeller', 'SellerReportingCustomer']);
            $table->uuid('referenceId')->nullable();
            $table->string('reason');
            $table->text('description');
            $table->text('evidence')->nullable(); // JSON string
            $table->enum('status', ['Pending', 'In Review', 'Resolved', 'Dismissed'])->default('Pending');
            $table->text('adminNotes')->nullable();
            $table->enum('actionTaken', ['None', 'Warning', 'Restricted', 'Suspended'])->nullable();
            $table->timestamps();

            $table->foreign('reporterId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('reportedId')->references('id')->on('users')->onDelete('cascade');
        });

        // 10. Reviews Table
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('productId');
            $table->uuid('customerId');
            $table->uuid('orderId')->nullable();
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->text('images')->nullable();
            $table->timestamps();

            $table->foreign('productId')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('orderId')->references('id')->on('orders')->onDelete('set null');
            $table->unique(['productId', 'customerId', 'orderId'], 'unique_order_product_review');
        });

        // 11. Seller Funnel Events Table
        Schema::create('seller_funnel_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('sellerId');
            $table->uuid('productId');
            $table->uuid('customerId')->nullable();
            $table->string('visitorSessionId')->nullable();
            $table->string('ipAddress')->nullable();
            $table->enum('eventType', ['add_to_cart']);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('productId')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('customerId')->references('id')->on('users')->onDelete('set null');
            $table->index('sellerId');
            $table->index('productId');
            $table->index('customerId');
            $table->index('visitorSessionId');
            $table->index('eventType');
            $table->index('created_at');
        });

        // 12. Product Views Table
        Schema::create('product_views', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('productId');
            $table->uuid('sellerId');
            $table->uuid('customerId')->nullable();
            $table->string('visitorSessionId')->nullable();
            $table->string('ipAddress')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('productId')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('sellerId')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customerId')->references('id')->on('users')->onDelete('set null');
            $table->index('productId');
            $table->index('sellerId');
            $table->index('customerId');
            $table->index('visitorSessionId');
            $table->index('ipAddress');
            $table->index('created_at');
        });

        // 13. System Settings Table
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('value');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('product_views');
        Schema::dropIfExists('seller_funnel_events');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
    }
};
