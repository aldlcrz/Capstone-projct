<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommissionReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $sellerName;
    public $period;
    public $amountDue;
    public $dueDateFormatted;
    public $reminderType;
    public $reminderTitle;
    public $reminderMessage;
    public $badgeClass;

    public function __construct(
        string $sellerName,
        string $period,
        float $amountDue,
        string $dueDateFormatted,
        string $reminderType, // '7_days_before', '3_days_before', 'on_due_date', 'overdue'
        string $reminderTitle,
        string $reminderMessage,
        string $badgeClass = 'badge-warning'
    ) {
        $this->sellerName       = $sellerName;
        $this->period           = $period;
        $this->amountDue        = $amountDue;
        $this->dueDateFormatted = $dueDateFormatted;
        $this->reminderType     = $reminderType;
        $this->reminderTitle    = $reminderTitle;
        $this->reminderMessage  = $reminderMessage;
        $this->badgeClass       = $badgeClass;
        $this->subject          = "LumBarong Seller Commission Notice: {$reminderTitle}";
    }

    public function build()
    {
        $fromAddress = config('mail.from.address', 'no-reply@lumbarong.com');
        $fromName    = config('mail.from.name', 'LumBarong Automated Notifications');

        return $this->from($fromAddress, $fromName)
                    ->replyTo($fromAddress, $fromName)
                    ->subject($this->subject)
                    ->view('emails.commission-reminder');
    }
}
