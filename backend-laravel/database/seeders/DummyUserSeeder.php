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
            }

            // Always ensure exact name, email, active customer status, and verification
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
    }
}
