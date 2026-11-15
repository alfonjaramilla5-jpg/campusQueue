<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentDisplayController;
use App\Http\Controllers\DepartmentRegistrationController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ServiceController;
use App\Models\Department;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('campusqueue/home', [
        'departments' => Department::query()
            ->where('status', 'approved')
            ->with(['services' => fn ($query) => $query->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Department $department): array => [
                'id' => $department->id,
                'name' => $department->name,
                'services' => $department->services->map(fn ($service): array => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'isPaused' => $service->isCurrentlyPaused(),
                    'pausedUntil' => $service->paused_until?->toIso8601String(),
                ])->values(),
            ]),
    ]);
})->name('home');

Route::get('/departments/register', [DepartmentRegistrationController::class, 'create'])->name('departments.register');
Route::post('/departments/register', [DepartmentRegistrationController::class, 'store'])->name('departments.store');
Route::get('/display/{department}', DepartmentDisplayController::class)->name('departments.display');
Route::post('/services/{service}/tickets', [QueueController::class, 'join'])->name('tickets.join');
Route::get('/tickets/{token}', [QueueController::class, 'show'])->name('tickets.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/admin/departments', [DepartmentRegistrationController::class, 'index'])->name('admin.departments');
    Route::post('/admin/departments/{department}/approve', [DepartmentRegistrationController::class, 'approve'])->name('admin.departments.approve');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::post('/admin/departments/{department}/services', [ServiceController::class, 'storeForDepartment'])->name('admin.departments.services.store');
    Route::put('/admin/services/{service}', [ServiceController::class, 'update'])->name('admin.services.update');
    Route::delete('/admin/services/{service}', [ServiceController::class, 'destroy'])->name('admin.services.destroy');
    Route::patch('/admin/services/{service}/restore', [ServiceController::class, 'restore'])->name('admin.services.restore');
    Route::post('/staff/services/{service}/call-next', [QueueController::class, 'callNext'])->name('staff.services.call-next');
    Route::post('/staff/services/{service}/pause', [QueueController::class, 'pause'])->name('staff.services.pause');
    Route::post('/staff/services/{service}/resume', [QueueController::class, 'resume'])->name('staff.services.resume');
    Route::post('/staff/tickets/{ticket}/complete', [QueueController::class, 'complete'])->name('staff.tickets.complete');
    Route::post('/staff/tickets/{ticket}/skip', [QueueController::class, 'skip'])->name('staff.tickets.skip');
});

require __DIR__.'/settings.php';
