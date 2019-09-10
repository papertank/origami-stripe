<?php

namespace Origami\Stripe\Exceptions;

use Exception;
use Throwable;
use Origami\Stripe\Payment;

class IncompletePayment extends Exception
{
    /**
     *
     * @var \Origami\Stripe\Payment
     */
    public $payment;

    /**
     * Create a new IncompletePayment instance.
     *
     * @param  \Origami\Stripe\Payment  $payment
     * @param  string  $message
     * @param  int  $code
     * @param  \Throwable|null  $previous
     * @return void
     */
    public function __construct(Payment $payment, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->payment = $payment;
    }
}
