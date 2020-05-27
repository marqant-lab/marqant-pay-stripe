# Marqant Pay Stripe Gateway

This package makes the stripe payment provider available for the marqant/marqant-pay package.

## Instalation

You can just require this package through composer as follows.

```shell script
composer require marqant-lab/marqant-pay-stripe
```

Then you have to add the service infomration to the `services.php` configuration file of Laravel. To do so, update
 the configuration file as shown below.
 
```php
return [
    // other services
    // ...

    'stripe' => [
        'key'    => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
];
```

Next you have to add the environment variables. Go to your stripe dashboard and get your stripe key and secret, so
 you can add it to your `.env` file.

```dotenv
STRIPE_KEY=pk_test_iLokikJOvEuI2HlWgH4olf3P
STRIPE_SECRET=sk_test_BQokikJOvBiI2HlWgH4olfQ2
```

Now go ahead and enable the payment provider in the `marqant-pay.php` configuration file.

```php
return [
    /*
     |--------------------------------------------------------------------------
     | Gateways
     |--------------------------------------------------------------------------
     |
     | In this section you can define all payment gateways that you need for
     | your project.
     |
     */

    'gateways' => [
        'stripe' => \Marqant\MarqantPayStripe\StripePaymentGateway::class,
    ],
];
```

Next you will need to add the fields for stripe on the billables you setup in the `marqant-pay` setup. So run the
 following for each billable model that you have setup (or will setup).
 
```shell script
php artisan marqant-pay:migrations:stripe
```

If you are using the [marqant-lab/marqant-pay-subscriptions](https://github.com/marqant-lab/marqant-pay-subscriptions
) package to enable subscriptions, then you will need to add the `--subscriptions` flag to the choosen command.
 
```shell script
php artisan marqant-pay:migrations:stripe --subscriptions
```

Now you can run the migrations as usual.

```shell script
php artisan migrate
```

And that's it, you should be good to go now.

###WebHooks:

We using `spatie/laravel-stripe-webhooks` package for stripe webhooks.  

#####Configuration:  

Add `STRIPE_WEBHOOK_SECRET` to your .env  

You can find the secret used at the webhook 
configuration settings on the [Stripe dashboard](https://dashboard.stripe.com/account/webhooks).  

run `$ php artisan migrate` if you don't do it before.  

Go to your `your_project/app/Http/Middleware/VerifyCsrfToken.php` and add this row:  

```php
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/stripe/webhook'
    ];
```

Finally, take care of the routing:  
  At the Stripe dashboard you must configure at what url Stripe webhooks should hit your app.  

You should set it up to '/stripe/webhook'.  

Example of Endpoint URL:  
http://your.awesome.site/stripe/webhook  

Available stripe events:  
 - payment_intent.succeeded
 - invoice.payment_succeeded
 - payment_intent.payment_failed
 - charge.failed (not completed)

For 'payment_intent.payment_failed' you should set configs
 `'marqant-pay.payment_urls.base_url'` and `'marqant-pay.payment_urls.payment_sub_url'`,  
description at config file.  
You need also look at `'marqant-pay.support_emails'` config.

You can also add to project/resources/lang these keys for translate:  
 - "Here"  
 - "Payment failed."  
 - "Requires payment method."  
 - "You needs to update your payment method in the"  
Don't forget: it should be json file.

Example resources/lang/en.json:
```json
{
  "Payment failed.": "Payment failed translation."
}
```
 
They are used at emails for `'payment_intent.payment_failed'` event

That's all you need
