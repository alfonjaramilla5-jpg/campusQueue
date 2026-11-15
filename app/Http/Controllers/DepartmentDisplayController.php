<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentDisplayController extends Controller
{
    public function __invoke(Department $department): Response
    {
        abort_unless($department->status === 'approved', 404);

        $services = $department->services()
            ->where('is_active', true)
            ->with(['tickets' => fn (HasMany $query) => $query
                ->whereDate('queue_date', today())
                ->orderBy('sequence')
                ->select(['id', 'service_id', 'ticket_number', 'sequence', 'status'])])
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'location', 'paused_until'])
            ->map(function (Service $service): array {
                $currentTicket = $service->tickets->first(
                    fn (QueueTicket $ticket) => in_array($ticket->status, ['waiting', 'serving'], true),
                );
                $batchNumber = $currentTicket
                    ? intdiv($currentTicket->sequence - 1, 5) + 1
                    : null;

                $tickets = $batchNumber === null
                    ? collect()
                    : $service->tickets
                        ->filter(fn (QueueTicket $ticket) => in_array($ticket->status, ['waiting', 'serving'], true)
                            && intdiv($ticket->sequence - 1, 5) + 1 === $batchNumber)
                        ->map(fn (QueueTicket $ticket): array => [
                            'ticketNumber' => $ticket->ticket_number,
                            'status' => $ticket->status,
                        ])
                        ->values();

                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'code' => $service->code,
                    'location' => $service->location,
                    'isPaused' => $service->isCurrentlyPaused(),
                    'pausedUntil' => $service->paused_until?->toIso8601String(),
                    'batchNumber' => $batchNumber,
                    'tickets' => $tickets,
                ];
            });

        return Inertia::render('campusqueue/display', [
            'department' => [
                'name' => $department->name,
            ],
            'services' => $services,
        ]);
    }
}
