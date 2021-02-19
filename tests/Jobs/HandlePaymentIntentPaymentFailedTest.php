<?php

namespace Marqant\MarqantPayStripe\Tests\Jobs;

use Marqant\MarqantPay\Models\Payment;
use Spatie\WebhookClient\Models\WebhookCall;
use Marqant\MarqantPayStripe\Tests\MarqantPayStripeTestCase;
use Marqant\MarqantPayStripe\Jobs\HandlePaymentIntentPaymentFailed;

/**
 * Class HandlePaymentIntentPaymentFailedTest
 *
 * @package Marqant\MarqantPayStripe\Tests\Jobs
 */
class HandlePaymentIntentPaymentFailedTest extends MarqantPayStripeTestCase
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
    public function test_webhook_payment_intent_payment_failed()
    {
        /**
         * @var \App\User $User
         */

        $amount = 9.99; // 9,99 ($|€|...)

        $description = 'test webhook event \'payment_intent.payment_failed\'';

        // create fake customer through factory
        $User = $this->createBillableUser();

        // charge the user
        $Payment = $User->charge($amount, null, $description);

        // check that we got back an instance of Payment
        $this->assertInstanceOf(config('marqant-pay.payment_model'), $Payment);

        // check the amount
        $this->assertEquals($amount, $Payment->amount);

        // check if we billed the correct user
        $this->assertEquals($User->provider_id, $Payment->customer);

        // check that there is the right description
        $this->assertEquals($description, $Payment->description);

        $WebhookCall = WebhookCall::create([
            'name'    => 'stripe',
            'payload' => [
                'type'     => 'payment_intent.payment_failed',
                'livemode' => false,
                'data'     => [
                    'object' => [
                        'id' => $Payment->stripe_payment_intent,
                    ],
                ],
            ],
        ]);

        // create webhook
        $processStripeWebhookJob = new HandlePaymentIntentPaymentFailed($WebhookCall);
        // fires webhook
        $processStripeWebhookJob->handle();

        // update data from database
        $Payment->refresh();

        // check if Payment status 'failed'
        $this->assertEquals(Payment::STATUS_FAILED, $Payment->status);
    }
}
