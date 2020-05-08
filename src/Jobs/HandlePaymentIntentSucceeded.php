<?php

namespace Marqant\MarqantPayStripe\Jobs;

use Illuminate\Bus\Queueable;
use Marqant\MarqantPay\Models\Payment;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Marqant\MarqantPay\Services\MarqantPay;
use Spatie\WebhookClient\Models\WebhookCall;

/**
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
        if (empty($webhook_data['data']['object']['id']))
            throw new \Exception('Empty PaymentIntent ID');

        $paymentID = $webhook_data['data']['object']['id'];
        $Payment = Payment::where('stripe_payment_intent', $paymentID)
            ->firstOrFail();

        MarqantPay::updatePaymentStatus($Payment);
    }

}
