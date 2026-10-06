<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\EmailVerification;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Review;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanCustomerAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:clean 
                            {--keep=customer@gmail.com : The email address of the customer to keep} 
                            {--force : Execute deletion without confirmation prompt} 
                            {--dry-run : List customer accounts that would be deleted without deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean all customer accounts from the database except the specified account to keep.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $keepEmail = strtolower(trim($this->option('keep') ?: 'customer@gmail.com'));
        $isDryRun  = (bool) $this->option('dry-run');
        $isForce   = (bool) $this->option('force');

        $this->info("=================================================");
        $this->info(" LumBarong Customer Accounts Cleanup");
        $this->info(" Keep Account: {$keepEmail}");
        $this->info(" Mode: " . ($isDryRun ? "DRY-RUN (Preview Only)" : "EXECUTE"));
        $this->info("=================================================");

        // Fetch all customer accounts excluding seller, admin, and superadmin
        $customers = User::withTrashed()
            ->where(function ($q) {
                $q->whereIn('role', ['customer', 'buyer', 'user'])
                  ->orWhereNull('role');
            })
            ->whereNotIn('role', ['seller', 'admin', 'superadmin'])
            ->whereRaw('LOWER(TRIM(email)) != ?', [$keepEmail])
            ->get();

        if ($customers->isEmpty()) {
            $this->info("No other customer accounts found to delete.");
            $keptUser = User::withTrashed()->where('email', $keepEmail)->first();
            if (!$keptUser) {
                $keptUser = new User();
                $keptUser->forceFill([
                    'id'                => (string) \Illuminate\Support\Str::uuid(),
                    'name'              => 'Heritage Customer',
                    'email'             => $keepEmail,
                    'password'          => \Illuminate\Support\Facades\Hash::make('password123'),
                    'role'              => 'customer',
                    'status'            => 'active',
                    'isVerified'        => true,
                    'email_verified_at' => now(),
                ]);
                $keptUser->save();
                $this->info(" Created active customer account for '{$keepEmail}' (Password: password123).");
            } else {
                $this->info(" Account '{$keepEmail}' already exists and is active.");
            }
            return 0;
        }

        $this->warn("Found {$customers->count()} customer account(s) to remove:");
        foreach ($customers as $c) {
            $this->line(" - ID: {$c->id} | Name: {$c->name} | Email: {$c->email} | Status: {$c->status}");
        }

        if ($isDryRun) {
            $this->info("\n[Dry Run] No data was deleted. Run with --force to execute.");
            return 0;
        }

        if (!$isForce && !$this->confirm("Are you sure you want to PERMANENTLY delete these {$customers->count()} customer accounts?", false)) {
            $this->info("Operation cancelled. No accounts were deleted.");
            return 0;
        }

        $deletedCount = 0;
        DB::beginTransaction();
        try {
            foreach ($customers as $customer) {
                $userId = $customer->id;
                $userEmail = strtolower(trim($customer->email));

                // 1. Clean related delivery addresses
                if (\Illuminate\Support\Facades\Schema::hasTable('addresses')) {
                    Address::where('userId', $userId)->delete();
                }

                // 2. Clean reviews written by this customer (uses customerId)
                if (\Illuminate\Support\Facades\Schema::hasTable('reviews')) {
                    if (\Illuminate\Support\Facades\Schema::hasColumn('reviews', 'customerId')) {
                        Review::where('customerId', $userId)->delete();
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('reviews', 'userId')) {
                        Review::where('userId', $userId)->delete();
                    }
                }

                // 3. Clean email verification OTPs
                if (\Illuminate\Support\Facades\Schema::hasTable('email_verifications')) {
                    EmailVerification::where('email', $userEmail)->delete();
                }

                // 4. Clean user notifications
                if (\Illuminate\Support\Facades\Schema::hasTable('notifications')) {
                    Notification::where('userId', $userId)->delete();
                }

                // 5. Clean customer orders if applicable
                if (\Illuminate\Support\Facades\Schema::hasTable('orders')) {
                    Order::where(function($q) use ($userId) {
                        if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'customerId')) {
                            $q->where('customerId', $userId);
                        }
                        if (\Illuminate\Support\Facades\Schema::hasColumn('orders', 'userId')) {
                            $q->orWhere('userId', $userId);
                        }
                    })->delete();
                }

                // 6. Clean sessions and tokens if applicable
                if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
                    DB::table('sessions')->where('user_id', $userId)->delete();
                }
                if (\Illuminate\Support\Facades\Schema::hasTable('cart_items')) {
                    DB::table('cart_items')->where('userId', $userId)->delete();
                }

                // 7. Permanently force delete the customer user record
                $customer->forceDelete();
                $deletedCount++;
            }

            // 8. Ensure kept customer account exists
            $keptUser = User::withTrashed()->where('email', $keepEmail)->first();
            if (!$keptUser) {
                $keptUser = new User();
                $keptUser->forceFill([
                    'id'                => (string) \Illuminate\Support\Str::uuid(),
                    'name'              => 'Heritage Customer',
                    'email'             => $keepEmail,
                    'password'          => \Illuminate\Support\Facades\Hash::make('password123'),
                    'role'              => 'customer',
                    'status'            => 'active',
                    'isVerified'        => true,
                    'email_verified_at' => now(),
                ]);
                $keptUser->save();
                $this->info(" Created active customer account for '{$keepEmail}' with default password 'password123'.");
            } else {
                if ($keptUser->trashed()) {
                    $keptUser->restore();
                }
                $keptUser->role = 'customer';
                $keptUser->status = 'active';
                $keptUser->isVerified = true;
                $keptUser->email_verified_at = $keptUser->email_verified_at ?: now();
                $keptUser->save();
                $this->info(" Active account confirmed for: {$keepEmail}");
            }

            DB::commit();
            $this->info("\n Customer cleanup complete.");
            Log::info("CleanCustomerAccounts: Deleted {$deletedCount} customers, preserved/created {$keepEmail}");
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error during customer cleanup: " . $e->getMessage());
            Log::error("CleanCustomerAccounts failed: " . $e->getMessage());
            return 1;
        }
    }
}
