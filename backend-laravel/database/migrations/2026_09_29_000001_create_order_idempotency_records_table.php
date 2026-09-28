<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('order_idempotency_records')) {
            Schema::create('order_idempotency_records', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->uuid('customer_id')->index();
                $table->string('idempotency_key', 128);
                $table->string('request_hash', 64);
                $table->uuid('order_id')->nullable()->index();
                $table->string('status', 32)->default('processing'); // processing, completed, failed
                $table->timestamps();

                $table->unique(['customer_id', 'idempotency_key'], 'uniq_cust_idempotency');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('order_idempotency_records');
    }
};
