<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EmailChangeOldVerificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $userName;
    public $code;
    public $newEmail;

    public function __construct(string $userName, string $code, string $newEmail)
    {
        $this->userName = $userName;
        $this->code     = $code;
        $this->newEmail = $newEmail;
        $this->subject  = 'LumBarong Security: Verification Code for Email Change Request (' . $code . ')';
    }

    public function build()
    {
        $fromAddress = config('mail.from.address') ?: (config('mail.mailers.smtp.username') ?: 'lumbarongsupport@gmail.com');
        $fromName    = config('mail.from.name', 'LumBarong Security');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.email-change-old');
    }
}
