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
            // 1. Check if SPX Express exists in shipping_providers
            $spx = DB::table('shipping_providers')->where('code', 'spx')->orWhere('name', 'SPX Express')->first();
            $flash = DB::table('shipping_providers')->where('code', 'flash')->orWhere('name', 'Flash Express')->first();

            if ($spx && !$flash) {
                // Rename SPX Express to Flash Express
                DB::table('shipping_providers')->where('id', $spx->id)->update([
                    'name' => 'Flash Express',
                    'code' => 'flash',
                    'updated_at' => now(),
                ]);
            } elseif (!$flash) {
                // Insert Flash Express
                $flashId = (string) Str::uuid();
                DB::table('shipping_providers')->insert([
                    'id' => $flashId,
                    'name' => 'Flash Express',
                    'code' => 'flash',
                    'default_volumetric_divisor' => 3500,
                    'is_active' => true,
                    'is_platform_default' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if ($spx) {
                    // Re-link rates from spx to flash
                    DB::table('shipping_rates')->where('provider_id', $spx->id)->update([
                        'provider_id' => $flashId,
                    ]);
                    DB::table('shipping_providers')->where('id', $spx->id)->delete();
                }
            } elseif ($spx && $flash) {
                // Re-link rates from spx to flash and delete spx
                DB::table('shipping_rates')->where('provider_id', $spx->id)->update([
                    'provider_id' => $flash->id,
                ]);
                DB::table('shipping_providers')->where('id', $spx->id)->delete();
            }

            // Ensure J&T Express and LBC Express exist and are active
            DB::table('shipping_providers')->where('code', 'jnt')->update(['is_active' => true]);
            DB::table('shipping_providers')->where('code', 'lbc')->update(['is_active' => true]);
            DB::table('shipping_providers')->where('code', 'flash')->update(['is_active' => true]);

        }
    }

    public function down(): void
    {
        // No-op rollback
    }
};
