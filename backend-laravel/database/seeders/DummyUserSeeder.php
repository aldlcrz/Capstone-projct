<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DummyUserSeeder extends Seeder
{
    /**
     * Seed the exact 100 dummy users from LumBarong_Dummy_Users.xlsx
     */
    public function run(): void
    {
        $jsonPath = database_path('data_dummy_users.json');

        if (!file_exists($jsonPath)) {
            $rootXlsx = base_path('../LumBarong_Dummy_Users.xlsx');
            if (!file_exists($rootXlsx)) {
                $rootXlsx = base_path('LumBarong_Dummy_Users.xlsx');
            }
            throw new \RuntimeException("Dummy users data file not found at: {$jsonPath}");
        }

        $dummyUsers = json_decode(file_get_contents($jsonPath), true);

        if (!is_array($dummyUsers) || empty($dummyUsers)) {
            throw new \RuntimeException("No dummy users found in {$jsonPath}");
        }

        $defaultPassword = Hash::make('password123');
        $totalUsers = count($dummyUsers);

        foreach ($dummyUsers as $index => $data) {
            $name = (string) ($data['name'] ?? '');
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            if (empty($name) || empty($email)) {
                continue;
            }

            // Distribute registration timestamps across Sept 21 - Sept 30, 2026 with unique hours/minutes/seconds
            $dayOffset = (int) floor(($index / max(1, $totalUsers)) * 10); // 0 to 9 days -> Sept 21 to Sept 30
            $targetDay = 21 + min(9, $dayOffset);
            $hour = 8 + (($index * 2 + 1) % 14); // 8:00 AM to 10:00 PM
            $minute = (($index * 17) + 23) % 60;
            $second = (($index * 29) + 11) % 60;
            $timestamp = \Carbon\Carbon::create(2026, 9, $targetDay, $hour, $minute, $second);

            $user = User::withTrashed()->where('email', $email)->first();

            if (!$user) {
                $user = new User();
                $user->id = (string) Str::uuid();
            }

            // Always ensure exact name, email, active customer status, and verification
            $user->name = $name;
            $user->email = $email;
            $user->role = 'customer';
            $user->status = 'active';
            $user->isVerified = true;
            $user->hasPasswordSet = true;
            $user->email_verified_at = $timestamp;
            $user->createdAt = $timestamp;
            $user->updatedAt = $timestamp;

            if (empty($user->password)) {
                $user->password = $defaultPassword;
            }

            if ($user->trashed()) {
                $user->restore();
            }

            $user->save();
        }
    }
}
