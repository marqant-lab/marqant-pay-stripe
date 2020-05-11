<?php

namespace Marqant\MarqantPayStripe\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Marqant\MarqantPay\Services\MarqantPay;
use Spatie\WebhookClient\Models\WebhookCall;

/**
 * WebHook for processing 'invoice.payment_succeeded' event from Stripe
 *
 * Class HandleInvoicePaymentSucceeded
 *
 * @package Marqant\MarqantPayStripe\Jobs
 */
class HandleInvoicePaymentSucceeded implements ShouldQueue
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

        // Get Payment
        $Payment = MarqantPay::getPaymentByInvoice('stripe', $webhook_data['data']['object']);

        // Update Payment status in case not new Payment
        MarqantPay::updatePaymentStatus($Payment);

        /**
         * Create Invoice
         *
         * @var \Marqant\MarqantPay\Models\Payment $Payment
         */
        $Payment->createInvoice();
    }

}
