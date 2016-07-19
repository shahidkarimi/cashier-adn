<?php

namespace Laravel\CashierAuthorizeNet;

use Carbon\Carbon;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\constants as AnetConstants;
use net\authorize\api\controller as AnetController;

class Requestor
{
    public $env;

    public $refId;

    public function __construct()
    {
        if (! defined("AUTHORIZENET_LOG_FILE")) {
            define("AUTHORIZENET_LOG_FILE", getenv('ADN_LOG'));
        }
    }

    public function prepare($request)
    {
        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(getenv('ADN_API_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(getenv('ADN_TRANSACTION_KEY'));

        $env = strtoupper(getenv('ADN_ENV'));

        if ($env === '') {
            $env = 'SANDBOX';
        }

        $this->env = constant("net\authorize\api\constants\ANetEnvironment::$env");

        $refId = 'ref' . time();
        $this->refId = $refId;

        $request->setRefId($refId);
        $request->setMerchantAuthentication($merchantAuthentication);

        return $request;
    }
}
