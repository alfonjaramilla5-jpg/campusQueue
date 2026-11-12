<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QueueTicket extends Model
{
    protected $fillable = [
        'service_id',
        'ticket_number',
        'sequence',
        'tracking_token',
        'status',
        'queue_date',
    ];

    protected function casts(): array
    {
        return [
            'queue_date' => 'date',
        ];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
