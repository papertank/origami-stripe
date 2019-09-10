<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Support\Str;
use Origami\Stripe\Exceptions\PaymentFailed;
use Origami\Stripe\Exceptions\PaymentActionRequired;
use Origami\Stripe\Payment;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $this->validate($request, [
            'payment_method' => ['required', 'in:stripe'],
        ], [
            'payment_method.required' => 'Please select a payment method',
        ]);

        $paymentMethod = $request->input('payment_method');

        return $this->{'handle' . Str::studly($paymentMethod)};
    }

    protected function handleStripe(Request $request)
    {
        $this->validate($request, [
            'payment_method_id' => ['required_without:payment_intent_id'],
            'payment_intent_id' => ['required_without:payment_method_id'],
        ]);

        try {
            if ($id = $request->input('payment_intent_id')) {
                $payment = Payment::find($id);
                $payment->intent()->confirm();
            } else {
                $payment = new Payment(PaymentIntent::create([
                    'payment_method' => $request->input('payment_method_id'),
                    'amount' => $request->input('amount'),
                    'currency' => 'gbp',
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                ]));
            }

            $payment->validate();

            return response()->json([
                'payment_intent_id' => $payment->id,
                'success' => true,
            ]);
        } catch (PaymentActionRequired $e) {
            return response()->json([
                'requires_action' => true,
                'payment_intent_client_secret' => $e->payment->clientSecret(),
                'success' => false,
            ], 200);
        } catch (PaymentFailed $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'success' => false,
            ], 500);
        } catch (Exception $e) {
            Log::error($e);

            return response()->json([
                'error' => 'An unknown error occurred',
                'success' => false,
            ], 500);
        }
    }
}
