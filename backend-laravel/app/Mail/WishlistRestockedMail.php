<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WishlistRestockedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $productName;
    public string $shopName;
    public float $price;
    public ?string $size;
    public string $productId;
    public ?string $imageUrl;

    public function __construct(
        string $customerName,
        string $productName,
        string $shopName,
        float $price,
        ?string $size,
        string $productId,
        ?string $imageUrl = null
    ) {
        $this->customerName = $customerName;
        $this->productName  = $productName;
        $this->shopName     = $shopName;
        $this->price        = $price;
        $this->size         = $size;
        $this->productId    = $productId;
        $this->imageUrl     = $imageUrl;

        $sizeText = $size ? " (Size {$size})" : "";
        $this->subject = "🎉 Back in Stock: \"{$productName}\"{$sizeText} was added to your cart!";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'lumbarongsupport@gmail.com');
        $fromName    = config('mail.from.name', 'LumBarong Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.wishlist-restocked');
    }
}
