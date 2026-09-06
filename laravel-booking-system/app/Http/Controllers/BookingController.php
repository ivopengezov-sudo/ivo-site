<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Service;
use App\Services\StripeCheckoutService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    /**
     * Public list of bookable services.
     */
    public function index(): View
    {
        return view('booking.index', [
            'services' => Service::active()->orderBy('name')->get(),
        ]);
    }

    /**
     * Show available slots for a service and the booking form.
     */
    public function create(Request $request, Service $service): View
    {
        $date = Carbon::parse($request->query('date', now()->addDay()->toDateString()));

        return view('booking.create', [
            'service' => $service,
            'date'    => $date,
            'slots'   => $this->availableSlots($service, $date),
        ]);
    }

    /**
     * Validate the requested slot, create a pending booking, and hand the
     * customer off to Stripe Checkout to pay for it.
     */
    public function store(Request $request, Service $service, StripeCheckoutService $checkout): RedirectResponse
    {
        $data = $request->validate([
            'customer_name'  => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'starts_at'      => ['required', 'date', 'after:now'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ]);

        $startsAt = Carbon::parse($data['starts_at']);
        $endsAt   = $startsAt->clone()->addMinutes($service->duration_minutes);

        $conflict = Booking::overlapping($service->id, $startsAt, $endsAt)->exists();

        if ($conflict) {
            return back()
                ->withInput()
                ->withErrors(['starts_at' => 'That time slot was just taken. Please pick another one.']);
        }

        $booking = Booking::create([
            'service_id'     => $service->id,
            'customer_name'  => $data['customer_name'],
            'customer_email' => $data['customer_email'],
            'starts_at'      => $startsAt,
            'ends_at'        => $endsAt,
            'notes'          => $data['notes'] ?? null,
            'status'         => Booking::STATUS_PENDING,
        ]);

        $session = $checkout->createSessionForBooking($booking);

        $booking->update(['stripe_checkout_session_id' => $session->id]);

        return redirect()->away($session->url);
    }

    public function confirmation(Booking $booking): View
    {
        return view('booking.confirmation', [
            'booking' => $booking->loadMissing('service'),
        ]);
    }

    /**
     * Generate candidate slots for a service on a given day (business hours
     * 09:00-17:00), excluding anything already booked.
     */
    private function availableSlots(Service $service, Carbon $date): array
    {
        $duration = $service->duration_minutes;

        $dayStart = $date->clone()->setTime(9, 0);
        $dayEnd   = $date->clone()->setTime(17, 0);

        $existing = Booking::where('service_id', $service->id)
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->whereBetween('starts_at', [$dayStart, $dayEnd])
            ->get(['starts_at', 'ends_at']);

        $slots = [];

        for ($slotStart = $dayStart->clone(); $slotStart->clone()->addMinutes($duration)->lte($dayEnd); $slotStart->addMinutes($duration)) {
            $slotEnd = $slotStart->clone()->addMinutes($duration);

            $taken = $existing->contains(
                fn ($booking) => $slotStart->lt($booking->ends_at) && $slotEnd->gt($booking->starts_at)
            );

            if (!$taken && $slotStart->isFuture()) {
                $slots[] = $slotStart->clone();
            }
        }

        return $slots;
    }
}
