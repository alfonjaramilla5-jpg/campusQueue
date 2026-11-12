<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentRegistrationController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('campusqueue/department-register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'department_name' => ['required', 'string', 'max:255'],
            'department_email' => ['required', 'email', 'max:255', 'unique:departments,email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        DB::transaction(function () use ($validated): void {
            $owner = User::create([
                'name' => $validated['department_name'],
                'email' => $validated['department_email'],
                'password' => Hash::make($validated['password']),
                'role' => 'owner',
            ]);

            Department::create([
                'name' => $validated['department_name'],
                'email' => $validated['department_email'],
                'owner_id' => $owner->id,
            ]);
        });

        return redirect()->route('login')->with('status', 'Department application submitted. You can sign in after an administrator approves it.');
    }

    public function index(Request $request): Response
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $departments = Department::query()
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'status']);

        return Inertia::render('admin/departments', [
            'departments' => $departments,
        ]);
    }

    public function approve(Request $request, Department $department): RedirectResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);

        $department->update(['status' => 'approved']);

        return back()->with('success', "{$department->name} approved.");
    }
}
