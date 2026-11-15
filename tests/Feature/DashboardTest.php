<?php

use App\Models\Department;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create(['role' => 'owner']);
    Department::create([
        'name' => 'Registrar',
        'email' => 'registrar@example.com',
        'status' => 'approved',
        'owner_id' => $user->id,
    ]);

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('users without a department are redirected to the department creation page', function () {
    $user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('departments.create'));
});


