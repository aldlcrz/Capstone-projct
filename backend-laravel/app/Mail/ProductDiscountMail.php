<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductDiscountMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $userName;
    public string $productName;
    public string $shopName;
    public float $originalPrice;
    public float $oldPrice;
    public float $salePrice;
    public float $newPrice;
    public float $discountPercentage;
    public string $productId;

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
        $this->userName           = $customerName;
        $this->productName        = $productName;
        $this->shopName           = $shopName;
        $this->originalPrice      = $originalPrice;
        $this->oldPrice           = $originalPrice;
        $this->salePrice          = $salePrice;
        $this->newPrice           = $salePrice;
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
