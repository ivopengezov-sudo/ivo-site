<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'duration_minutes',
        'price_cents',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'price_cents'      => 'integer',
        'is_active'        => 'boolean',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * Price formatted for display, e.g. "€45.00".
     */
    public function getFormattedPriceAttribute(): string
    {
        return '€' . number_format($this->price_cents / 100, 2);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
