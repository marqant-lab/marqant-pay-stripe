<?php

namespace Marqant\MarqantPayStripe\PaymentMethods;

use Marqant\MarqantPay\Contracts\PaymentMethodContract;

class Card extends PaymentMethodContract
{
    /**
     * @inheritDoc
     */
    public static function make(array $details): PaymentMethodContract
    {
        // TODO: Implement make() method.
    }
}