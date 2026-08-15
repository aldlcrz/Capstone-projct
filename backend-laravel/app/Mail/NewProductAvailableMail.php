<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewProductAvailableMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $productName;
    public $shopName;
    public $price;
    public $productId;

    public function __construct(string $customerName, string $productName, string $shopName, float $price, string $productId)
    {
        $this->customerName = $customerName;
        $this->productName  = $productName;
        $this->shopName     = $shopName;
        $this->price        = $price;
        $this->productId    = $productId;
        $this->subject      = "New Heritage Product: \"{$productName}\" by {$shopName}";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.new-product-available');
    }
}
