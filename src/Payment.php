<?php

namespace Origami\Stripe;

use Money\Money;
use Money\Currency;
use Stripe\PaymentIntent;
use Origami\Stripe\Exceptions\PaymentFailure;
use Origami\Stripe\Exceptions\PaymentActionRequired;

class Payment
{
    /**
     * The Stripe PaymentIntent instance.
     *
     * @var \Stripe\PaymentIntent
     */
    protected $paymentIntent;

    /**
     * Create a new Payment instance.
     *
     * @param  \Stripe\PaymentIntent  $paymentIntent
     * @return void
     */
    public function __construct(PaymentIntent $paymentIntent)
    {
        $this->paymentIntent = $paymentIntent;
    }

    /**
     * Create a new Payment instance
     *
     * @return Payment
     */
    public static function create($params = null, $options = null)
    {
        return new static(PaymentIntent::create($params, $options));
    }

    /**
     * Create a new Payment instance from a PaymentIntent id
     *
     * @return Payment
     */
    public static function find($id)
    {
        return new static(PaymentIntent::retrieve($id));
    }

    /**
     * The Stripe PaymentIntent id
     *
     * @return string
     */
    public function id()
    {
        return $this->paymentIntent->id;
    }

    /**
     * Get the total amount that will be paid.
     *
     * @return Money
     */
    public function amount()
    {
        return new Money($this->rawAmount(), new Currency(strtoupper($this->paymentIntent->currency)));
    }

    /**
     * Get the raw total amount that will be paid.
     *
     * @return int
     */
    public function rawAmount()
    {
        return $this->paymentIntent->amount;
    }

    /**
     * The Stripe PaymentIntent client secret.
     *
     * @return string
     */
    public function clientSecret()
    {
        return $this->paymentIntent->client_secret;
    }

    /**
     * Determine if the payment has the given statuses
     *
     * @param array|string $status
     * @return bool
     */
    public function hasStatus($status)
    {
        if (is_array($status)) {
            return in_array($this->paymentIntent->status, $status);
        }

        return $this->paymentIntent->status == $status;
    }

    /**
     * Determine if the payment needs to be confirmed.
     *
     * @return bool
     */
    public function requiresConfirmation()
    {
        return $this->hasStatus('requires_confirmation');
    }

    /**
     * Determine if the payment needs a valid payment method.
     *
     * @return bool
     */
    public function requiresPaymentMethod()
    {
        return $this->hasStatus('requires_payment_method');
    }

    /**
     * Determine if the payment requires capture
     *
     * @return bool
     */
    public function requiresCapture()
    {
        return $this->hasStatus('requires_capture');
    }

    /**
     * Determine if the payment needs an extra action like 3D Secure.
     *
     * @return bool
     */
    public function requiresAction()
    {
        return $this->hasStatus('requires_action');
    }

    /**
     * Determine if the payment was cancelled.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return $this->hasStatus('canceled');
    }

    /**
     * Determine if the payment was successful.
     *
     * @return bool
     */
    public function isSucceeded()
    {
        return $this->hasStatus('succeeded');
    }

    /**
     * Determine if the payment was successful.
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->isSucceeded();
    }

    /**
     * Validate if the payment intent was successful and throw an exception if not.
     *
     * @return void
     *
     * @throws \Origami\Stripe\Exceptions\PaymentActionRequired
     * @throws \Origami\Stripe\Exceptions\PaymentFailure
     */
    public function validate()
    {
        if ($this->requiresPaymentMethod()) {
            throw PaymentFailure::invalidPaymentMethod($this);
        } elseif ($this->requiresAction()) {
            throw PaymentActionRequired::incomplete($this);
        }
    }

    public function confirm($params = null, $options = null)
    {
        $this->paymentIntent->confirm();

        return $this;
    }

    public function capture(Money $amount = null, array $params = [], $options = null)
    {
        if (!$this->hasStatus('requires_capture')) {
            throw PaymentFailure::unableToCapture($this);
        }

        if (!$amount) {
            $amount = $this->amount();
        }

        $params = array_merge($params, [
            'amount_to_capture' => $amount->getAmount()
        ]);

        $this->paymentIntent->capture($params);

        return $this;
    }

    public function charges()
    {
        return $this->paymentIntent->charges->data ?? [];
    }

    public function getLastSuccessfulCharge()
    {
        foreach ($this->charges() as $charge) {
            if ($charge->paid == true) {
                return $charge;
            }
        }

        return null;
    }

    /**
     * The Stripe PaymentIntent instance.
     *
     * @return \Stripe\PaymentIntent
     */
    public function asPaymentIntent()
    {
        return $this->paymentIntent;
    }

    /**
     * The Stripe PaymentIntent instance.
     *
     * @return \Stripe\PaymentIntent
     */
    public function intent()
    {
        return $this->asPaymentIntent();
    }

    /**
     * Dynamically get values from the Stripe PaymentIntent.
     *
     * @param  string  $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->paymentIntent->{$key};
    }
}
