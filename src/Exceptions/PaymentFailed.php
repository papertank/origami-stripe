<?php

namespace Origami\Stripe\Exceptions;

use Origami\Stripe\Payment;

class PaymentFailure extends IncompletePayment
{
    /**
     * Create a new PaymentFailure instance.
     *
     * @param  \Origami\Stripe\Payment  $payment
     * @return self
     */
    public static function invalidPaymentMethod(Payment $payment)
    {
        return new self(
            $payment,
            'The payment attempt failed because of an invalid payment method.'
        );
    }

    /**
     * Create a new PaymentFailure instance.
     *
     * @param  \Origami\Stripe\Payment  $payment
     * @return self
     */
    public static function unableToCapture(Payment $payment)
    {
        return new self(
            $payment,
            'The capture attempt failed because of an invalid status.'
        );
    }
}
