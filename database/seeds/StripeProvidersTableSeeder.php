<?php

namespace Marqant\MarqantPayStripe\Seeds;

use Illuminate\Database\Seeder;
use Marqant\MarqantPay\Models\Provider;

class StripeProvidersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // create stripe provider
        Provider::updateOrCreate([
            'slug' => 'stripe',
        ], [
            'name' => 'Stripe',
        ]);
    }
}