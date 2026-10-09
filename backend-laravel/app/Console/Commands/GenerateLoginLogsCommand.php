<?php

namespace App\Console\Commands;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateLoginLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'logs:generate-logins {--days=7 : Number of past days to distribute logins over} {--clear : Clear existing laravel.log before generating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates realistic authentication login audit logs in storage/logs/laravel.log for all customers, sellers, admins, and superadmin';

    /**
     * Sample realistic IP addresses from major Philippine ISPs (PLDT, Globe, Smart, Converge, DITO).
     */
    protected array $philippineIps = [
        '112.198.42.88',
        '112.204.18.92',
        '119.95.210.45',
        '120.29.88.14',
        '175.176.64.12',
        '136.158.42.10',
        '180.191.134.56',
        '124.106.220.73',
        '49.144.178.29',
        '110.54.240.115',
        '152.32.105.62',
        '103.100.136.44',
        '122.54.190.81',
        '114.108.214.93',
    ];

    /**
     * Sample realistic user agent strings.
     */
    protected array $userAgents = [
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 14; SM-S928B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.6312.80 Mobile Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36 Edg/123.0.0.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_4) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15',
        'Mozilla/5.0 (Linux; Android 13; Redmi Note 12) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.6261.119 Mobile Safari/537.36',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');
        $logDir = dirname($logPath);

        if (!File::exists($logDir)) {
            File::makeDirectory($logDir, 0755, true);
        }

        if ($this->option('clear') && File::exists($logPath)) {
            File::put($logPath, '');
            $this->info("Cleared existing logs at {$logPath}");
        }

        $days = (int) $this->option('days') ?: 7;

        // Fetch all users or provide realistic fallbacks
        try {
            $users = User::all();
        } catch (\Throwable $e) {
            $users = collect();
        }

        if ($users->isEmpty()) {
            $users = collect([
                (object)['id' => 'usr_sa01', 'name' => 'Super Administrator', 'email' => 'superadmin@lumbarong.shop', 'role' => 'superadmin'],
                (object)['id' => 'usr_ad01', 'name' => 'Platform Admin', 'email' => 'admin@lumbarong.shop', 'role' => 'admin'],
                (object)['id' => 'usr_ar01', 'name' => 'Lumban Embroidery Artisan', 'email' => 'artisan@lumbarong.shop', 'role' => 'seller'],
                (object)['id' => 'usr_cu01', 'name' => 'Maria Clara Santos', 'email' => 'maria.santos@gmail.com', 'role' => 'customer'],
                (object)['id' => 'usr_cu02', 'name' => 'Juan Dela Cruz', 'email' => 'juan.delacruz@yahoo.com', 'role' => 'customer'],
                (object)['id' => 'usr_cu03', 'name' => 'Ana Teresa Reyes', 'email' => 'ana.reyes@gmail.com', 'role' => 'customer'],
                (object)['id' => 'usr_cu04', 'name' => 'Carlos Miguel Fernandez', 'email' => 'carlos.fernandez@gmail.com', 'role' => 'customer'],
                (object)['id' => 'usr_cu05', 'name' => 'Beatriz Alonzo', 'email' => 'beatriz.alonzo@gmail.com', 'role' => 'customer'],
            ]);
        }

        $logEntries = [];
        $now = Carbon::now();

        foreach ($users as $user) {
            $userName = $user->name ?: 'User';
            $userEmail = $user->email ?: 'user@lumbarong.shop';
            $userRole = $user->role ?: 'customer';
            $userId = $user->id;

            // Generate 1 to 4 staggered past login events for each user
            $loginCount = rand(1, 4);

            for ($i = 0; $i < $loginCount; $i++) {
                // Pick a random time in past $days days
                $minutesAgo = rand(15, $days * 24 * 60);
                $timestamp = (clone $now)->subMinutes($minutesAgo);

                // Realistic active hours: 07:00 to 23:00
                $hour = rand(7, 22);
                $minute = rand(0, 59);
                $second = rand(0, 59);
                $timestamp->setTime($hour, $minute, $second);

                if ($timestamp->gt($now)) {
                    $timestamp = (clone $now)->subMinutes(rand(10, 180));
                }

                $ip = $this->philippineIps[array_rand($this->philippineIps)];
                $ua = $this->userAgents[array_rand($this->userAgents)];

                $formattedTimestamp = $timestamp->format('Y-m-d H:i:s');
                $env = app()->environment() ?: 'production';

                $line = "[{$formattedTimestamp}] {$env}.INFO: AUTH_LOGIN_SUCCESS: User \"{$userName}\" (ID: {$userId}, Role: {$userRole}, Email: {$userEmail}) logged in successfully from IP {$ip} [Device: {$ua}].";

                $logEntries[] = [
                    'time' => $timestamp->timestamp,
                    'line' => $line,
                ];
            }
        }

        // Sort chronologically from oldest to newest
        usort($logEntries, fn($a, $b) => $a['time'] <=> $b['time']);

        $content = implode("\n", array_column($logEntries, 'line')) . "\n";
        File::append($logPath, $content);

        $this->info("Successfully generated " . count($logEntries) . " login audit log entries for " . $users->count() . " users in storage/logs/laravel.log.");

        return 0;
    }
}
