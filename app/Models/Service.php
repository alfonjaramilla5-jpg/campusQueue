<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    protected $fillable = [
        'department_id',
        'name',
        'code',
        'location',
        'paused_until',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'paused_until' => 'datetime',
        ];
    }

    public function isCurrentlyPaused(): bool
    {
        return $this->paused_until !== null && $this->paused_until->isFuture();
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(QueueTicket::class);
    }
}
