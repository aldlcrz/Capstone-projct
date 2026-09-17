<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailVerification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailNotificationService
{
    /**
     * Generate or overwrite a 6-digit verification code.
     * Preserves single verification record per email/type to maintain rate limit history.
     */
    public static function createVerificationCode(string $email, string $type = 'registration'): EmailVerification
    {
        $normalizedEmail = strtolower(trim($email));
        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // DEV / Testing / Demo OTP logging (gated to non-production / debug)
        if (app()->environment('local', 'testing') || config('app.debug') || env('OTP_DEBUG', false)) {
            Log::info("[DEV/DEMO OTP] Verification code for {$normalizedEmail} ({$type}): {$code}");
        }

        $existing = EmailVerification::where('email', $normalizedEmail)
            ->where('type', $type)
            ->first();

        if ($existing) {
            // Check rolling 1-hour window
            $isWithinWindow = $existing->resend_window_started_at &&
                $existing->resend_window_started_at->gt(Carbon::now()->subHour());

            $windowStartedAt = $isWithinWindow
                ? $existing->resend_window_started_at
                : Carbon::now();

            $newResendCount = $isWithinWindow
                ? ((int) $existing->resend_count + 1)
                : 1;

            $existing->update([
                'code'                     => $code,
                'expires_at'               => Carbon::now()->addMinutes(5),
                'last_sent_at'             => Carbon::now(),
                'resend_count'             => $newResendCount,
                'resend_window_started_at' => $windowStartedAt,
                'failed_attempts'          => 0,
            ]);

            return $existing->fresh();
        }

        return EmailVerification::create([
            'email'                    => $normalizedEmail,
            'code'                     => $code,
            'type'                     => $type,
            'expires_at'               => Carbon::now()->addMinutes(5),
            'resend_count'             => 0,
            'resend_window_started_at' => Carbon::now(),
            'last_sent_at'             => Carbon::now(),
            'failed_attempts'          => 0,
        ]);
    }

    /**
     * Validate a verification code with 5-attempt maximum limit.
     */
    public static function verifyCode(string $email, string $code, string $type = 'registration'): bool
    {
        $verification = EmailVerification::where('email', strtolower($email))
            ->where('type', $type)
            ->first();

        if (!$verification) {
            return false;
        }

        if ($verification->isExpired()) {
            return false;
        }

        // Lockout if already reached 5 failed attempts
        if ((int) $verification->failed_attempts >= 5) {
            EmailVerification::where('email', strtolower($email))->where('type', $type)->delete();
            return false;
        }

        $inputCode = trim($code);
        $matches = ($verification->code === $inputCode) || \Illuminate\Support\Facades\Hash::check($inputCode, $verification->code);

        if (!$matches) {
            EmailVerification::where('email', strtolower($email))->where('type', $type)->increment('failed_attempts');
            if (((int) $verification->failed_attempts + 1) >= 5) {
                EmailVerification::where('email', strtolower($email))->where('type', $type)->delete();
            }
            return false;
        }

        return true;
    }

    /**
     * Consume (delete) code after successful verification.
     */
    public static function consumeCode(string $email, string $type = 'registration'): void
    {
        EmailVerification::where('email', strtolower($email))
            ->where('type', $type)
            ->delete();
    }

    /**
     * Send email notification and record in email_logs.
     */
    public static function sendNotification(
        string $recipientEmail,
        Mailable $mailable,
        string $notificationType,
        ?string $userId = null,
        ?string $relatedType = null,
        ?string $relatedId = null
    ): bool {
        $recipientEmail = strtolower(trim($recipientEmail));

        // Find user if not explicitly passed
        if (!$userId) {
            $user = User::where('email', $recipientEmail)->first();
            $userId = $user?->id;
        }

        $subject = property_exists($mailable, 'subject') && $mailable->subject
            ? $mailable->subject
            : $notificationType;

        $deliveryStatus = 'sent';
        $errorMessage = null;

        try {
            Mail::to($recipientEmail)->send($mailable);
        } catch (\Throwable $e) {
            Log::error("Failed to send email [{$notificationType}] to {$recipientEmail}: " . $e->getMessage());
            $deliveryStatus = 'failed';
            $errorMessage = substr($e->getMessage(), 0, 1000);
        }

        try {
            EmailLog::create([
                'recipient_email'   => $recipientEmail,
                'user_id'           => $userId,
                'notification_type' => $notificationType,
                'subject'           => $subject,
                'delivery_status'   => $deliveryStatus,
                'error_message'     => $errorMessage,
                'related_type'      => $relatedType,
                'related_id'        => $relatedId,
                'sent_at'           => Carbon::now(),
            ]);
        } catch (\Throwable $logError) {
            Log::error("Failed to write to email_logs: " . $logError->getMessage());
        }

        return $deliveryStatus === 'sent';
    }
}
