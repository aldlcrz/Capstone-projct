<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SellerRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $sellerName;
    public string $shopName;
    public string $reason;
    public string $rejectionType;

    public function __construct(string $sellerName, ?string $shopName = null, ?string $reason = null, string $rejectionType = 'document_correction')
    {
        $this->sellerName    = $sellerName;
        $this->shopName      = $shopName ?? 'Artisan Workshop';
        $this->reason        = $reason ?? 'Application did not meet seller verification standards.';
        $this->rejectionType = $rejectionType;
        $this->subject       = $rejectionType === 'ineligible'
            ? "Important Notice: Artisan Application Ineligibility - LumBarong"
            : "Action Required: Update Verification Documents - LumBarong";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Artisan Registry');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.seller-rejected');
    }
}
