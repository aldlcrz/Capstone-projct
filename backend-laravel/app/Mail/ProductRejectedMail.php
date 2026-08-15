<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ProductRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $productName;
    public $productId;
    public $rejectionReason;

    public function __construct(string $sellerName, string $productName, string $productId, string $rejectionReason)
    {
        $this->sellerName      = $sellerName;
        $this->productName     = $productName;
        $this->productId       = $productId;
        $this->rejectionReason = $rejectionReason;
        $this->subject         = "Product Submission Update: \"{$productName}\" Requires Revision";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.product-rejected');
    }
}
