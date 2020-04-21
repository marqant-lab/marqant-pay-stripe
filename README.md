# Marqant Pay Stripe Gateway

This package makes the stripe payment provider available for the marqant/marqant-pay package.

## Instalation

You can just require this package through composer as follows.

```shell script
composer require marqant/marqant-pay-stripe
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