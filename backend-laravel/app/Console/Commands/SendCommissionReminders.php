<?php

namespace App\Console\Commands;

use App\Mail\CommissionReminderMail;
use App\Models\CommissionRecord;
use App\Models\EmailLog;
use App\Services\EmailNotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendCommissionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated Gmail notifications for upcoming and overdue seller commission payments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking seller commission due dates...');

        $unpaidRecords = CommissionRecord::where('status', 'unpaid')
            ->whereNotNull('dueDate')
            ->with('seller')
            ->get();

        $today = Carbon::today();
        $sentCount = 0;

        foreach ($unpaidRecords as $record) {
            $seller = $record->seller;
            if (!$seller || !$seller->email) {
                continue;
            }

            $dueDate = Carbon::parse($record->dueDate)->startOfDay();
            $diffInDays = (int) $today->diffInDays($dueDate, false);

            $reminderType = null;
            $title = null;
            $message = null;
            $badgeClass = 'badge-warning';

            if ($diffInDays === 7) {
                $reminderType = 'commission_reminder_7d';
                $title = 'Upcoming Commission Due (In 7 Days)';
                $message = 'This is a friendly reminder that your monthly seller commission payment is due in 7 days.';
                $badgeClass = 'badge-info';
            } elseif ($diffInDays === 3) {
                $reminderType = 'commission_reminder_3d';
                $title = 'Upcoming Commission Due (In 3 Days)';
                $message = 'Your seller commission payment is due in 3 days. Please settle your account to ensure uninterrupted service.';
                $badgeClass = 'badge-warning';
            } elseif ($diffInDays === 0) {
                $reminderType = 'commission_reminder_due';
                $title = 'Commission Payment Due Today';
                $message = 'Your seller commission payment is due today. Please submit your payment reference and proof in the Seller Center.';
                $badgeClass = 'badge-warning';
            } elseif ($diffInDays < 0) {
                $reminderType = 'commission_reminder_overdue';
                $title = 'OVERDUE: Seller Commission Payment';
                $message = 'Your seller commission payment is overdue. Failure to settle may lead to account freezing.';
                $badgeClass = 'badge-danger';
            }

            if (!$reminderType) {
                continue;
            }

            // Prevent duplicate reminders for the same record and threshold
            $alreadySent = EmailLog::where('related_id', $record->id)
                ->where('notification_type', $reminderType)
                ->when($reminderType === 'commission_reminder_overdue', function ($q) use ($today) {
                    return $q->whereDate('sent_at', $today);
                })
                ->exists();

            if ($alreadySent) {
                continue;
            }

            $mailable = new CommissionReminderMail(
                $seller->name,
                $record->period,
                (float) $record->commissionAmount,
                $dueDate->format('M d, Y'),
                str_replace('_', ' ', strtoupper($reminderType)),
                $title,
                $message,
                $badgeClass
            );

            EmailNotificationService::sendNotification(
                $seller->email,
                $mailable,
                $reminderType,
                $seller->id,
                'CommissionRecord',
                $record->id
            );

            $sentCount++;
            $this->info("Sent {$reminderType} to {$seller->email} for period {$record->period}");
        }

        $this->info("Commission reminder checks complete. Dispatched {$sentCount} notifications.");
        return self::SUCCESS;
    }
}
