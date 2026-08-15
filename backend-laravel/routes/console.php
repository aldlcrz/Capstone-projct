<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Illuminate\Support\Facades\Schedule::command('commission:send-reminders')->daily();

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

