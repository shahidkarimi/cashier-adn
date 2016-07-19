# Laravel Cashier-Authorize

## Introduction

Laravel Cashier-Authorize provides an expressive, fluent interface to [Authorize.net's](https://authorize.net) subscription billing services. It handles almost all of the boilerplate subscription billing code you are dreading writing. In addition to basic subscription management, Cashier-Authorize can handle cancellation grace periods, and even generate invoice PDFs.

## Basic Setup

Please read the following for the basic setup.

#### .env
ADN_ENV=
ADN_LOG=authorize.log

ADN_API_LOGIN_ID=
ADN_TRANSACTION_KEY=
ADN_SECRET_KEY=Simon

ADN_ENV should be one of: sandbox, production

#### Migrations

You will need to make migrations that include the following:

```php
Schema::table('users', function ($table) {
    $table->string('authorize_id')->nullable();
    $table->string('authorize_payment_id')->nullable();
    $table->string('card_brand')->nullable();
    $table->string('card_last_four')->nullable();
});
```

```php
Schema::create('subscriptions', function ($table) {
    $table->increments('id');
    $table->integer('user_id');
    $table->string('name');
    $table->string('authorize_id');
    $table->string('authorize_payment_id');
    $table->text('metadata');
    $table->string('authorize_plan');
    $table->integer('quantity');
    $table->timestamp('trial_ends_at')->nullable();
    $table->timestamp('ends_at')->nullable();
    $table->timestamps();
});
```

#### Publish

You will need to publish the assets of this package.

`php artisan vendor:publish --provider="Laravel\CashierAuthorizeNet\CashierServiceProvider"`

#### Config

Below is an example config for a subscription with Authorize.net compatibility. You can define your subscriptions in this config.

```php
'monthly-10-1' => [
    'name' => 'main',
    'interval' => [
        'length' => 1, // number of instances for billing
        'unit' => 'months' //months, days, years
    ],
    'total_occurances' => 9999, // 9999 means without end date
    'trial_occurances' => 0,
    'amount' => 9.99,
    'trial_amount' => 0,
    'trial_days' => 0,
    'trial_delay' => 0, // days you wish to delay the start of billing
]
```

#### 'config/services.php'

You will need to add the following to your 'config/services.php' file, please make sure that the model matches your app's User class:

```php
'authorize' => [
    'model'  => App\User::class,
],
```

You can also set this value with the following `.env` variable: ADN_MODEL

## Basic Usage

There are differences with Authorize.net vs services like Stripe. Authorize.net is a slighly slower and more restricted subscription provider. This means you cannot do things like swap subscriptions, or change quantity of subscriptions. You need to cancel, and create new subscriptions to handle those variations.

You can perform the following actions:

User::
* charge($amount, array $options = [])
* hasCardOnFile
* newSubscription($subscription, $plan)
* onTrial($subscription = 'default', $plan = null)
* onGenericTrial()
* subscribed($subscription = 'default', $plan = null)
* subscription($subscription = 'default')
* subscriptions()
* updateCard($card) // $card = ['number' => '', 'expriation' => '']
* subscribedToPlan($plans, $subscription = 'default')
* onPlan($plan)
* hasAuthorizeId()
* createAsAuthorizeCustomer($creditCardDetails)
* upcomingInvoice($plan)
* findInvoice($invoiceId)
* findInvoiceOrFail($id)
* downloadInvoice($id, array $data, $storagePath = null)
* getSubscriptionFromAuthorize($subscriptionId)
* invoices($plan)
* deleteAuthorizeProfile()
* preferredCurrency()
* taxPercentage() (this method should be added to the User Model and define that user's tax percentage ie: return 10;)

#### Transaction Details

Enabling the API
To enable the Transaction Details API:
1) Log on to the Merchant Interface at https://account.authorize.net .
2) Select Settings under Account in the main menu on the left.
3) Click the Transaction Details API link in the Security Settings section. The Transaction Details API screen opens.
4) If you have not already enabled the Transaction Details API, enter the answer to your Secret Question, then click Enable Transaction Details API.
5) When you have successfully enabled the Transaction Details API, the Settings page displays.

### CRON job

You need to enable the following CRON job to check the status of your user's subscriptions. This can run as often as you like, and will check to confirm that your user's subscription is active. If the status is changed to cancelled or suspended - the system will disable their subscription locally. Your team will need to resolve the payment issue with Authorize.net and then move forward.

```php
protected $commands = [
    \Laravel\CashierAuthorizeNet\Console\SubscriptionUpdates::class,
];
```

```php
$schedule->command('subscription:update')->hourly();
```

#### Limitations
Another limitation is time related. Due to the fact that Authorize.net uses a SOAP structure for its APIs, there needs to be a time delay between adding a customer with a credit card to their system and then adding a subscription to that user. This could be done easily in your app by having the user enter their credit card information, and then allowing a confirmation of the subscription they wish to purchase as another action. This time can be as little as a second, but all tests thus far with immediate adding of subscriptions fails to work, so please be mindful of this limitation when designing your app.

## License

Laravel Cashier-Authorize is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
"# cashier-authorize" 
"# cashier-adn" 
