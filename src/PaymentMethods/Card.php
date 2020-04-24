<?php

namespace Marqant\MarqantPayStripe\PaymentMethods;

use Stripe\Token;
use Stripe\PaymentMethod;
use Marqant\MarqantPay\Contracts\PaymentMethodContract;

class Card extends PaymentMethodContract
{
    /**
     * @var string The type of this payment.
     */
    protected string $type = 'card';

    /**
     * @var string
     */
    protected string $provider_type = 'card';

    /**
     * @var array The details from the implemented payment method.
     */
    protected array $details;

    /**
     * @var string The class this payment method is supposed to represent.
     */
    protected string $provider_object = Token::class;

    /**
     * Create the provider object based on the provider_object attribute of the given payment method.
     *
     * @param array $details
     *
     * @return \Stripe\PaymentMethod
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function createProviderObject(array $details): PaymentMethod
    {
        return PaymentMethod::retrieve($details['token']);
    }
}