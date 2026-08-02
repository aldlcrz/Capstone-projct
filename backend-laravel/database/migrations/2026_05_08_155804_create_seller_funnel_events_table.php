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
        Schema::create('seller_funnel_events', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('seller_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('product_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('customer_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('visitor_session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->enum('event_type', ['add_to_cart']);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seller_funnel_events');
    }
};
