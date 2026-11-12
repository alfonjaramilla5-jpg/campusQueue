<?php

use App\Http\Controllers\DashboardController;
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
            ->get(['id', 'name']),
    ]);
})->name('home');

Route::get('/departments/register', [DepartmentRegistrationController::class, 'create'])->name('departments.register');
Route::post('/departments/register', [DepartmentRegistrationController::class, 'store'])->name('departments.store');
Route::post('/services/{service}/tickets', [QueueController::class, 'join'])->name('tickets.join');
Route::get('/tickets/{token}', [QueueController::class, 'show'])->name('tickets.show');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/admin/departments', [DepartmentRegistrationController::class, 'index'])->name('admin.departments');
    Route::post('/admin/departments/{department}/approve', [DepartmentRegistrationController::class, 'approve'])->name('admin.departments.approve');
    Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
    Route::post('/staff/services/{service}/call-next', [QueueController::class, 'callNext'])->name('staff.services.call-next');
    Route::post('/staff/tickets/{ticket}/complete', [QueueController::class, 'complete'])->name('staff.tickets.complete');
});

require __DIR__.'/settings.php';
