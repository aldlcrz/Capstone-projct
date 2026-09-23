<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. shipping_providers
        Schema::create('shipping_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->string('logo_path', 255)->nullable();
            $table->unsignedInteger('default_volumetric_divisor')->default(3500);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. shipping_zones
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('code', 50)->unique();
            $table->timestamps();
        });

        // 3. shipping_zone_areas
        Schema::create('shipping_zone_areas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('zone_id');
            $table->string('postal_code', 10)->nullable()->index();
            $table->string('postal_code_prefix', 10)->nullable()->index();
            $table->string('province', 100)->index();
            $table->string('city', 100)->nullable()->index();
            $table->string('barangay', 100)->nullable()->index();
            $table->timestamps();

            $table->foreign('zone_id')->references('id')->on('shipping_zones')->onDelete('cascade');
        });

        // 4. seller_shipping_providers
        Schema::create('seller_shipping_providers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('seller_id');
            $table->uuid('provider_id');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->foreign('seller_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('shipping_providers')->onDelete('cascade');
            $table->unique(['seller_id', 'provider_id'], 'seller_provider_unique');
        });

        // 5. shipping_rates
        Schema::create('shipping_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('provider_id');
            $table->uuid('origin_zone_id');
            $table->uuid('destination_zone_id');
            $table->decimal('min_weight', 8, 2)->default(0.00);
            $table->decimal('max_weight', 8, 2)->nullable();
            $table->decimal('base_rate', 10, 2);
            $table->decimal('additional_weight_rate', 10, 2)->default(0.00);
            $table->unsignedInteger('volumetric_divisor')->nullable();
            $table->unsignedInteger('estimated_days_min')->default(2);
            $table->unsignedInteger('estimated_days_max')->default(4);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('provider_id')->references('id')->on('shipping_providers')->onDelete('cascade');
            $table->foreign('origin_zone_id')->references('id')->on('shipping_zones')->onDelete('cascade');
            $table->foreign('destination_zone_id')->references('id')->on('shipping_zones')->onDelete('cascade');
            $table->index(['provider_id', 'origin_zone_id', 'destination_zone_id', 'is_active'], 'ship_rate_lookup_idx');
        });

        // 6. order_shipping
        Schema::create('order_shipping', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->unique();
            $table->uuid('provider_id');
            $table->string('provider_name', 100);
            $table->uuid('shipping_rate_id')->nullable();
            $table->uuid('origin_zone_id')->nullable();
            $table->string('origin_zone_name', 100);
            $table->uuid('destination_zone_id')->nullable();
            $table->string('destination_zone_name', 100);
            $table->decimal('actual_weight', 8, 2);
            $table->decimal('volumetric_weight', 8, 2);
            $table->decimal('chargeable_weight', 8, 2);
            $table->decimal('rate_base_snapshot', 10, 2);
            $table->decimal('additional_weight_rate_snapshot', 10, 2)->default(0.00);
            $table->unsignedInteger('volumetric_divisor_snapshot');
            $table->decimal('shipping_fee', 10, 2);
            $table->unsignedInteger('estimated_days_min');
            $table->unsignedInteger('estimated_days_max');
            $table->string('tracking_number', 100)->nullable();
            $table->string('shipping_status', 50)->default('Pending');
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('provider_id')->references('id')->on('shipping_providers');
            $table->foreign('shipping_rate_id')->references('id')->on('shipping_rates')->nullOnDelete();
        });

        // 7. Update products table: add package specs & make shippingFee nullable
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('package_weight_per_unit', 8, 2)->default(0.00)->after('stock');
            $table->decimal('package_length_per_unit', 8, 2)->default(0.00)->after('package_weight_per_unit');
            $table->decimal('package_width_per_unit', 8, 2)->default(0.00)->after('package_length_per_unit');
            $table->decimal('package_height_per_unit', 8, 2)->default(0.00)->after('package_width_per_unit');
            $table->unsignedInteger('handling_days')->default(2)->after('package_height_per_unit');
            $table->decimal('shippingFee', 10, 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'package_weight_per_unit',
                'package_length_per_unit',
                'package_width_per_unit',
                'package_height_per_unit',
                'handling_days',
            ]);
            $table->decimal('shippingFee', 10, 2)->default(0)->change();
        });

        Schema::dropIfExists('order_shipping');
        Schema::dropIfExists('shipping_rates');
        Schema::dropIfExists('seller_shipping_providers');
        Schema::dropIfExists('shipping_zone_areas');
        Schema::dropIfExists('shipping_zones');
        Schema::dropIfExists('shipping_providers');
    }
};
