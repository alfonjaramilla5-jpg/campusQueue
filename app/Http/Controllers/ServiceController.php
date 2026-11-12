<?php

namespace App\Http\Controllers;

use App\Models\Department;
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
        ]);

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

        $department->services()->create([
            'name' => $validated['name'],
            'code' => $code,
        ]);

        return back()->with('success', 'Service added.');
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
