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
        ->post(route('services.store'), [
            'name' => 'Transcript of Records',
            'location' => 'Window 1',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('services', [
        'department_id' => $department->id,
        'name' => 'Transcript of Records',
        'code' => 'TOR',
        'location' => 'Window 1',
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
            ->where('ticket.departmentId', $department->id)
            ->where('ahead', 0));
});

test('a department display shows each active service and advances by groups of five', function () {
    [, $department] = createApprovedDepartment();
    $service = $department->services()->create([
        'name' => 'General Inquiries',
        'code' => 'GI',
        'location' => 'Window 1',
    ]);
    $department->services()->create([
        'name' => 'Transcript Requests',
        'code' => 'TR',
    ]);
    $department->services()->create([
        'name' => 'Inactive Service',
        'code' => 'IS',
        'is_active' => false,
    ]);

    foreach (range(1, 8) as $sequence) {
        $service->tickets()->create([
            'ticket_number' => sprintf('GI-%03d', $sequence),
            'sequence' => $sequence,
            'tracking_token' => str_pad((string) $sequence, 64, 'a', STR_PAD_LEFT),
            'status' => $sequence <= 6 ? 'completed' : 'waiting',
            'queue_date' => today(),
        ]);
    }

    $this->get(route('departments.display', $department))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('campusqueue/display')
            ->where('department.name', 'Registrar')
            ->has('services', 2)
            ->where('services.0.name', 'General Inquiries')
            ->where('services.0.location', 'Window 1')
            ->where('services.0.batchNumber', 2)
            ->has('services.0.tickets', 2)
            ->where('services.0.tickets.0.ticketNumber', 'GI-007')
            ->where('services.0.tickets.1.ticketNumber', 'GI-008')
            ->where('services.0.tickets.0.status', 'waiting')
            ->where('services.1.name', 'Transcript Requests')
            ->has('services.1.tickets', 0));
});

test('a department display is unavailable until the department is approved', function () {
    [, $department] = createApprovedDepartment();
    $department->update(['status' => 'pending']);

    $this->get(route('departments.display', $department))->assertNotFound();
});

test('staff can skip the serving ticket and automatically call the next ticket', function () {
    [$owner, $department] = createApprovedDepartment();
    $service = $department->services()->create([
        'name' => 'Transcript Records',
        'code' => 'TR',
    ]);

    $this->post(route('tickets.join', $service));
    $this->post(route('tickets.join', $service));
    $tickets = QueueTicket::orderBy('sequence')->get();
    $first = $tickets[0];
    $second = $tickets[1];

    expect($tickets->pluck('ticket_number')->all())->toBe(['TR-001', 'TR-002']);

    $this->actingAs($owner)
        ->post(route('staff.services.call-next', $service))
        ->assertRedirect();
    $this->post(route('staff.tickets.skip', $first))
        ->assertRedirect()
        ->assertSessionHas('success', 'TR-001 skipped. TR-002 called next.');

    expect($first->fresh()->status)->toBe('skipped')
        ->and($second->fresh()->status)->toBe('serving');

    $this->get(route('tickets.show', $first->tracking_token))
        ->assertInertia(fn ($page) => $page->where('ticket.status', 'skipped'));
});

test('staff can pause a service while preserving its queue and resume early', function () {
    [$owner, $department] = createApprovedDepartment();
    $service = $department->services()->create([
        'name' => 'Transcript Records',
        'code' => 'TR',
    ]);

    $this->post(route('tickets.join', $service));
    $this->post(route('tickets.join', $service));
    $tickets = QueueTicket::orderBy('sequence')->get();
    $first = $tickets[0];
    $second = $tickets[1];

    $this->actingAs($owner)
        ->post(route('staff.services.call-next', $service))
        ->assertRedirect();
    $this->post(route('staff.services.pause', $service))->assertRedirect();

    $pausedService = $service->fresh();
    expect($pausedService->isCurrentlyPaused())->toBeTrue()
        ->and($pausedService->paused_until->between(now()->addMinutes(59), now()->addMinutes(61)))->toBeTrue();

    $this->get(route('departments.display', $department))
        ->assertInertia(fn ($page) => $page->where('services.0.isPaused', true));
    $this->get(route('home'))
        ->assertInertia(fn ($page) => $page->where('departments.0.services.0.isPaused', true));

    $this->post(route('tickets.join', $service))->assertSessionHasErrors('service');
    $this->post(route('staff.services.call-next', $service))->assertSessionHasErrors('queue');

    $this->post(route('staff.tickets.skip', $first))
        ->assertRedirect()
        ->assertSessionHas('success', 'TR-001 skipped. The service is paused; the remaining queue was kept.');

    expect($first->fresh()->status)->toBe('skipped')
        ->and($second->fresh()->status)->toBe('waiting');

    $this->post(route('staff.services.resume', $service))->assertRedirect();

    expect($service->fresh()->isCurrentlyPaused())->toBeFalse();

    $this->post(route('staff.services.call-next', $service))->assertRedirect();
    expect($second->fresh()->status)->toBe('serving');
});

test('a service automatically resumes when its one-hour pause expires', function () {
    [$owner, $department] = createApprovedDepartment();
    $service = $department->services()->create([
        'name' => 'Transcript Records',
        'code' => 'TR',
    ]);

    Carbon::setTestNow('2026-11-15 10:00:00');

    $this->actingAs($owner)
        ->post(route('staff.services.pause', $service))
        ->assertRedirect();

    expect($service->fresh()->paused_until->toDateTimeString())->toBe('2026-11-15 11:00:00');

    Carbon::setTestNow('2026-11-15 11:00:01');

    $this->get(route('departments.display', $department))
        ->assertInertia(fn ($page) => $page->where('services.0.isPaused', false));

    Carbon::setTestNow();
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
