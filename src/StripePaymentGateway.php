<?php

namespace Marqant\MarqantPayStripe;

use Stripe\Customer;
use Marqant\MarqantPay\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Contracts\PaymentGatewayContract;

class StripePaymentGateway extends PaymentGatewayContract
{
    /**
     * @var string Name of the provider to reference in database and config.
     */
    protected const PAYMETN_PROVIDER = 'stripe';

    /*
     |--------------------------------------------------------------------------
     | Customers
     |--------------------------------------------------------------------------
     |
     | In this section of the gateway, you will find all methods to manage the
     | customers on stripe.
     |
     */

    /**
     * Create customer on the provider end and update the user.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createCustomer(Model $Billable): Model
    {
        $Customer = Customer::create([
            'email' => $Billable->email,
        ]);

        self::updateCustomerInformation($Billable, $Customer);

        return $Billable;
    }

    /**
     * Update the billable with the customer information from the provider.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     * @param \Stripe\Customer                    $Customer
     */
    private static function updateCustomerInformation(Model &$Billable, Customer $Customer)
    {
        $Billable->update([
            'marqant_pay_provider' => self::PAYMETN_PROVIDER,
            'stripe_id'            => $Customer->id,
        ]);
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
    public function charge(Model $Billable, int $amount): Payment
    {
        // TODO: Implement charge() method.
    }


    /*
     |--------------------------------------------------------------------------
     | Payment Method
     |--------------------------------------------------------------------------
     |
     | In this section of the gateway, you will find all methods to payment
     | methods of the payment methods of a customer on stripe.
     |
     */

    // TODO: Implement management of payment methods

    /**
     * @inheritDoc
     */
    public function savePaymentMethod(Model $Billable, array $payment_method): Model
    {
        // TODO: Implement savePaymentMethod() method.
    }

    // TODO: Implement handling of charges

    /**
     * @inheritDoc
     */
    public function removePaymentMethod(Model $Billable, array $payment_method): Model
    {
        // TODO: Implement removePaymentMethod() method.
    }

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
    public function subscribe(Model $Billable, Model $Plan): Model
    {
        // TODO: Implement subscribe() method.
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