<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Routes for Marqant Pay Stripe package
| webhooks
|
*/

Route::stripeWebhooks('stripe/webhook');
