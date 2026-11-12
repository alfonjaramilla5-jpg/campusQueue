<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\QueueTicket;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class QueueController extends Controller
{
    public function join(Request $request, Service $service): RedirectResponse
    {
        abort_unless($service->is_active && $service->department()->where('status', 'approved')->exists(), 404);

        $ticket = DB::transaction(function () use ($service): QueueTicket {
            $lockedService = Service::query()->lockForUpdate()->findOrFail($service->id);
            $today = today();
            $sequence = (int) $lockedService->tickets()
                ->whereDate('queue_date', $today)
                ->max('sequence') + 1;

            return $lockedService->tickets()->create([
                'ticket_number' => sprintf('%s-%03d', $lockedService->code, $sequence),
                'sequence' => $sequence,
                'tracking_token' => Str::random(64),
                'status' => 'waiting',
                'queue_date' => $today,
            ]);
        });

        return redirect()->route('tickets.show', $ticket->tracking_token);
    }

    public function show(string $token): Response
    {
        $ticket = QueueTicket::query()
            ->with(['service.department'])
            ->where('tracking_token', $token)
            ->firstOrFail();

        $ahead = $ticket->status === 'waiting'
            ? QueueTicket::query()
                ->where('service_id', $ticket->service_id)
                ->whereDate('queue_date', $ticket->queue_date)
                ->where('status', 'waiting')
                ->where('id', '<', $ticket->id)
                ->count()
            : 0;

        $serving = QueueTicket::query()
            ->where('service_id', $ticket->service_id)
            ->whereDate('queue_date', $ticket->queue_date)
            ->where('status', 'serving')
            ->value('ticket_number');

        return Inertia::render('campusqueue/ticket', [
            'ticket' => [
                'number' => $ticket->ticket_number,
                'status' => $ticket->status,
                'department' => $ticket->service->department->name,
                'service' => $ticket->service->name,
                'date' => $ticket->queue_date->toDateString(),
            ],
            'ahead' => $ahead,
            'currentlyServing' => $serving,
        ]);
    }

    public function callNext(Request $request, Service $service): RedirectResponse
    {
        $department = $this->approvedDepartment($request);
        abort_unless($service->department_id === $department->id && $service->is_active, 404);

        $result = DB::transaction(function () use ($service): string {
            Service::query()->lockForUpdate()->findOrFail($service->id);
            $today = today();
            $tickets = QueueTicket::query()
                ->where('service_id', $service->id)
                ->whereDate('queue_date', $today);

            if ((clone $tickets)->where('status', 'serving')->exists()) {
                return 'already-serving';
            }

            $next = (clone $tickets)
                ->where('status', 'waiting')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $next) {
                return 'empty';
            }

            $next->update(['status' => 'serving']);

            return 'called';
        });

        if ($result === 'already-serving') {
            return back()->withErrors(['queue' => 'Complete the current ticket before calling the next one.']);
        }

        if ($result === 'empty') {
            return back()->withErrors(['queue' => 'There are no waiting tickets for this service.']);
        }

        return back()->with('success', 'Next ticket called.');
    }

    public function complete(Request $request, QueueTicket $ticket): RedirectResponse
    {
        $department = $this->approvedDepartment($request);
        abort_unless($ticket->service()->where('department_id', $department->id)->exists(), 404);

        $completedTicket = DB::transaction(function () use ($ticket): QueueTicket {
            Service::query()->lockForUpdate()->findOrFail($ticket->service_id);
            $lockedTicket = QueueTicket::query()->lockForUpdate()->findOrFail($ticket->id);
            abort_unless($lockedTicket->status === 'serving' && $lockedTicket->queue_date->isToday(), 409);
            $lockedTicket->update(['status' => 'completed']);

            return $lockedTicket;
        });

        return back()->with('success', "{$completedTicket->ticket_number} completed.");
    }

    private function approvedDepartment(Request $request): Department
    {
        $user = $request->user();
        abort_unless($user?->role === 'owner', 403);

        $department = $user->department;
        abort_unless($department && $department->status === 'approved', 403);

        return $department;
    }
}
