<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Staff-facing bookings overview. Routes are protected by the
 * 'auth' and 'admin' middleware (see routes/web.php).
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::with('service')
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('starts_at')
            ->paginate(20);

        return view('admin.dashboard', [
            'bookings' => $bookings,
            'statusFilter' => $request->query('status'),
        ]);
    }

    public function updateStatus(Request $request, Booking $booking): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:pending,paid,cancelled,completed'],
        ]);

        $booking->update(['status' => $data['status']]);

        return back()->with('status', "Booking #{$booking->id} marked as {$data['status']}.");
    }
}
