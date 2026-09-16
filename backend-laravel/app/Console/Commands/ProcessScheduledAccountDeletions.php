<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\ArchivedRecord;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ProcessScheduledAccountDeletions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:process-deletions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Permanently delete accounts that have reached their 7-day expiration deadline.';

    /**
     * Execute the console command.
     */
    /**
     * Safely output line message if CLI output is available.
     */
    protected function writeLine(string $message): void
    {
        if ($this->output) {
            $this->line($message);
        }
    }

    /**
     * Safely output info message if CLI output is available.
     */
    protected function writeInfo(string $message): void
    {
        if ($this->output) {
            $this->info($message);
        }
    }

    /**
     * Safely output error message if CLI output is available.
     */
    protected function writeError(string $message): void
    {
        if ($this->output) {
            $this->error($message);
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = now();
        $this->writeInfo("Checking for expired pending deletion accounts at {$now}...");

        // Query all accounts in pending_deletion status whose 7-day recovery period has expired
        $expiredUsers = User::withTrashed()
            ->where(function ($query) {
                $query->where('status', 'pending_deletion')
                    ->orWhereNotNull('deletion_scheduled_at');
            })
            ->whereNotNull('permanent_deletion_at')
            ->where('permanent_deletion_at', '<=', now())
            ->get();

        if ($expiredUsers->isEmpty()) {
            $this->writeInfo('No expired accounts to process.');
            return 0;
        }

        $this->writeInfo("Found {$expiredUsers->count()} account(s) ready for permanent deletion.");

        foreach ($expiredUsers as $user) {
            $this->permanentlyDeleteAccount($user);
        }

        $this->writeInfo('Permanent deletion process completed.');
        return 0;
    }

    /**
     * Safely and permanently delete a single expired user account.
     */
    public function permanentlyDeleteAccount(User $user): void
    {
        $userId = $user->id;
        $userEmail = $user->email;
        $userName = $user->name;
        $role = $user->role;

        $this->writeLine("Permanently deleting {$role} account: {$userName} ({$userEmail}) [ID: {$userId}]...");

        try {
            DB::beginTransaction();

            // 1. Comprehensive Final Archive Snapshot
            try {
                ArchivedRecord::archive(
                    $role === 'seller' ? 'seller' : 'customer',
                    $user,
                    '7-day recovery period expired. Permanent automated deletion.'
                );
            } catch (\Throwable $e) {
                Log::warning("Archiving failed during permanent deletion for {$userId}: " . $e->getMessage());
            }

            // 2. Role-specific dependent records cleanup
            if ($role === 'seller') {
                // Delete seller's catalog products & associated variants/images
                $products = Product::where('sellerId', $userId)->get();
                foreach ($products as $prod) {
                    // Delete product images from disk
                    if ($prod->images && is_array($prod->images)) {
                        foreach ($prod->images as $img) {
                            if ($img && !str_starts_with($img, 'http') && !str_starts_with($img, '/uploads/')) {
                                Storage::disk('public')->delete($img);
                            }
                        }
                    }
                    if ($prod->featuredImage && !str_starts_with($prod->featuredImage, 'http') && !str_starts_with($prod->featuredImage, '/uploads/')) {
                        Storage::disk('public')->delete($prod->featuredImage);
                    }
                    if (method_exists($prod, 'variants')) {
                        $prod->variants()->delete();
                    }
                    $prod->delete();
                }
            } else {
                // Customer cleanup
                // Delete reviews written by this customer
                Review::where('customerId', $userId)->delete();

                // Delete saved delivery addresses
                Address::where('userId', $userId)->delete();

                // Delete cart items if table exists
                if (Schema::hasTable('cart_items')) {
                    CartItem::where('userId', $userId)->delete();
                }
            }

            // 3. Clean up active sessions & tokens
            if (Schema::hasTable('personal_access_tokens') && method_exists($user, 'tokens')) {
                $user->tokens()->delete();
            }
            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $userId)->delete();
            }

            // 4. Force Delete the User record from the database
            // This permanently removes the row so the email address becomes 100% available for new registration
            $user->forceDelete();

            DB::commit();

            Log::info("Account permanently deleted: {$userEmail} (ID: {$userId}, Role: {$role}).");
            $this->writeInfo("✓ Successfully permanently deleted {$userEmail}.");
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Failed to permanently delete account {$userId}: " . $e->getMessage());
            $this->writeError("✗ Failed to delete {$userEmail}: " . $e->getMessage());
        }
    }
}
