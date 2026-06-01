<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SmsSchedule;
use App\Models\Bill;
use App\Models\Client;
use App\Services\SmsService;
use Carbon\Carbon;

class ProcessSmsSchedules extends Command
{
    protected $signature = 'sms:process-schedules';
    protected $description = 'Process scheduled SMS messages';

    public function handle(SmsService $smsService)
    {
        $schedules = SmsSchedule::dueToRun()->get();

        foreach ($schedules as $schedule) {
            $this->info("Processing schedule: {$schedule->name}");

            switch ($schedule->schedule_type) {
                case 'due_reminder':
                    $this->processDueReminders($schedule, $smsService);
                    break;
                case 'disconnect':
                    $this->processDisconnectNotices($schedule, $smsService);
                    break;
                case 'birthday':
                    $this->processBirthdayMessages($schedule, $smsService);
                    break;
                default:
                    $this->processCustomSchedule($schedule, $smsService);
            }

            $schedule->last_run = now();
            $schedule->next_run = now()->addDay();
            $schedule->save();
        }

        $this->info('SMS schedule processing completed.');
    }

    protected function processDueReminders(SmsSchedule $schedule, SmsService $smsService)
    {
        $daysBeforeDue = $schedule->days_before ?? 3;

        $bills = Bill::where('status', 'unpaid')
            ->whereDate('due_date', now()->addDays($daysBeforeDue))
            ->with('client')
            ->get();

        foreach ($bills as $bill) {
            $smsService->sendDueReminder($bill);
            $this->line("  Sent due reminder to: {$bill->client->name}");
        }
    }

    protected function processDisconnectNotices(SmsSchedule $schedule, SmsService $smsService)
    {
        $daysAfterDue = $schedule->days_after ?? 7;

        $clients = Client::whereHas('bills', function ($q) use ($daysAfterDue) {
            $q->where('status', 'unpaid')
                ->whereDate('due_date', '<=', now()->subDays($daysAfterDue));
        })->where('status', 'active')->get();

        foreach ($clients as $client) {
            $smsService->sendDisconnectNotice($client);
            $this->line("  Sent disconnect notice to: {$client->name}");
        }
    }

    protected function processBirthdayMessages(SmsSchedule $schedule, SmsService $smsService)
    {
        $clients = Client::whereRaw("EXTRACT(MONTH FROM connection_date) = ? AND EXTRACT(DAY FROM connection_date) = ?", [
            now()->month,
            now()->day
        ])->get();

        $message = $schedule->template?->content ?? 'Happy Anniversary! Thank you for being with us.';

        foreach ($clients as $client) {
            $smsService->sendSms($client->phone, $message, 'birthday');
            $this->line("  Sent birthday message to: {$client->name}");
        }
    }

    protected function processCustomSchedule(SmsSchedule $schedule, SmsService $smsService)
    {
        if (!$schedule->template) return;

        $filter = $schedule->target_filter ?? [];
        $clientIds = $this->getClientsByFilter($filter);

        $smsService->sendBulkSms($clientIds, $schedule->template->content, 'custom');
    }

    protected function getClientsByFilter(array $filter): array
    {
        $query = Client::query();

        if (!empty($filter['zone_id'])) {
            $query->where('zone_id', $filter['zone_id']);
        }
        if (!empty($filter['package_id'])) {
            $query->where('package_id', $filter['package_id']);
        }
        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        return $query->pluck('id')->toArray();
    }
}
