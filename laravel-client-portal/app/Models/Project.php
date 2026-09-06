<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    public const STATUS_SUBMITTED   = 'submitted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_NEEDS_INFO  = 'needs_info';
    public const STATUS_COMPLETED   = 'completed';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'admin_note',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function clientFiles(): HasMany
    {
        return $this->files()->where('source', ProjectFile::SOURCE_CLIENT);
    }

    public function deliverables(): HasMany
    {
        return $this->files()->where('source', ProjectFile::SOURCE_ADMIN);
    }

    public function scopeForClient($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeStatus($query, ?string $status)
    {
        return $query->when($status, fn ($q) => $q->where('status', $status));
    }
}
