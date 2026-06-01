<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

use App\Events\BillGenerated;
use App\Events\PaymentReceived;
use App\Events\AdvanceReceived;
use App\Events\AdvanceUsed;
use App\Events\ClientStatusChanged;
use App\Events\ClientBalanceChanged;
use App\Events\BillDueReminder;
use App\Events\BillOverdue;
use App\Events\TemporaryDeadlineApplied;
use App\Events\TemporaryDeadlineExpired;
use App\Events\MikroTikActionExecuted;
use App\Events\SMSDispatched;

use App\Listeners\SyncBillGenerated;
use App\Listeners\SyncPaymentReceived;
use App\Listeners\SyncAdvanceReceived;
use App\Listeners\SyncAdvanceUsed;
use App\Listeners\SyncClientStatusChanged;
use App\Listeners\SyncClientBalanceChanged;
use App\Listeners\SmsBillGenerated;
use App\Listeners\SmsPaymentReceived;
use App\Listeners\SmsClientStatusChanged;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        BillGenerated::class => [
            SyncBillGenerated::class,
            SmsBillGenerated::class,
        ],
        PaymentReceived::class => [
            SyncPaymentReceived::class,
            SmsPaymentReceived::class,
        ],
        AdvanceReceived::class => [
            SyncAdvanceReceived::class,
        ],
        AdvanceUsed::class => [
            SyncAdvanceUsed::class,
        ],
        ClientStatusChanged::class => [
            SyncClientStatusChanged::class,
            SmsClientStatusChanged::class,
        ],
        ClientBalanceChanged::class => [
            SyncClientBalanceChanged::class,
        ],
        BillDueReminder::class => [],
        BillOverdue::class => [],
        TemporaryDeadlineApplied::class => [],
        TemporaryDeadlineExpired::class => [],
        MikroTikActionExecuted::class => [],
        SMSDispatched::class => [],
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                Event::listen($event, $listener);
            }
        }
    }
}
