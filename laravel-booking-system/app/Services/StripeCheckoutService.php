<?php

namespace App\Services;

use App\Models\Booking;
use Stripe\Checkout\Session;
use Stripe\Stripe;

/**
 * Thin wrapper around the Stripe Checkout Session API, scoped to
 * turning a single Booking into a payable Stripe session.
 */
class StripeCheckoutService
{
    public function __construct(?string $secretKey = null)
    {
        Stripe::setApiKey($secretKey ?? config('services.stripe.secret'));
    }

    public function createSessionForBooking(Booking $booking): Session
    {
        $booking->loadMissing('service');

        return Session::create([
            'mode' => 'payment',
            'payment_method_types' => ['card'],
            'customer_email' => $booking->customer_email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $booking->service->price_cents,
                    'product_data' => [
                        'name' => $booking->service->name,
                        'description' => sprintf(
                            'Appointment on %s',
                            $booking->starts_at->format('Y-m-d H:i')
                        ),
                    ],
                ],
            ]],
            'metadata' => [
                'booking_id' => $booking->id,
            ],
            'success_url' => route('bookings.confirmation', $booking) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('bookings.create', $booking->service),
        ]);
    }
}
