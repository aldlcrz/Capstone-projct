<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $orderId;
    public $status;
    public $statusMessage;

    public function __construct(string $customerName, string $orderId, string $status, ?string $statusMessage = null)
    {
        $this->customerName  = $customerName;
        $this->orderId       = $orderId;
        $this->status        = $status;
        $this->statusMessage = $statusMessage;
        $this->subject       = "Order #{$orderId} Update: {$status}";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.order-status');
    }
}
