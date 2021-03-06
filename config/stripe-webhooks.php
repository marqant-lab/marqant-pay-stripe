<?php

return [

    /*
     * Stripe will sign each webhook using a secret. You can find the used secret at the
     * webhook configuration settings: https://dashboard.stripe.com/account/webhooks.
     */
    'signing_secret' => env('STRIPE_WEBHOOK_SECRET'),

    /*
     * You can define the job that should be run when a certain webhook hits your application
     * here. The key is the name of the Stripe event type with the `.` replaced by a `_`.
     *
     * You can find a list of Stripe webhook types here:
     * https://stripe.com/docs/api#event_types.
     */
    'jobs' => [
        'payment_intent_succeeded'      => \Marqant\MarqantPayStripe\Jobs\HandlePaymentIntentSucceeded::class,
        'invoice_payment_succeeded'     => \Marqant\MarqantPayStripe\Jobs\HandleInvoicePaymentSucceeded::class,
        'payment_intent_payment_failed' => \Marqant\MarqantPayStripe\Jobs\HandlePaymentIntentPaymentFailed::class,
        'charge_failed'                 => \Marqant\MarqantPayStripe\Jobs\HandleChargeFailed::class,
    ],

    /*
     * The classname of the model to be used. The class should equal or extend
     * Spatie\StripeWebhooks\ProcessStripeWebhookJob.
     */
    'model' => \Spatie\StripeWebhooks\ProcessStripeWebhookJob::class,
];
