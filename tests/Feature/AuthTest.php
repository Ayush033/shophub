<?php

use App\Models\User;

it('registers a customer and returns a token', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.role', 'customer')
        ->assertJsonStructure(['user' => ['id', 'email'], 'token']);
});

it('ignores a role sent by the client during registration', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'role' => 'admin',
    ])->assertCreated();

    expect(User::where('email', 'sneaky@example.com')->first()->role)->toBe('customer');
});

it('rejects weak passwords', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Weak',
        'email' => 'weak@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
});

it('logs in with the right password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'password'])
        ->assertOk()
        ->assertJsonStructure(['token']);
});

it('rejects the wrong password', function () {
    $user = User::factory()->create();

    $this->postJson('/api/v1/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertUnprocessable();
});

it('does not return the password hash', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonMissingPath('data.password');
});

it('blocks protected routes without a token', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
    $this->getJson('/api/v1/orders')->assertUnauthorized();
    $this->postJson('/api/v1/orders')->assertUnauthorized();
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api-token')->plainTextToken;

    $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

    expect($user->tokens()->count())->toBe(0);
});
