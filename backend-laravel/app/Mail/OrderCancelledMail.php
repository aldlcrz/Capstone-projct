<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public $recipientName;
    public $orderId;
    public $reason;
    public $actionUrl;

    public function __construct(string $recipientName, string $orderId, ?string $reason = null, ?string $actionUrl = null)
    {
        $this->recipientName = $recipientName;
        $this->orderId       = $orderId;
        $this->reason        = $reason;
        $this->actionUrl     = $actionUrl;
        $this->subject       = "Notice: Order #{$orderId} Has Been Cancelled";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.order-cancelled');
    }
}
