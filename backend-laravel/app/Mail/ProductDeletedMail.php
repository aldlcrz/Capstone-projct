<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $productName;
    public $reason;

    public function __construct(string $sellerName, string $productName, ?string $reason = null)
    {
        $this->sellerName  = $sellerName;
        $this->productName = $productName;
        $this->reason      = $reason ?? 'Administrative listing removal.';
        $this->subject     = "Product Listing Removed: \"{$productName}\" - LumBarong";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Moderation Desk');

        return $this->from($fromAddress, $fromName)
                    ->replyTo('lumbarongsupport@gmail.com', 'LumBarong Support')
                    ->subject($this->subject)
                    ->view('emails.product-deleted');
    }
}
