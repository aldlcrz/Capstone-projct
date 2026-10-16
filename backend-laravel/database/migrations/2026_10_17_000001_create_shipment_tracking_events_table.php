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
        if (!Schema::hasTable('shipment_tracking_events')) {
            Schema::create('shipment_tracking_events', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->char('order_id', 36)->index();
                $table->char('order_shipping_id', 36)->nullable()->index();
                $table->string('courier_code', 50)->index();
                $table->string('tracking_number', 100)->index();
                $table->string('checkpoint_status', 50)->index(); // 'registered', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed_delivery', 'returned'
                $table->string('raw_status', 100)->nullable();
                $table->string('location', 255)->nullable();
                $table->text('description');
                $table->dateTime('occurred_at')->index();
                $table->string('event_hash', 64)->unique();
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->index(['order_id', 'occurred_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_tracking_events');
    }
};
