<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReturnRefundStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $customerName;
    public string $userName;
    public string $orderId;
    public string $status;
    public ?string $comments;
    public ?string $reason;
    public string $requestType;

    public function __construct(string $customerName, string $orderId, string $status, ?string $comments = null, string $requestType = 'Return/Refund')
    {
        $this->customerName = $customerName;
        $this->userName     = $customerName;
        $this->orderId      = $orderId;
        $this->status       = $status;
        $this->comments     = $comments;
        $this->reason       = $comments;
        $this->requestType  = $requestType;
        $this->subject      = "Update on Your {$requestType} Request for Order #{$orderId}";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.return-refund-status');
    }
}
