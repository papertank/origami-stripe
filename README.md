# Origami Stripe Package

This package is a helper for Laravel projects using Stripe's PaymentIntents API and manual confirmation SCA setup.

It was inspired by the SCA updates and logic on the [Laravel Cashier](https://github.com/laravel/cashier) package.

## Installation

Install this package through Composer.

```
composer require origami/stripe
```

### Requirements

This package is designed to work with Laravel >= 5.8 projects currently.

### Setup

1. You should add a your stripe keys to your `config/services.php` file:

```php
    
    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'key' => env('STRIPE_KEY'),
        'webhook' => [
            'secret' => env('STRIPE_WEBHOOK_SECRET'),
        ]
    ],

```

2. Update your `.env` file with the key and secret:

```
STRIPE_KEY=
STRIPE_SECRET=
STRIPE_WEBHOOK_SECRET=
```

## Usage

### Payment object

```php
// Make a Payment from a payment_intent ID. 
$payment = Origami\Stripe\Payment::find($id);

// Or create a Payment as a new payment_intent
$payment = Origami\Stripe\Payment::create([
    'amount' => 1000,
    'currency' => 'gbp',
    'payment_method' => $method,
    'capture_method' => 'manual',
    'payment_method_types' => ['card'],
    'confirmation_method' => 'manual',
    'confirm' => true,
]));
```

### Available Methods

#### Get amount

Returns amount as `Money\Money` object using [moneyphp/money](https://github.com/moneyphp/money)

```php
$payment->amount();
```

### Check status

Returns a boolean:

```php
public function hasStatus($status)
```

You can pass an array or a string:

```php
$payment->hasStatus(['requires_confirmation','requires_capture']);
$payment->hasStatus('requires_confirmation');
```

There are also helper methods for the PaymentIntent statuses:

```php
$payment->requiresConfirmation();
$payment->requiresPaymentMethod();
$payment->requiresCapture();
$payment->requiresAction();
$payment->isCancelled();
$payment->isSucceeded();
$payment->isSuccessful(); // Alias for above
```

### Validate PaymentIntent

See [https://stripe.com/docs/payments/payment-intents/web-manual#creating-with-manual-confirmation](https://stripe.com/docs/payments/payment-intents/web-manual#creating-with-manual-confirmation)

```php
try {
    
    $payment = Origami\Stripe\Payment::create([
        'amount' => 1000,
        'currency' => 'gbp',
        'payment_method' => $method,
        'capture_method' => 'manual',
        'payment_method_types' => ['card'],
        'confirmation_method' => 'manual',
        'confirm' => true,
    ]));

    if (!$payment) {
        throw new Exception('Error fetching Stripe payment');
    }

    $payment->validate();

    // PaymentIntent is valid
    // capture_method: manual above means we need to capture in another controller
    // capture_method: automatic above means the payment was successully taken

    return response([
        'payment_intent_id' => $payment->id,
        'success' => true,
    ]);

} catch (Origami\Stripe\PaymentActionRequired $e) {
    // Action is required on the client end - see Stripe docs.
    return response()->json([
        'requires_action' => true,
        'payment_intent_client_secret' => $e->payment->clientSecret(),
        'success' => false,
    ], 200);
} catch (Origami\Stripe\PaymentFailed $e) {
    // Payment failed - handle on the client end.
    return response()->json([
        'error' => $e->getMessage(),
        'success' => false,
    ], 500);
} catch (Stripe\Exception\CardException $e) {
    // Don't forget to handle Stripe's exceptions for declined cards, etc.
    return response()->json([
        'error' => $e->getMessage(),
        'success' => false,
    ], 500);
} catch (Exception $e) {
    // Something else went wrong.
    Log::error($e);
    return response()->json(['error' => 'Unexpected error', 'success' => false], 500);
}
```

## Changelog

#### v1.1.0
- Bugfix: Renamed PaymentFailure exception to PaymentFailed.
- Added PaymentFailed::requiresConfirmation exception state.

## Author
[Papertank Limited](http://papertank.com)

## License
[MIT License](http://github.com/papertank/origami-stripe/blob/master/LICENSE)