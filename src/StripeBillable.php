<?php

namespace Origami\Stripe;

use Money\Money;
use Stripe\SetupIntent;
use Stripe\PaymentIntent;

trait StripeBillable
{
    /**
     * Make a "one off" charge on the customer for the given amount.
     *
     * @param  Money   $amount
     * @param  string  $paymentMethod
     * @param  array   $options
     * @return \Origami\Stripe\Payment
     */
    public function charge(Money $amount, $paymentMethod, array $options = [])
    {
        $options = array_merge([
            'confirmation_method' => 'automatic',
            'confirm' => true,
            'currency' => $this->preferredCurrency(),
        ], $options);

        $options['amount'] = $amount->getAmount();
        $options['payment_method'] = $paymentMethod;

        if ($this->stripe_id) {
            $options['customer'] = $this->stripe_id;
        }

        $payment = new Payment(
            PaymentIntent::create($options, $this->stripeOptions())
        );

        $payment->validate();

        return $payment;
    }

    /**
     * Refund a customer for a charge.
     *
     * @param  string  $paymentIntent
     * @param  array  $options
     * @return \Stripe\Refund
     */
    public function refund($paymentIntent, array $options = [])
    {
        $intent = PaymentIntent::retrieve($paymentIntent, $this->stripeOptions());

        return $intent->charges->data[0]->refund($options);
    }

    /**
     * Create a new SetupIntent instance.
     *
     * @param  array  $options
     * @return \Stripe\SetupIntent
     */
    public function createSetupIntent(array $options = [])
    {
        return SetupIntent::create(
            $options,
            $this->stripeOptions()
        );
    }

    public function preferredCurrency()
    {
        return 'GBP';
    }

    public function stripeOptions(array $options = [])
    {
        return array_merge([
            'api_key' => config('services.stripe.secret'),
        ], $options);
    }
}
