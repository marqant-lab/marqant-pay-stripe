<?php

namespace Marqant\MarqantPayStripe\Jobs;

use Illuminate\Bus\Queueable;
use Marqant\MarqantPay\Models\Payment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Marqant\MarqantPay\Services\MarqantPay;
use Spatie\WebhookClient\Models\WebhookCall;
use Illuminate\Queue\{SerializesModels, InteractsWithQueue};

/**
 * WebHook for processing 'payment_intent.succeeded' event from Stripe
 *
 * Class HandleChargeSucceeded
 *
 * @package Marqant\MarqantPayStripe\Jobs\StripeWebhooks
 */
class HandlePaymentIntentSucceeded implements ShouldQueue
{

    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var \Spatie\WebhookClient\Models\WebhookCall
     */
    public $webhookCall;

    public function __construct(WebhookCall $webhookCall)
    {
        $this->webhookCall = $webhookCall;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $webhook_data = $this->webhookCall->payload;

        // Check for PaymentIntent ID'
        if (empty($webhook_data['data']['object']['id']))
            throw new \Exception('Empty Stripe PaymentIntent ID');

        $paymentID = $webhook_data['data']['object']['id'];
        $Payment = Payment::where('stripe_payment_intent', $paymentID)
            ->first();

        // Create Payment if not exists
        if (empty($Payment)) {
            $Payment = MarqantPay::createPaymentByProviderPaymentID('stripe', $paymentID);
        }

        // Update Payment status
        MarqantPay::updatePaymentStatus($Payment);
    }

}
