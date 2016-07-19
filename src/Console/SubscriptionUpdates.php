<?php

namespace Laravel\CashierAuthorizeNet\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Laravel\Support\Facades\Log;
use Laravel\CashierAuthorizeNet\Requestor;
use net\authorize\api\contract\v1 as AnetAPI;
use Laravel\CashierAuthorizeNet\Subscription;
use net\authorize\api\controller as AnetController;
use Laravel\CashierAuthorizeNet\Console\SubscriptionUpdateService;

class SubscriptionUpdates extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'subscription:updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Your Authorize.net subscriptions will be updated via a check with their status on Authorize.net';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $subscriptionUpdateService = new SubscriptionUpdateService();
        $subscriptionUpdateService->runUpdates();
    }
}
