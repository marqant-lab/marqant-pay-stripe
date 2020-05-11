<?php

namespace Marqant\MarqantPayStripe;

use Stripe\Stripe;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Marqant\MarqantPayStripe\Commands\MigrationsForStripe;

class MarqantPayStripeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->setupConfig();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupMigrations();

        $this->setupCommands();

        $this->setupStripe();

        $this->setupRoutes();
    }

    /**
     * Setup configuration in register method.
     *
     * @return void
     */
    private function setupConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/marqant-pay-stripe.php', 'marqant-pay-stripe');
        $this->mergeConfigFrom(__DIR__ . '/../config/stripe-webhooks.php', 'stripe-webhooks');
    }

    /**
     * Setup Stripe API.
     *
     * @return void
     */
    private function setupStripe()
    {
        // set application key
        Stripe::setApiKey(config('services.stripe.secret'));

        // set application info
        Stripe::setAppInfo('Marqant Pay', 'beta', 'https://github.com/marqant-lab/marqant-pay');

        // set api version to use
        Stripe::setApiVersion('2020-03-02');
    }

    /**
     * Setup migrations in boot method.
     *
     * @return void
     */
    private function setupMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * Setup commands in boot method.
     *
     * @return void
     */
    private function setupCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MigrationsForStripe::class,
            ]);
        }
    }

    /**
     * Register package routes.
     *
     * @return void
     */
    private function setupRoutes(): void
    {
        if (!$this->app->routesAreCached()) {
            // Fixed bug 'Attribute [stripeWebhooks] does not exist.'
            if (!property_exists('Illuminate\\Support\\Facades\\Route', 'stripeWebhooks')) {
                Route::macro('stripeWebhooks', function ($url) {
                    return Route::post($url, '\Spatie\StripeWebhooks\StripeWebhooksController');
                });
            }

            require __DIR__ . '/../routes/web.php';
        }
    }
}
