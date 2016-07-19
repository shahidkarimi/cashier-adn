<?php

namespace Laravel\CashierAuthorizeNet;

use Exception;
use Carbon\Carbon;
use LogicException;
use DateTimeInterface;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Laravel\CashierAuthorizeNet\Requestor;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\constants as AnetConstants;
use net\authorize\api\controller as AnetController;

class Subscription extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'trial_ends_at', 'ends_at',
        'created_at', 'updated_at',
    ];

    /**
     * Get the user that owns the subscription.
     */
    public function user()
    {
        $model = getenv('ADN_MODEL') ?: config('services.authorize.model', 'User');

        return $this->belongsTo($model, 'user_id');
    }

    /**
     * Determine if the subscription is active, on trial, or within its grace period.
     *
     * @return bool
     */
    public function valid()
    {
        return $this->active() || $this->onTrial() || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is active.
     *
     * @return bool
     */
    public function active()
    {
        return is_null($this->ends_at) || $this->onGracePeriod();
    }

    /**
     * Determine if the subscription is no longer active.
     *
     * @return bool
     */
    public function cancelled()
    {
        return ! is_null($this->ends_at);
    }

    /**
     * Determine if the subscription is within its trial period.
     *
     * @return bool
     */
    public function onTrial()
    {
        if (! is_null($this->trial_ends_at)) {
            return Carbon::today()->lt($this->trial_ends_at);
        } else {
            return false;
        }
    }

    /**
     * Determine if the subscription is within its grace period after cancellation.
     *
     * @return bool
     */
    public function onGracePeriod()
    {
        if (! is_null($endsAt = $this->ends_at)) {
            return Carbon::now()->lt(Carbon::instance($endsAt));
        } else {
            return false;
        }
    }

    /**
     * Cancel the subscription at the end of the billing period.
     *
     * @return $this
     */
    public function cancel()
    {
        $today = Carbon::now('America/Denver');
        $billingDay = $this->created_at->day;
        $billingDate = Carbon::createFromDate($today->year, $today->month, $billingDay)->timezone('America/Denver');

        $endingDate = $billingDate;

        if ($today->gte($endingDate)) {
            $endingDate = $billingDate->addDays($this->getBillingDays());
        }

        $requestor = new Requestor();
        $request = $requestor->prepare((new AnetAPI\ARBCancelSubscriptionRequest()));
        $request->setSubscriptionId($this->authorize_id);
        $controller = new AnetController\ARBCancelSubscriptionController($request);
        $response = $controller->executeWithApiResponse($requestor->env);

        if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
            // If the user was on trial, we will set the grace period to end when the trial
            // would have ended. Otherwise, we'll retrieve the end of the billing period
            // period and make that the end of the grace period for this current user.
            if ($this->onTrial()) {
                $this->ends_at = $this->trial_ends_at;
            } else {
                $this->ends_at = $endingDate;
            }

            $this->save();
        } else {
            throw new Exception("Response : " . $errorMessages[0]->getCode() . "  " .$errorMessages[0]->getText(), 1);
        }

        return $this;
    }

    /**
     * Cancel the subscription immediately.
     *
     * @return $this
     */
    public function cancelNow()
    {
        $this->cancel();
        $this->markAsCancelled();

        return $this;
    }

    /**
     * Mark the subscription as cancelled.
     *
     * @return void
     */
    public function markAsCancelled()
    {
        $this->fill(['ends_at' => Carbon::now()])->save();
    }

    /**
     * Get billing days
     *
     * @return integer
     */
    public function getBillingDays()
    {
        $config = Config::get('cashier-authorize');
        $unit = $config[$this->authorize_plan]['interval']['unit'];
        $length = $config[$this->authorize_plan]['interval']['length'];

        if ($unit === 'months') {
            $days = 31 * $length;
        } else if ($unit === 'days') {
            $days = 1 * $length;
        } else if ($unit === 'weeks') {
            $days = 7 * $length;
        } else if ($unit === 'years') {
            $days = 365 * $length;
        }

        return $days;
    }
}
