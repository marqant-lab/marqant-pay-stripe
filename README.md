# Marqant Pay Stripe Gateway

This package makes the stripe payment provider available for the marqant/marqant-pay package.

## Instalation

You can just require this package through composer as follows.

```shell script
composer require marqant/marqant-pay-stripe
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
php artisan marqant-pay:migrations:stripe App\\User
# or
php artisan marqant-pay:migrations:stripe "App\User"
```

Now you can run the migrations as usual.

```shell script
php artisan migrate
```