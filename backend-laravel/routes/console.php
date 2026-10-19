<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Illuminate\Support\Facades\Schedule::command('commission:send-reminders')->daily();
Illuminate\Support\Facades\Schedule::command('accounts:process-deletions')->hourly();
Illuminate\Support\Facades\Schedule::command('products:audit-images')->dailyAt('03:00');

Artisan::command('mail:test {email}', function ($email) {
    try {
        $code = '123456';
        $mailable = new \App\Mail\VerificationCodeMail('Tester', $code);
        \Illuminate\Support\Facades\Mail::to($email)->send($mailable);
        $this->info("SUCCESS: Email sent to {$email}");
    } catch (\Throwable $e) {
        $this->error("ERROR: " . $e->getMessage());
    }
});

