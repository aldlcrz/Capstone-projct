<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $reason;

    public function __construct(string $customerName, ?string $reason = null)
    {
        $this->customerName = $customerName;
        $this->reason       = $reason ?? 'Administrative account cleanup.';
        $this->subject      = "Customer Account Deletion Notice - LumBarong";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Support');

        return $this->from($fromAddress, $fromName)
                    ->replyTo('lumbarongsupport@gmail.com', 'LumBarong Support')
                    ->subject($this->subject)
                    ->view('emails.customer-deleted');
    }
}
