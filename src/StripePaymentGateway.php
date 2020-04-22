<?php

namespace Marqant\MarqantPayStripe;

use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Contracts\PaymentGatewayContract;

class StripePaymentGateway extends PaymentGatewayContract
{

    /*
     |--------------------------------------------------------------------------
     | Plans
     |--------------------------------------------------------------------------
     |
     | In this section of the gateway, you will find all methods to manage the
     | plans on stripe.
     |
     */

    // TODO: Implement management of plans

    /*
     |--------------------------------------------------------------------------
     | Customers
     |--------------------------------------------------------------------------
     |
     | In this section of the gateway, you will find all methods to manage the
     | customers on stripe.
     |
     */

    // TODO: Implement management of customers

    /**
     * @inheritDoc
     */
    protected function savePaymentMethod(Model $Billable, array $payment_method): Model
    {
        // TODO: Implement savePaymentMethod() method.
    }

    // TODO: Implement handling of charges

    /**
     * @inheritDoc
     */
    protected function removePaymentMethod(Model $Billable, array $payment_method): Model
    {
        // TODO: Implement removePaymentMethod() method.
    }

    /*
     |--------------------------------------------------------------------------
     | Subscriptions
     |--------------------------------------------------------------------------
     |
     | In this section of the gateway, you will find all methods to manage the
     | subscriptions of a billable on stripe.
     |
     */

    // TODO: Implement management of subscriptions

    /**
     * @inheritDoc
     */
    protected function subscribe(Model $Billable, Model $Plan): Model
    {
        // TODO: Implement subscribe() method.
    }

    /*
     |--------------------------------------------------------------------------
     | Charges
     |--------------------------------------------------------------------------
     |
     | In this section of the gateway, you will find all methods to make charges
     | against a billable.
     |
     */

    /**
     * @inheritDoc
     */
    protected function charge(Model $Billable, array $payment_method): Model
    {
        // TODO: Implement charge() method.
    }

    /*
     |--------------------------------------------------------------------------
     | Invoices
     |--------------------------------------------------------------------------
     |
     | In this section of the gateway, you will find all methods to manage
     | invoices on stripe.
     |
     */

    // TODO: Implement management of invoices
}