<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CustomerRestoredMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;

    public function __construct(string $customerName)
    {
        $this->customerName = $customerName;
        $this->subject      = "🎉 Your LumBarong Account Has Been Restored";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Support');

        return $this->from($fromAddress, $fromName)
                    ->replyTo('lumbarongsupport@gmail.com', 'LumBarong Support')
                    ->subject($this->subject)
                    ->view('emails.customer-restored');
    }
}
