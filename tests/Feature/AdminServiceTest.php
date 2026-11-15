<?php

use App\Models\Department;
use App\Models\Service;
use App\Models\User;

function createServiceDepartment(): Department
{
    $owner = User::factory()->create(['role' => 'owner']);

    return Department::create([
        'name' => 'Registrar',
        'email' => 'registrar@example.com',
        'status' => 'approved',
        'owner_id' => $owner->id,
    ]);
}

test('an administrator can create, update, deactivate, and reactivate services', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $department = createServiceDepartment();

    $this->actingAs($admin)
        ->post(route('admin.departments.services.store', $department), [
            'name' => 'Transcript Records',
            'location' => 'Window 1',
        ])
        ->assertRedirect();

    $service = Service::firstOrFail();
    expect($service->code)->toBe('TR')
        ->and($service->location)->toBe('Window 1');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/departments')
            ->where('departments.0.services.0.location', 'Window 1'));

    $this->put(route('admin.services.update', $service), [
        'name' => 'Transcript Records',
        'location' => 'Window 2',
    ])->assertRedirect();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'location' => 'Window 2',
    ]);

    $ticket = $service->tickets()->create([
        'ticket_number' => 'TR-001',
        'sequence' => 1,
        'tracking_token' => str_repeat('a', 64),
        'status' => 'completed',
        'queue_date' => today(),
    ]);

    $this->delete(route('admin.services.destroy', $service))->assertRedirect();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'is_active' => false,
    ]);
    $this->assertDatabaseHas('queue_tickets', ['id' => $ticket->id]);

    $this->patch(route('admin.services.restore', $service))->assertRedirect();

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'is_active' => true,
    ]);
});

test('an administrator cannot deactivate a service with active tickets', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $service = createServiceDepartment()->services()->create([
        'name' => 'Transcript Records',
        'code' => 'TR',
    ]);
    $service->tickets()->create([
        'ticket_number' => 'TR-001',
        'sequence' => 1,
        'tracking_token' => str_repeat('b', 64),
        'status' => 'waiting',
        'queue_date' => today(),
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.services.destroy', $service))
        ->assertSessionHasErrors('service');

    $this->assertDatabaseHas('services', [
        'id' => $service->id,
        'is_active' => true,
    ]);
});

test('only an administrator can manage services across departments', function () {
    $owner = User::factory()->create(['role' => 'owner']);
    $service = createServiceDepartment()->services()->create([
        'name' => 'Transcript Records',
        'code' => 'TR',
    ]);

    $this->actingAs($owner)
        ->put(route('admin.services.update', $service), [
            'name' => 'Transcript Records',
            'location' => 'Window 1',
        ])
        ->assertForbidden();
});
