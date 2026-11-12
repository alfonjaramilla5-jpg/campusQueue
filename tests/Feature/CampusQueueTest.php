<?php

use App\Models\Department;
use App\Models\QueueTicket;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

function createApprovedDepartment(string $name = 'Registrar'): array
{
    $owner = User::factory()->create(['role' => 'owner']);
    $department = Department::create([
        'name' => $name,
        'email' => strtolower($name).'@example.com',
        'status' => 'approved',
        'owner_id' => $owner->id,
    ]);

    return [$owner, $department];
}

test('a department can apply and waits for administrator approval', function () {
    $response = $this->post(route('departments.store'), [
        'department_name' => 'Registrar',
        'department_email' => 'registrar@example.com',
        'password' => 'QueuePassword123!',
        'password_confirmation' => 'QueuePassword123!',
    ]);

    $response->assertRedirect(route('login'));
    $this->assertDatabaseHas('departments', [
        'name' => 'Registrar',
        'status' => 'pending',
    ]);
    $this->assertDatabaseHas('users', [
        'name' => 'Registrar',
        'email' => 'registrar@example.com',
        'role' => 'owner',
    ]);
    expect(Hash::check('QueuePassword123!', User::where('email', 'registrar@example.com')->firstOrFail()->password))->toBeTrue();
});

test('only an administrator can approve department applications', function () {
    [$owner, $department] = createApprovedDepartment('Library');
    $department->update(['status' => 'pending']);
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($owner)
        ->post(route('admin.departments.approve', $department))
        ->assertForbidden();

    $this->actingAs($admin)
        ->post(route('admin.departments.approve', $department))
        ->assertRedirect();

    expect($department->fresh()->status)->toBe('approved');
});

test('an approved department owner can create services with a generated queue code', function () {
    [$owner, $department] = createApprovedDepartment();

    $this->actingAs($owner)
        ->post(route('services.store'), ['name' => 'Transcript of Records'])
        ->assertRedirect();

    $this->assertDatabaseHas('services', [
        'department_id' => $department->id,
        'name' => 'Transcript of Records',
        'code' => 'TOR',
    ]);
});

test('visitors can join a service queue and privately track their ticket', function () {
    [, $department] = createApprovedDepartment();
    $service = $department->services()->create([
        'name' => 'General Inquiries',
        'code' => 'GI',
    ]);

    $this->post(route('tickets.join', $service))->assertRedirect();
    $ticket = QueueTicket::firstOrFail();

    expect($ticket->ticket_number)->toBe('GI-001')
        ->and(strlen($ticket->tracking_token))->toBe(64)
        ->and($ticket->status)->toBe('waiting');

    $this->get(route('tickets.show', $ticket->tracking_token))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('campusqueue/ticket')
            ->where('ticket.number', 'GI-001')
            ->where('ahead', 0));
});

test('ticket numbers restart at one on the next day', function () {
    [, $department] = createApprovedDepartment();
    $service = $department->services()->create([
        'name' => 'General Inquiries',
        'code' => 'GI',
    ]);

    Carbon::setTestNow('2026-11-12 10:00:00');
    $this->post(route('tickets.join', $service));
    Carbon::setTestNow('2026-11-13 10:00:00');
    $this->post(route('tickets.join', $service));
    Carbon::setTestNow();

    expect(QueueTicket::orderBy('id')->pluck('ticket_number')->all())->toBe(['GI-001', 'GI-001']);
});

test('department staff process tickets in FIFO order and cannot process another department', function () {
    [$owner, $department] = createApprovedDepartment();
    $service = $department->services()->create([
        'name' => 'General Inquiries',
        'code' => 'GI',
    ]);

    $this->post(route('tickets.join', $service));
    $this->post(route('tickets.join', $service));
    $first = QueueTicket::orderBy('id')->firstOrFail();
    $second = QueueTicket::orderByDesc('id')->firstOrFail();

    $this->actingAs($owner)
        ->post(route('staff.services.call-next', $service))
        ->assertRedirect();

    expect($first->fresh()->status)->toBe('serving')
        ->and($second->fresh()->status)->toBe('waiting');

    $this->post(route('staff.services.call-next', $service))
        ->assertSessionHasErrors('queue');

    $this->post(route('staff.tickets.complete', $first))
        ->assertRedirect();
    $this->post(route('staff.services.call-next', $service))
        ->assertRedirect();

    expect($first->fresh()->status)->toBe('completed')
        ->and($second->fresh()->status)->toBe('serving');

    [, $otherDepartment] = createApprovedDepartment('Library');
    $otherService = $otherDepartment->services()->create([
        'name' => 'Borrowing',
        'code' => 'B',
    ]);
    $otherTicket = $otherService->tickets()->create([
        'ticket_number' => 'B-001',
        'sequence' => 1,
        'tracking_token' => str_repeat('a', 64),
        'queue_date' => today(),
    ]);

    $this->post(route('staff.tickets.complete', $otherTicket))->assertNotFound();
});
