<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Booking extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_PAID      = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_COMPLETED = 'completed';

    protected $fillable = [
        'service_id',
        'customer_name',
        'customer_email',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'stripe_checkout_session_id',
        'stripe_payment_intent_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Bookings for a given service whose time range overlaps [$start, $end).
     * Cancelled bookings free up the slot again.
     */
    public function scopeOverlapping(Builder $query, int $serviceId, Carbon $start, Carbon $end): Builder
    {
        return $query
            ->where('service_id', $serviceId)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start);
    }

    public function markPaid(?string $paymentIntentId = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);
    }
}
