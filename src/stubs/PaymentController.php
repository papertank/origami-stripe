<?php

namespace App\Http\Controllers\Ajax;

use Origami\Stripe\Exceptions\PaymentFailed;
use Origami\Stripe\Exceptions\PaymentActionRequired;
use Origami\Stripe\Payment;
use Stripe\Exception\ApiErrorException;

class PaymentController extends Controller
{
    protected function store(Request $request)
    {
        $this->validate($request, [
            'payment_method_id' => ['required_without:payment_intent_id'],
            'payment_intent_id' => ['required_without:payment_method_id'],
        ]);

        try {
            $payment = null;

            if ($id = $request->input('payment_intent_id')) {
                $payment = Payment::find($id);
            } elseif ($method = $request->input('payment_method_id')) {
                $amount = $request->input('amount');
                $data = [
                    'amount' => $amount,
                    'currency' => 'gbp',
                    'payment_method' => $method,
                    'capture_method' => 'manual',
                    'payment_method_types' => ['card'],
                    'confirmation_method' => 'manual',
                    'confirm' => true,
                ];
                if ($request->input('save_card')) {
                    $data['setup_future_usage'] = 'on_session';
                }
                $payment = Payment::create($data);
            }

            if (!$payment) {
                throw new Exception('Error fetching Stripe payment');
            }

            $payment->validate();

            return response([
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
