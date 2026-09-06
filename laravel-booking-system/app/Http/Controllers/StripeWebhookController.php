<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use UnexpectedValueException;

/**
 * Receives Stripe's webhook calls. Registered without the `web` middleware
 * group (no CSRF, no session) since Stripe is not a logged-in browser.
 */
class StripeWebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (UnexpectedValueException|SignatureVerificationException $e) {
            report($e);
            return response('Invalid payload or signature', 400);
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;

            $booking = Booking::where('stripe_checkout_session_id', $session->id)->first();

            if ($booking && $booking->status !== Booking::STATUS_PAID) {
                $booking->markPaid($session->payment_intent);
            }
        }

        return response('ok', 200);
    }
}
