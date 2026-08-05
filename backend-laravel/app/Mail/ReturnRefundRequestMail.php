<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReturnRefundRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $orderId;
    public $reason;
    public $requestType;

    public function __construct(string $sellerName, string $orderId, string $reason, string $requestType = 'Return/Refund')
    {
        $this->sellerName  = $sellerName;
        $this->orderId     = $orderId;
        $this->reason      = $reason;
        $this->requestType = $requestType;
        $this->subject     = "Action Required: New {$requestType} Request for Order #{$orderId}";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.return-refund-request');
    }
}
