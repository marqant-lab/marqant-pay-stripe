<?php
/**
 * HandlePaymentIntentSucceededTest.php code file.
 * User: Dmytro Sydorenko (dmytro@du.digital)
 * Date: 2020-05-12
 */

namespace Marqant\MarqantPayStripe\Tests\Jobs;

use Stripe\PaymentIntent;
use Spatie\WebhookClient\Models\WebhookCall;
use Marqant\MarqantPayStripe\Tests\MarqantPayStripeTestCase;
use Marqant\MarqantPayStripe\Jobs\HandlePaymentIntentSucceeded;

/**
 * Class HandlePaymentIntentSucceededTest
 *
 * @package Marqant\MarqantPayStripe\Tests\Jobs
 */
class HandlePaymentIntentSucceededTest extends MarqantPayStripeTestCase
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
    public function test_webhook_payment_intent_succeeded()
    {
        /**
         * @var \App\User $User
         */

        $amount = 9.99; // 9,99 ($|€|...)

        $description = 'test webhook event \'payment_intent.succeeded\'';

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
            'name'    => 'stripe',
            'payload' => [
                'type'     => 'payment_intent.succeeded',
                "livemode" => false,
                'data'     => [
                    'object' => [
                        'id' => $Payment->stripe_payment_intent,
                    ],
                ],
            ],
        ]);

        // create webhook
        $processStripeWebhookJob = new HandlePaymentIntentSucceeded($WebhookCall);
        // fires webhook
        $processStripeWebhookJob->handle();

        // update data from database
        $Payment->fresh();

        // check if Payment status 'succeeded'
        $this->assertEquals(PaymentIntent::STATUS_SUCCEEDED, $Payment->status);
    }

}
