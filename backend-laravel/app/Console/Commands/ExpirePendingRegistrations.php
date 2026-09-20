<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpirePendingRegistrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sellers:expire-registrations {--email= : Expire a specific seller registration by email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Expire unverified seller registrations whose registration_expires_at has passed.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $specificEmail = $this->option('email');

        if ($specificEmail) {
            $seller = User::where('role', 'seller')
                ->where('isVerified', false)
                ->where('email', strtolower(trim($specificEmail)))
                ->first();

            if (!$seller) {
                $this->error("No unverified seller found with email: {$specificEmail}");
                return 1;
            }

            $seller->status = 'expired';
            $seller->save();

            $this->info("Successfully expired seller registration for: {$seller->name} ({$seller->email}) - Shop '{$seller->shopName}' is now freed.");
            Log::info("Manual expiration of seller registration: {$seller->email}");
            return 0;
        }

        $staleSellers = User::where('role', 'seller')
            ->where('isVerified', false)
            ->where('status', 'awaiting_email_verification')
            ->where(function ($q) {
                $q->where('registration_expires_at', '<=', now())
                  ->orWhere(function ($sub) {
                      $sub->whereNull('registration_expires_at')
                          ->where('createdAt', '<=', now()->subHours(2));
                  });
            })
            ->get();

        $count = $staleSellers->count();
        if ($count === 0) {
            $this->info('No expired seller registrations found.');
            return 0;
        }

        foreach ($staleSellers as $seller) {
            $seller->status = 'expired';
            $seller->save();
            Log::info("Expired stale seller registration: {$seller->email} (Shop: {$seller->shopName})");
        }

        $this->info("Successfully expired {$count} stale seller registration(s). Shop names have been released.");
        return 0;
    }
}
