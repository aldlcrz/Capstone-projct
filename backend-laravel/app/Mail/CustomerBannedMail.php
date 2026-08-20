<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerBannedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $reason;

    public function __construct(string $customerName, ?string $reason = null)
    {
        $this->customerName = $customerName;
        $this->reason       = $reason ?? 'Violation of platform terms and conditions.';
        $this->subject      = "⚠️ Important Notice: Your Account Has Been Suspended - LumBarong";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Support');

        return $this->from($fromAddress, $fromName)
                    ->replyTo('lumbarongsupport@gmail.com', 'LumBarong Support')
                    ->subject($this->subject)
                    ->view('emails.customer-banned');
    }
}
