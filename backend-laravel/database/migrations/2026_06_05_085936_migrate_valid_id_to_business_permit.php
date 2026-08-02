<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Copy validId values to businessPermit if businessPermit is null
        if (Schema::hasColumn('users', 'validId') && Schema::hasColumn('users', 'businessPermit')) {
            DB::table('users')
                ->whereNull('businessPermit')
                ->whereNotNull('validId')
                ->update([
                    'businessPermit' => DB::raw('validId')
                ]);
        }

        // Drop validId column
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'validId')) {
                $table->dropColumn('validId');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-create validId column
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'validId')) {
                $table->string('validId')->nullable();
            }
        });

        // Copy businessPermit values back to validId
        if (Schema::hasColumn('users', 'validId') && Schema::hasColumn('users', 'businessPermit')) {
            DB::table('users')
                ->whereNull('validId')
                ->whereNotNull('businessPermit')
                ->update([
                    'validId' => DB::raw('businessPermit')
                ]);
        }
    }
};
