<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PasswordResetCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $code;

    public function __construct(string $userName, string $code)
    {
        $this->userName = $userName;
        $this->code     = $code;
        $this->subject  = 'LumBarong Password Reset Code: ' . $code;
    }

    public function build()
    {
        $fromAddress = config('mail.from.address') ?: (config('mail.mailers.smtp.username') ?: 'lumbarongsupport@gmail.com');
        $fromName    = config('mail.from.name', 'LumBarong Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.password-reset-code');
    }
}
