<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $customers = User::where('role', 'customer')
            ->orderBy('id', 'asc')
            ->get();

        $total = $customers->count();
        if ($total === 0) {
            return;
        }

        foreach ($customers as $index => $customer) {
            $dayOffset = (int) floor(($index / max(1, $total)) * 10); // 0 to 9 days -> Sept 21 to Sept 30
            $targetDay = 21 + min(9, $dayOffset);
            $hour = 8 + (($index * 2 + 1) % 14); // 8:00 AM to 10:00 PM
            $minute = (($index * 17) + 23) % 60;
            $second = (($index * 29) + 11) % 60;
            $timestamp = Carbon::create(2026, 9, $targetDay, $hour, $minute, $second)->format('Y-m-d H:i:s');

            DB::table('users')
                ->where('id', $customer->id)
                ->update([
                    'createdAt' => $timestamp,
                    'updatedAt' => $timestamp,
                    'email_verified_at' => $timestamp,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive
    }
};
