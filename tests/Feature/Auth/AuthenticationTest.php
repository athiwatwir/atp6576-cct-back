<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    foreach (['admin', 'staff', 'student'] as $name) {
        Role::query()->create([
            'name' => $name,
            'display_name' => ucfirst($name),
        ]);
    }
});

function createUserWithRole(string $role, array $overrides = []): User
{
    $user = User::query()->create(array_merge([
        'name' => 'Test User',
        'email' => "{$role}@example.com",
        'password' => Hash::make('password'),
        'status' => 'active',
    ], $overrides));

    $user->roles()->attach(Role::query()->where('name', $role)->firstOrFail());

    return $user;
}

it('shows the login page to guests', function () {
    $this->get(route('login'))->assertOk();
});

it('allows admin to login and reach the dashboard', function () {
    createUserWithRole('admin', ['email' => 'admin@example.com']);

    $this->post(route('login'), [
        'email' => 'admin@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

it('allows staff to login', function () {
    createUserWithRole('staff', ['email' => 'staff@example.com']);

    $this->post(route('login'), [
        'email' => 'staff@example.com',
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

it('rejects student login', function () {
    createUserWithRole('student', ['email' => 'student@example.com']);

    $this->from(route('login'))
        ->post(route('login'), [
            'email' => 'student@example.com',
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('rejects inactive users', function () {
    createUserWithRole('admin', [
        'email' => 'inactive@example.com',
        'status' => 'inactive',
    ]);

    $this->from(route('login'))
        ->post(route('login'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('redirects guests away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

it('logs out authenticated users', function () {
    $user = createUserWithRole('admin');

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
