<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentConfirmedSellerMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $orderId;
    public $totalAmount;
    public $paymentMethod;

    public function __construct(string $sellerName, string $orderId, float $totalAmount, string $paymentMethod = 'Online Payment')
    {
        $this->sellerName    = $sellerName;
        $this->orderId       = $orderId;
        $this->totalAmount   = $totalAmount;
        $this->paymentMethod = $paymentMethod;
        $this->subject       = "Payment Confirmed for Order #{$orderId}";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.payment-confirmed-seller');
    }
}
