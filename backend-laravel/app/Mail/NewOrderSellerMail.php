<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewOrderSellerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $orderId;
    public $totalAmount;

    public function __construct(string $sellerName, string $orderId, float $totalAmount)
    {
        $this->sellerName  = $sellerName;
        $this->orderId     = $orderId;
        $this->totalAmount = $totalAmount;
        $this->subject     = "New Order Received! Order #{$orderId}";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.new-order-seller');
    }
}
