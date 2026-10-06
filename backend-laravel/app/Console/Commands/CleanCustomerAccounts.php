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
            $this->info("No customer accounts found to delete. Only '{$keepEmail}' (or no customers) exist.");
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

                // Clean related addresses
                Address::where('userId', $userId)->delete();

                // Clean related reviews
                Review::where('userId', $userId)->delete();

                // Clean email verification OTPs
                EmailVerification::where('email', $userEmail)->delete();

                // Clean user notifications
                Notification::where('userId', $userId)->delete();

                // Clean customer orders if applicable
                Order::where('userId', $userId)->delete();

                // Permanently force delete the customer user record
                $customer->forceDelete();
                $deletedCount++;
            }

            DB::commit();
            $this->info("\n Successfully deleted {$deletedCount} customer account(s).");
            $this->info("Kept account: {$keepEmail}");
            Log::info("CleanCustomerAccounts: Deleted {$deletedCount} customers, preserved {$keepEmail}");
            return 0;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("Error during customer cleanup: " . $e->getMessage());
            Log::error("CleanCustomerAccounts failed: " . $e->getMessage());
            return 1;
        }
    }
}
