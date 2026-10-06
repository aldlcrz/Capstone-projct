<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ImportDummyUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:import-dummy {--verify-only : Verify dummy users against source without importing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and verify the exact 100 dummy users from LumBarong_Dummy_Users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsonPath = database_path('data_dummy_users.json');

        if (!file_exists($jsonPath)) {
            $this->error("Dummy users source data not found at: {$jsonPath}");
            return 1;
        }

        $dummyUsers = json_decode(file_get_contents($jsonPath), true);
        $totalCount = count($dummyUsers);

        $this->info("=================================================");
        $this->info(" LumBarong Dummy Users Import & Verification");
        $this->info(" Total Source Records: {$totalCount}");
        $this->info("=================================================");

        if ($this->option('verify-only')) {
            $matched = 0;
            $missing = 0;
            $mismatched = 0;

            foreach ($dummyUsers as $data) {
                $expectedName = (string) $data['name'];
                $expectedEmail = strtolower(trim((string) $data['email']));

                $user = User::withTrashed()->where('email', $expectedEmail)->first();

                if (!$user) {
                    $this->warn(" [MISSING] {$expectedEmail} ({$expectedName})");
                    $missing++;
                } elseif ($user->name !== $expectedName) {
                    $this->error(" [MISMATCH] {$expectedEmail}: Expected '{$expectedName}', found '{$user->name}'");
                    $mismatched++;
                } else {
                    $matched++;
                }
            }

            $this->info("Verification Complete: {$matched} matched, {$missing} missing, {$mismatched} mismatched.");
            return ($missing === 0 && $mismatched === 0) ? 0 : 1;
        }

        $defaultPassword = Hash::make('password123');
        $created = 0;
        $updated = 0;

        foreach ($dummyUsers as $data) {
            $name = (string) ($data['name'] ?? '');
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            if (empty($name) || empty($email)) {
                continue;
            }

            $user = User::withTrashed()->where('email', $email)->first();

            if (!$user) {
                $user = new User();
                $user->id = (string) Str::uuid();
                $created++;
            } else {
                $updated++;
            }

            $user->name = $name;
            $user->email = $email;
            $user->role = 'customer';
            $user->status = 'active';
            $user->isVerified = true;
            $user->hasPasswordSet = true;
            $user->email_verified_at = $user->email_verified_at ?? now();

            if (empty($user->password)) {
                $user->password = $defaultPassword;
            }

            if ($user->trashed()) {
                $user->restore();
            }

            $user->save();
        }

        $this->info(" Successfully processed {$totalCount} dummy users ({$created} created, {$updated} updated).");
        return 0;
    }
}
