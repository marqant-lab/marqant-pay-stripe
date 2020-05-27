<?php

namespace Marqant\MarqantPayStripe\Jobs;


use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Marqant\MarqantPay\Models\Payment;
use Illuminate\Queue\InteractsWithQueue;
use Marqant\MarqantPay\Services\MarqantPay;
use Illuminate\Contracts\Queue\ShouldQueue;
use Spatie\WebhookClient\Models\WebhookCall;

/**
 * Class HandlePaymentIntentPaymentFailed
 *
 * @package Marqant\MarqantPayStripe\Jobs
 */
class HandlePaymentIntentPaymentFailed implements ShouldQueue
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
     * Occurs when a PaymentIntent has failed the attempt to create a payment method or a payment.
     *
     * @throws \Exception
     */
    public function handle()
    {
        $webhook_data = $this->webhookCall->payload;

        // Check for PaymentIntent ID'
        if (empty($webhook_data['data']['object']['id'])) {
            throw new \Exception('Empty Stripe PaymentIntent ID');
        }

        $payment_id = $webhook_data['data']['object']['id'];

        $Payment = Payment::where('stripe_payment_intent', $payment_id)
            ->firstOrFail();

        MarqantPay::updatePaymentStatus($Payment, Payment::STATUS_FAILED);

        MarqantPay::sendEmailFailedPayment($Payment);
        MarqantPay::sendSupportEmailFailedPayment($Payment);
    }
}
