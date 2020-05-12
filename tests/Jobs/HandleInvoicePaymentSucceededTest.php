<?php

namespace Marqant\MarqantPayStripe\Tests\Jobs;

use Stripe\PaymentIntent;
use Spatie\WebhookClient\Models\WebhookCall;
use Marqant\MarqantPayStripe\Tests\MarqantPayStripeTestCase;
use Marqant\MarqantPayStripe\Jobs\HandleInvoicePaymentSucceeded;

/**
 * Class HandleInvoicePaymentSucceededTest
 *
 * @package Marqant\MarqantPayStripe\Tests
 */
class HandleInvoicePaymentSucceededTest extends MarqantPayStripeTestCase
{

    /**
     * Test WebHook event 'invoice.payment_succeeded'.
     *
     * @group MarqantPayStripe
     *
     * @test
     *
     * @return void
     *
     * @throws \Exception
     */
    public function test_webhook_invoice_payment_succeeded()
    {
        /**
         * @var \App\User $User
         */

        $amount = 999; // 9,99 ($|€|...)

        $description = 'test webhook event \'invoice.payment_succeeded\'';

        // create fake customer through factory
        $User = $this->createBillableUser();

        // charge the user
        $Payment = $User->charge($amount, $description);

        // check that we got back an instance of Payment
        $this->assertInstanceOf(config('marqant-pay.payment_model'), $Payment);

        // check the amount
        $this->assertEquals($amount, $Payment->amount);

        // check if we billed the correct user
        $this->assertEquals($User->provider_id, $Payment->customer);

        // check that there is the right description
        $this->assertEquals($description, $Payment->description);

        $WebhookCall = WebhookCall::create([
            'name' => 'stripe',
            'payload' => [
                'type' => 'invoice.payment_succeeded',
                "livemode" => false,
                'data' => [
                    'object' => [
                        'customer' => $User->stripe_id,
                        'payment_intent' => null,
                        'charge' => $Payment->stripe_charge,
                    ]
                ]
            ],
        ]);

        // create webhook
        $processStripeWebhookJob = new HandleInvoicePaymentSucceeded($WebhookCall);
        // fires webhook
        $processStripeWebhookJob->handle();

        // update data from database
        $Payment->refresh();

        // check if Payment status 'succeeded'
        $this->assertEquals(PaymentIntent::STATUS_SUCCEEDED, $Payment->status);

        // check if invoice pdf file were created
        $this->assertNotEmpty($Payment->invoice);
    }

}
