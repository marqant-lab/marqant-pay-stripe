<?php

namespace Marqant\MarqantPayStripe;

use Stripe\Customer;
use Marqant\MarqantPay\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Services\MarqantPay;
use Marqant\MarqantPay\Contracts\PaymentMethodContract;
use Marqant\MarqantPay\Contracts\PaymentGatewayContract;

/**
 * Class StripePaymentGateway
 *
 * @package Marqant\MarqantPayStripe
 */
class StripePaymentGateway extends PaymentGatewayContract
{
    /**
     * @var string Name of the provider to reference in database and config.
     */
    protected const PAYMENT_PROVIDER = 'stripe';

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
            'marqant_pay_provider' => self::PAYMENT_PROVIDER,
            'stripe_id'            => $Customer->id,
        ]);
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

    /**
     * Save the provided payment method to the given Billable on the payment provider side.
     *
     * @param \Illuminate\Database\Eloquent\Model                 $Billable
     * @param \Marqant\MarqantPay\Contracts\PaymentMethodContract $PaymentMethod
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function savePaymentMethod(Model $Billable, PaymentMethodContract $PaymentMethod): Model
    {
        // create the payment method on the provider
        if ($PaymentMethod->object->customer !== $Billable->stripe_id) {
            $PaymentMethod->object = $PaymentMethod->object->attach([
                'customer' => $Billable->stripe_id,
            ]);
        }

        // update columns on billable
        self::updatePaymentMethodInformation($Billable, $PaymentMethod);

        return $Billable;
    }

    /**
     * Update the information stored on the billable.
     *
     * @param \Illuminate\Database\Eloquent\Model                 $Billable
     * @param \Marqant\MarqantPay\Contracts\PaymentMethodContract $PaymentMethod
     *
     * @return void
     */
    private static function updatePaymentMethodInformation(Model &$Billable, PaymentMethodContract $PaymentMethod): void
    {
        $data = [
            'stripe_pm_token' => $PaymentMethod->object->id,
        ];

        if ($PaymentMethod->object->type == 'card') {
            $Card = $PaymentMethod->object->card;

            $data = array_merge($data, [
                'marqant_pay_method'         => 'card',
                'marqant_pay_card_brand'     => $Card->brand,
                'marqant_pay_card_last_four' => $Card->last4,
            ]);
        }

        $Billable->update($data);
    }

    /**
     * Check if billable has a payment method attached.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     *
     * @return bool
     */
    public function hasPaymentMethod(Model $Billable): bool
    {
        return !!$Billable->stripe_pm_token;
    }

    /**
     * Check if billable has a payment method attached.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     *
     * @return \Marqant\MarqantPay\Contracts\PaymentMethodContract
     * @throws \Exception
     */
    public function getPaymentMethodOfBillable(Model $Billable): PaymentMethodContract
    {
        $payment_method = $Billable->marqant_pay_method;

        $details = ['token' => $Billable->stripe_pm_token];

        return MarqantPay::resolvePaymentMethod($payment_method, $details);
    }

    /**
     * @inheritDoc
     */
    public function removePaymentMethod(Model $Billable, array $payment_method): Model
    {
        // TODO: Implement removePaymentMethod() method.
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

    // TODO: Implement handling of charges

    /**
     * @inheritDoc
     */
    public function charge(Model $Billable, int $amount): Payment
    {
        // TODO: Implement charge() method.
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