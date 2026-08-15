<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add paymentRejectionReason if not present
        if (!Schema::hasColumn('orders', 'paymentRejectionReason')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->text('paymentRejectionReason')->nullable()->after('cancellationReason');
            });
        }

        // 2. Safe Deduplication for existing records before adding unique index
        $duplicates = DB::table('orders')
            ->select('paymentReference')
            ->whereNotNull('paymentReference')
            ->where('paymentReference', '!=', '')
            ->groupBy('paymentReference')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('paymentReference');

        foreach ($duplicates as $ref) {
            $orderIds = DB::table('orders')
                ->where('paymentReference', $ref)
                ->orderBy('createdAt', 'asc')
                ->pluck('id');

            // Skip the first one, suffix duplicates with short id
            $first = true;
            foreach ($orderIds as $id) {
                if ($first) {
                    $first = false;
                    continue;
                }
                $shortId = substr(str_replace('-', '', (string)$id), 0, 6);
                DB::table('orders')
                    ->where('id', $id)
                    ->update(['paymentReference' => "{$ref}_dup_{$shortId}"]);
            }
        }

        // 3. Add unique index to paymentReference
        Schema::table('orders', function (Blueprint $table) {
            $table->unique('paymentReference', 'orders_payment_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'paymentRejectionReason')) {
                $table->dropColumn('paymentRejectionReason');
            }
            $table->dropUnique('orders_payment_reference_unique');
        });
    }
};
