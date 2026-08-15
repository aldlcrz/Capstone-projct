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
     * Generate a unique 6-digit verification code.
     */
    public static function createVerificationCode(string $email, string $type = 'registration'): EmailVerification
    {
        // Delete any existing code for this email and type
        EmailVerification::where('email', strtolower($email))
            ->where('type', $type)
            ->delete();

        $code = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        return EmailVerification::create([
            'email'           => strtolower($email),
            'code'            => $code,
            'type'            => $type,
            'expires_at'      => Carbon::now()->addMinutes(10),
            'resend_count'    => 0,
            'failed_attempts' => 0,
            'last_sent_at'    => Carbon::now(),
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

        if ($verification->code !== trim($code)) {
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
