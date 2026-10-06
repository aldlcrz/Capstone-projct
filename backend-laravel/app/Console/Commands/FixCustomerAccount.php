<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class FixCustomerAccount extends Command
{
    protected $signature = 'customers:fix
                            {--email=customer@gmail.com : The email to inspect and fix}
                            {--fix : Actually apply the fix (default is diagnose-only)}';

    protected $description = 'Diagnose and fix the visibility of a customer account in the Super Admin panel.';

    public function handle()
    {
        $email = strtolower(trim($this->option('email') ?: 'customer@gmail.com'));
        $fix   = (bool) $this->option('fix');

        $this->info("=================================================");
        $this->info(" LumBarong - Customer Account Diagnostic & Fix");
        $this->info(" Target: {$email}");
        $this->info(" Mode: " . ($fix ? "FIX" : "DIAGNOSE ONLY"));
        $this->info("=================================================");

        // 1. Raw DB row (bypasses all Eloquent scopes incl. SoftDeletes)
        $raw = DB::table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();

        if (!$raw) {
            $this->error("No row found in users table for: {$email}");

            if ($fix) {
                $this->warn("Creating fresh account...");
                $user = new User();
                $user->forceFill([
                    'id'                => (string) Str::uuid(),
                    'name'              => 'Heritage Customer',
                    'email'             => $email,
                    'password'          => Hash::make('password123'),
                    'role'              => 'customer',
                    'status'            => 'active',
                    'isVerified'        => true,
                    'email_verified_at' => now(),
                    'deleted_at'        => null,
                ]);
                $user->save();
                $this->info("Account created. Login: {$email} / password123");
            }
            return 0;
        }

        // 2. Show raw values
        $this->line("\nRaw DB row:");
        $this->line("  id         : {$raw->id}");
        $this->line("  name       : {$raw->name}");
        $this->line("  email      : {$raw->email}");
        $this->line("  role       : " . ($raw->role ?? 'NULL'));
        $this->line("  status     : " . ($raw->status ?? 'NULL'));
        $this->line("  isVerified : " . ($raw->isVerified ?? 'NULL'));
        $this->line("  deleted_at : " . ($raw->deleted_at ?? 'NULL (not soft-deleted)'));
        $this->line("  createdAt  : " . ($raw->createdAt ?? 'NULL'));

        // 3. Check controller query visibility
        $found = User::where(function ($q) {
            $q->whereIn('role', ['customer', 'buyer', 'user'])
              ->orWhereNull('role');
        })
        ->whereNotIn('role', ['seller', 'admin', 'superadmin'])
        ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
        ->first();

        if ($found) {
            $this->info("\nAccount IS visible in Super Admin panel.");
            $this->info("Try searching for 'customer' in the search box on /superadmin/customers.");
            return 0;
        }

        // 4. Diagnose
        $this->warn("\nAccount is NOT returned by the customers() controller query!");
        $problems = [];

        if ($raw->deleted_at !== null) {
            $problems[] = "deleted_at is SET ({$raw->deleted_at}) - soft-deleted, invisible to UI";
        }
        if ($raw->role !== null && !in_array($raw->role, ['customer', 'buyer', 'user'])) {
            $problems[] = "role='{$raw->role}' is not in [customer,buyer,user] - excluded";
        }
        if (empty($problems)) {
            $problems[] = "Unknown issue - role/deleted_at appear OK. Possible MySQL charset issue.";
        }

        foreach ($problems as $p) {
            $this->error("  PROBLEM: {$p}");
        }

        // 5. Apply fix
        if ($fix) {
            $this->line("\nApplying fix...");
            $user = User::withTrashed()->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
            if (!$user) {
                $this->error("Cannot find user even with withTrashed. Aborting.");
                return 1;
            }
            if ($user->trashed()) {
                $user->restore();
                $this->info("Account restored from soft-delete (deleted_at cleared).");
            }
            $user->role       = 'customer';
            $user->status     = 'active';
            $user->isVerified = true;
            $user->email_verified_at = $user->email_verified_at ?: now();
            $user->save();
            $this->info("Fix applied: role=customer, status=active, isVerified=true, deleted_at=NULL");
            $this->info("Refresh /superadmin/customers - the account should now appear.");
        } else {
            $this->line("\nRun with --fix to repair:");
            $this->line("  php artisan customers:fix --fix");
        }

        return 0;
    }
}
