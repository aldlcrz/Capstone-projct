<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $productName;
    public $productId;

    public function __construct(string $sellerName, string $productName, string $productId)
    {
        $this->sellerName  = $sellerName;
        $this->productName = $productName;
        $this->productId   = $productId;
        $this->subject     = "Product Approved: \"{$productName}\" is Live!";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.product-approved');
    }
}
