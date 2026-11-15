<?php

namespace App\Http\Controllers;

use App\Models\Department;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            $departments = Department::query()
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('name')
                ->with(['services' => fn ($query) => $query->orderBy('name')])
                ->get(['id', 'name', 'email', 'status'])
                ->map(fn (Department $department): array => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'email' => $department->email,
                    'status' => $department->status,
                    'services' => $department->services->map(fn ($service): array => [
                        'id' => $service->id,
                        'name' => $service->name,
                        'code' => $service->code,
                        'location' => $service->location,
                        'isActive' => $service->is_active,
                    ])->values(),
                ]);

            return Inertia::render('admin/departments', [
                'departments' => $departments,
            ]);
        }

        abort_unless($user->role === 'owner', 403);

        $department = $user->department;
        abort_unless($department, 403);

        $services = $department->services()->orderBy('name')->get()->map(function ($service) {
            $tickets = $service->tickets()->whereDate('queue_date', today());

            return [
                'id' => $service->id,
                'name' => $service->name,
                'code' => $service->code,
                'location' => $service->location,
                'isActive' => $service->is_active,
                'isPaused' => $service->isCurrentlyPaused(),
                'pausedUntil' => $service->paused_until?->toIso8601String(),
                'waitingCount' => (clone $tickets)->where('status', 'waiting')->count(),
                'waiting' => (clone $tickets)
                    ->where('status', 'waiting')
                    ->orderBy('id')
                    ->limit(5)
                    ->get(['id', 'ticket_number', 'status']),
                'serving' => (clone $tickets)
                    ->where('status', 'serving')
                    ->first(['id', 'ticket_number', 'status']),
            ];
        });

        return Inertia::render('dashboard', [
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
                'status' => $department->status,
            ],
            'services' => $services,
        ]);
    }
}
