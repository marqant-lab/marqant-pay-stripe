<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddStripeFieldsToPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_payment_intent')
                ->nullable();
            $table->string('stripe_pm_token')
                ->nullable();
            $table->string('stripe_customer')
                ->nullable();
            $table->string('stripe_status')
                ->nullable();
            $table->integer('stripe_amount_received')
                ->nullable();
            $table->string('stripe_charge')
                ->nullable();
            $table->string('stripe_transaction')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'stripe_transaction',
                'stripe_payment_intent',
            ]);
        });
    }

    /**
     * Method to resolve the table on which to add the stripe fields for the payments.
     */
    private function getPaymentsTable(): string
    {
        $model = config('marqant-pay.payment_model');

        return app($model)->getTable();
    }
}
