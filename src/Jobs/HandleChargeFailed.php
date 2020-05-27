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
 * Class HandleChargeFailed
 *
 * @package Marqant\MarqantPayStripe\Jobs
 */
class HandleChargeFailed implements ShouldQueue
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
     * Occurs whenever a failed charge attempt occurs.
     *
     * @throws \Exception
     */
    public function handle()
    {
        // TODO need to complete implementation and check this webhook
//        $webhook_data = $this->webhookCall->payload;
//
//        \Log::debug('HandleChargeFailed data: ' . var_export($webhook_data, true));
//
//        // Get Payment
//        $Payment = MarqantPay::getPaymentByInvoice('stripe', $webhook_data['data']['object']);
//
//        MarqantPay::updatePaymentStatus($Payment, Payment::STATUS_FAILED);
//
//        MarqantPay::sendEmailFailedPayment($Payment);
//        MarqantPay::sendSupportEmailFailedPayment($Payment);
    }
}
