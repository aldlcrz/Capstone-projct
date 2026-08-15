<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductDiscountMail extends Mailable
{
    use Queueable, SerializesModels;

    public $customerName;
    public $productName;
    public $shopName;
    public $originalPrice;
    public $salePrice;
    public $discountPercentage;
    public $productId;

    public function __construct(
        string $customerName,
        string $productName,
        string $shopName,
        float $originalPrice,
        float $salePrice,
        float $discountPercentage,
        string $productId
    ) {
        $this->customerName       = $customerName;
        $this->productName        = $productName;
        $this->shopName           = $shopName;
        $this->originalPrice      = $originalPrice;
        $this->salePrice          = $salePrice;
        $this->discountPercentage = $discountPercentage;
        $this->productId          = $productId;
        $this->subject            = "Special Discount: {$discountPercentage}% OFF on \"{$productName}\"!";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.product-discount');
    }
}
