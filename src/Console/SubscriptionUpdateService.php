<?php

namespace Laravel\CashierAuthorizeNet\Console;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Laravel\CashierAuthorizeNet\Requestor;
use net\authorize\api\contract\v1 as AnetAPI;
use Laravel\CashierAuthorizeNet\Subscription;
use net\authorize\api\controller as AnetController;

class SubscriptionUpdateService
{
    public function runUpdates()
    {
        $nonActiveStatus = [
            'expired',
            'suspended',
            'cancelled',
            'terminated',
        ];

        $subscriptions = Subscription::all();

        foreach ($subscriptions as $subscription) {
            $requestor = new Requestor;
            $request = $requestor->prepare(new AnetAPI\ARBGetSubscriptionStatusRequest());
            $request->setSubscriptionId($subscription->authorize_id);

            $controller = new AnetController\ARBGetSubscriptionStatusController($request);
            $response = $controller->executeWithApiResponse($requestor->env);

            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                if (in_array($response->getStatus(), $nonActiveStatus)) {
                    $subscription->ends_at = Carbon::now();
                    $subscription->save();
                }
             } else {
                $errorMessages = $response->getMessages()->getMessage();
                Log::error("Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText());
            }
        }
    }
}
