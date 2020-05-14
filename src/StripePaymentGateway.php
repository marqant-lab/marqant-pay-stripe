<?php

namespace Marqant\MarqantPayStripe;

use Exception;
use Stripe\Plan;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Marqant\MarqantPay\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Marqant\MarqantPay\Services\MarqantPay;
use Stripe\SetupIntent as StripeSetupIntent;
use Stripe\Subscription as StripeSubscription;
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
    public function createCustomer(Model &$Billable): Model
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
     * Create a SetupIntent on the provider side (stripe) to perform all authentication up front.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     *
     * @return \Stripe\SetupIntent
     */
    public static function createSetupIntent(Model $Billable): StripeSetupIntent
    {
        /**
         * @var \App\User $Billable
         */

        return StripeSetupIntent::create([
            'customer' => $Billable->stripe_id,
        ]);
    }

    /**
     * Save the provided payment method to the given Billable on the payment provider side.
     *
     * @param \Illuminate\Database\Eloquent\Model                 $Billable
     * @param \Marqant\MarqantPay\Contracts\PaymentMethodContract $PaymentMethod
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function savePaymentMethod(Model &$Billable, PaymentMethodContract $PaymentMethod): Model
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
     * @param \Illuminate\Database\Eloquent\Model                      $Billable
     * @param null|\Marqant\MarqantPay\Contracts\PaymentMethodContract $PaymentMethod
     *
     * @return void
     */
    private static function updatePaymentMethodInformation(Model &$Billable,
                                                           ?PaymentMethodContract $PaymentMethod = null): void
    {
        if ($PaymentMethod) {
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
        } else {
            $data = [
                'stripe_pm_token'            => null,
                'marqant_pay_method'         => null,
                'marqant_pay_card_brand'     => null,
                'marqant_pay_card_last_four' => null,
            ];
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
     * Remove payment method from billable.
     *
     * @param \Illuminate\Database\Eloquent\Model                 $Billable
     *
     * @param \Marqant\MarqantPay\Contracts\PaymentMethodContract $PaymentMethod
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Exception
     */
    public function removePaymentMethod(Model &$Billable, PaymentMethodContract $PaymentMethod): Model
    {
        // remove payment method on stripe
        $PaymentMethod->object->detach();

        // update columns on billable
        self::updatePaymentMethodInformation($Billable);

        return $Billable;
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
     * Charge a given billable for a given amount.
     *
     * @param \Illuminate\Database\Eloquent\Model                      $Billable
     * @param float                                                    $amount
     * @param string                                                   $description
     * @param null|\Marqant\MarqantPay\Contracts\PaymentMethodContract $PaymentMethod
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function charge(Model $Billable, float $amount, string $description,
                           ?PaymentMethodContract $PaymentMethod = null): Model
    {
        /**
         * @var \App\User $Billable
         */

        // resolve payment method
        if (!$PaymentMethod) {
            $PaymentMethod = MarqantPay::getPaymentMethodOfBillable($Billable);
        }

        // check if we actually have a payment method
        if (!$PaymentMethod) {
            throw new Exception('No payment method provided to charge.');
        }

        // create options for stripe payment (payment intent)
        $options = [
            'customer'            => $Billable->stripe_id,
            'amount'              => $amount * 100,
            'description'         => $description,
            'payment_method'      => $PaymentMethod->object,
            'currency'            => $this->getCurrency(),
            'confirmation_method' => 'automatic',
            'confirm'             => true,
            'off_session'         => true,
        ];

        // create payment (payment intent) on stripes end
        $PaymentIntent = PaymentIntent::create($options);

        return self::createPaymentFromPaymentIntent($Billable, $PaymentIntent);
    }

    /**
     * Create DB Payment from Stripe PaymentIntent
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     * @param \Stripe\PaymentIntent               $PaymentIntent
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws Exception
     */
    public static function createPaymentFromPaymentIntent(Model $Billable, PaymentIntent $PaymentIntent): Model
    {
        // validate the payment intent
        self::validatePaymentIntent($PaymentIntent);

        // create payment
        $Payment = $Billable->payments()
            ->create([

                // default fields
                'provider'               => self::PAYMENT_PROVIDER,
                'currency'               => $PaymentIntent->currency,

                // TODO: resolve propperly against configuration
                'status'                 => $PaymentIntent->status,

                // TODO: find out if we can use `amount` or if we have to use `amount_received` instead. Maybe we even
                //       need both of them.
                'amount_raw'             => $PaymentIntent->amount,

                // description of the payment used in invoice
                'description'            => $PaymentIntent->description,

                // stripe fields
                'stripe_payment_intent'  => $PaymentIntent->id,
                'stripe_pm_token'        => $PaymentIntent->payment_method,
                'stripe_customer'        => $PaymentIntent->customer,
                'stripe_status'          => $PaymentIntent->status,

                // TODO: find out if we can use `amount` or if we have to use `amount_received` instead. Maybe we even
                //       need both of them.
                'stripe_amount_received' => $PaymentIntent->amount_received,

                // TODO: find out if we need the transaction or the carge
                'stripe_charge'          => $PaymentIntent->charges['data'][0]['id'],
                'stripe_transaction'     => $PaymentIntent->charges['data'][0]['balance_transaction'],
            ]);

        return $Payment;
    }

    /**
     * Create DB Payment from Stripe Charge
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     * @param \Stripe\Charge                      $Charge
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws Exception
     */
    public static function createPaymentFromCharge(Model $Billable, Charge $Charge): Model
    {
        // validate the payment intent
        self::validateCharge($Charge);

        // create payment
        $Payment = $Billable->payments()
            ->create([

                // default fields
                'provider'               => self::PAYMENT_PROVIDER,
                'currency'               => $Charge->currency,

                // TODO: resolve propperly against configuration
                'status'                 => $Charge->status,

                // TODO: find out if we can use `amount` or if we have to use `amount_received` instead. Maybe we even
                //       need both of them.
                'amount'                 => $Charge->amount,

                // stripe fields
                'stripe_payment_intent'  => $Charge->payment_intent ?? '',
                'stripe_pm_token'        => $Charge->payment_method,
                'stripe_customer'        => $Charge->customer,
                'stripe_status'          => $Charge->status,

                // TODO: find out if we can use `amount` or if we have to use `amount_received` instead. Maybe we even
                //       need both of them.
                'stripe_amount_received' => $Charge->amount,

                // TODO: find out if we need the transaction or the carge
                'stripe_charge'          => $Charge->id,
                'stripe_transaction'     => $Charge->balance_transaction,
            ]);

        return $Payment;
    }

    /**
     * @param Model $Payment
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function updatePaymentStatus(Model $Payment): Model
    {
        // retrieve payment (payment intent) from stripes end
        $PaymentIntent = PaymentIntent::retrieve($Payment->stripe_payment_intent, []);
        $Payment->update([
            'status'        => $PaymentIntent->status,
            'stripe_status' => $PaymentIntent->status,
        ]);

        return $Payment;
    }

    /**
     * Create Payment using Stripe data
     *
     * @param string $payment_id
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Exception
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function createPaymentByProviderPaymentID(string $payment_id): Model
    {
        // Get PaymentIntent from Stripe
        $PaymentIntent = PaymentIntent::retrieve($payment_id);

        // Get customer
        $billables = config('marqant-pay.billables', []);
        $Billable = collect($billables)->first();
        $Billable = $Billable::where('stripe_id', $PaymentIntent->customer)
            ->firstOrFail();

        return self::createPaymentFromPaymentIntent($Billable, $PaymentIntent);
    }

    /**
     * Get or create (if not exists) Invoice Payment using Stripe data
     *
     * @param array $invoice_data
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Exception
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function getPaymentByInvoice(array $invoice_data): Model
    {
        // Get customer
        $billables = config('marqant-pay.billables', []);
        $Billable = collect($billables)->first();
        $Billable = $Billable::where('stripe_id', $invoice_data['customer'])
            ->firstOrFail();

        // Get PaymentIntent and get Payment
        if (isset($invoice_data['payment_intent'])) {
            // Get PaymentIntent from Stripe
            $PaymentIntent = PaymentIntent::retrieve($invoice_data['payment_intent']);

            // Try to get Payment from database
            $Payment = Payment::where('stripe_payment_intent', $PaymentIntent->id)
                ->first();

            // Create Payment
            if (empty($Payment)) {
                return self::createPaymentFromPaymentIntent($Billable, $PaymentIntent);
            }

            return $Payment;
        }

        // Get Charge and get Payment
        if (isset($invoice_data['charge'])) {
            // Get Charge from Stripe
            $Charge = Charge::retrieve($invoice_data['charge']);
            if (isset($Charge->payment_intent)) {
                // Get PaymentIntent from Stripe
                $PaymentIntent = PaymentIntent::retrieve($Charge->payment_intent);

                // Try to get Payment from database
                $Payment = Payment::where('stripe_payment_intent', $PaymentIntent->id)
                    ->first();
            }

            // Create Payment
            if (empty($Payment)) {
                return self::createPaymentFromCharge($Billable, $Charge);
            }

            return $Payment;
        }

        throw new Exception('Can\'t get Payment using current Invoice data');
    }

    /**
     * Provide basic validation of a made payment intent.
     *
     * @param \Stripe\PaymentIntent $PaymentIntent
     *
     * @return void
     *
     * @throws \Exception
     */
    private static function validatePaymentIntent(PaymentIntent $PaymentIntent): void
    {
        // check that a payment method is attached to the payment intent
        if ($PaymentIntent->status === PaymentIntent::STATUS_REQUIRES_PAYMENT_METHOD) {
            throw new Exception('The payment failed because of an invalid payment method.');
        }
    }

    /**
     * Check if charge is failed.
     *
     * @param \Stripe\Charge $Charge
     *
     * @return void
     *
     * @throws \Exception
     */
    private static function validateCharge(Charge $Charge): void
    {
        if ($Charge->status === Charge::STATUS_FAILED) {
            throw new Exception('The charge is failed.');
        }
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

    /**
     * Create a plan on stripe.
     *
     * @param \Illuminate\Database\Eloquent\Model $Plan
     *
     * @return \Illuminate\Database\Eloquent\Model
     * @throws \Stripe\Exception\ApiErrorException
     * @throws \Exception
     */
    public function createPlan(Model $Plan): Model
    {
        /**
         * @var \Marqant\MarqantPaySubscriptions\Models\Plan $Plan
         */

        // connect plan with provider model
        $Provider = app(config('marqant-pay.provider_model'))
            ->where('slug', self::PAYMENT_PROVIDER)
            ->first();

        $Plan->providers()
            ->syncWithoutDetaching([$Provider->id]);

        // create plan on stripe
        $StripePlan = Plan::create([
            'amount'   => $Plan->amount_raw,
            'currency' => self::getCurrency(),
            'interval' => self::resolvePlanIntervalFromPlan($Plan),
            'product'  => [
                'name' => $Plan->name,
            ],
        ]);

        // update the values on the plan
        $Plan->update([
            'provider'       => self::PAYMENT_PROVIDER,
            'stripe_id'      => $StripePlan->id,
            'stripe_product' => $StripePlan->product,
        ]);

        return $Plan;
    }

    /**
     * Resolve the interval
     *
     * @param \Illuminate\Database\Eloquent\Model $Plan
     *
     * @return string
     * @throws \Exception
     */
    public static function resolvePlanIntervalFromPlan(Model $Plan): string
    {
        /**
         * @var \Marqant\MarqantPaySubscriptions\Models\Plan $Plan
         */

        $map = [
            'yearly'  => 'year',
            'monthly' => 'month',
        ];

        if (!key_exists($Plan->type, $map)) {
            throw new Exception('Could not resolve type on plan to stripe intveral.');
        }

        return $map[$Plan->type];
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

    /**
     * Subscribe a given Billable to a plan on the payment provider side.
     *
     * @param \Illuminate\Database\Eloquent\Model $Billable
     * @param \Illuminate\Database\Eloquent\Model $Plan
     *
     * @return \Illuminate\Database\Eloquent\Model
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function subscribe(Model &$Billable, Model $Plan): Model
    {
        /**
         * @var \Marqant\MarqantPaySubscriptions\Models\Subscription $SubscriptionModel
         * @var \App\User                                            $Billable
         */
        // create stripe subscription
        $customer = $Billable->stripe_id;
        $payment_method = $Billable->stripe_pm_token;
        $subscription = [
            'customer'               => $customer,
            'default_payment_method' => $payment_method,
            'items'                  => [
                [
                    'plan' => $Plan->stripe_id,
                ],
            ],
        ];
        $StripeSubscription = StripeSubscription::create($subscription);

        // create local subscription with data from stripe
        $Billable->subscriptions()
            ->create([
                'plan_id'   => $Plan->id,
                'stripe_id' => $StripeSubscription->id,
            ]);

        return $Billable;
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
