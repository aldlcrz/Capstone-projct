<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerDeletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $shopName;
    public $reason;

    public function __construct(string $sellerName, ?string $shopName = null, ?string $reason = null)
    {
        $this->sellerName = $sellerName;
        $this->shopName   = $shopName ?? 'Artisan Workshop';
        $this->reason     = $reason ?? 'Administrative account cleanup.';
        $this->subject    = "Artisan Account Deletion Notice - LumBarong";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Artisan Registry');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.seller-deleted');
    }
}
