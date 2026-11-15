<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ServiceController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $department = $this->approvedDepartment($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $this->createService($department, $validated);

        return back()->with('success', 'Service added.');
    }

    public function storeForDepartment(Request $request, Department $department): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($department->status === 'approved', 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $this->createService($department, $validated);

        return back()->with('success', 'Service added.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);

        $service->update([
            'name' => $validated['name'],
            'location' => $validated['location'] ?? null,
        ]);

        return back()->with('success', 'Service updated.');
    }

    public function destroy(Request $request, Service $service): RedirectResponse
    {
        $this->authorizeAdmin($request);

        if ($service->tickets()->whereIn('status', ['waiting', 'serving'])->exists()) {
            return back()->withErrors([
                'service' => 'Complete or cancel active tickets before deactivating this service.',
            ]);
        }

        $service->update(['is_active' => false]);

        return back()->with('success', 'Service deactivated. Ticket history was preserved.');
    }

    public function restore(Request $request, Service $service): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($service->department()->where('status', 'approved')->exists(), 404);

        $service->update(['is_active' => true]);

        return back()->with('success', 'Service activated.');
    }

    private function createService(Department $department, array $validated): Service
    {
        $words = preg_split('/\s+/', trim($validated['name'])) ?: [];
        $code = Str::upper(implode('', array_map(
            fn (string $word): string => Str::substr($word, 0, 1),
            $words,
        )));
        $code = Str::substr($code ?: Str::upper(Str::substr($validated['name'], 0, 3)), 0, 12);
        $baseCode = $code;
        $suffix = 2;

        while ($department->services()->where('code', $code)->exists()) {
            $code = Str::substr($baseCode, 0, 9).'-'.$suffix++;
        }

        return $department->services()->create([
            'name' => $validated['name'],
            'code' => $code,
            'location' => $validated['location'] ?? null,
        ]);
    }

    private function approvedDepartment(Request $request): Department
    {
        $user = $request->user();
        abort_unless($user?->role === 'owner', 403);

        $department = $user->department;
        abort_unless($department && $department->status === 'approved', 403);

        return $department;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403);
    }
}
