<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipping_providers')) {
            // 1. Store Pickup
            $pickupExists = DB::table('shipping_providers')->where('code', 'store_pickup')->exists();
            if (!$pickupExists) {
                DB::table('shipping_providers')->insert([
                    'id'                         => (string) Str::uuid(),
                    'name'                       => 'Store Pickup (Lumban Artisan Shop)',
                    'code'                       => 'store_pickup',
                    'default_volumetric_divisor' => 3500,
                    'is_active'                  => true,
                    'is_platform_default'        => false,
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }

            // 2. Seller Direct Delivery (Local Rider)
            $directExists = DB::table('shipping_providers')->where('code', 'seller_direct')->exists();
            if (!$directExists) {
                DB::table('shipping_providers')->insert([
                    'id'                         => (string) Str::uuid(),
                    'name'                       => 'Seller Local Direct Delivery',
                    'code'                       => 'seller_direct',
                    'default_volumetric_divisor' => 3500,
                    'is_active'                  => true,
                    'is_platform_default'        => false,
                    'created_at'                 => now(),
                    'updated_at'                 => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('shipping_providers')) {
            DB::table('shipping_providers')->whereIn('code', ['store_pickup', 'seller_direct'])->delete();
        }
    }
};
