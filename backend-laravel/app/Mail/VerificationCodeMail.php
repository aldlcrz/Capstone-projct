<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class VerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $userName;
    public string $code;

    public function __construct(string $userName, string $code)
    {
        $this->userName = $userName;
        $this->code     = $code;
        $this->subject  = 'Your LumBarong Verification Code: ' . $code;
    }

    public function build()
    {
        $fromAddress = config('mail.from.address') ?: (config('mail.mailers.smtp.username') ?: 'lumbarongsupport@gmail.com');
        $fromName    = config('mail.from.name', 'LumBarong Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.verification-code')
                    ->text('emails.verification-code-text')
                    ->withSymfonyMessage(function ($message) {
                        $message->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, AutoReply');
                        $message->getHeaders()->addTextHeader('X-Priority', '1');
                        $message->getHeaders()->addTextHeader('Importance', 'high');
                    });
    }
}
