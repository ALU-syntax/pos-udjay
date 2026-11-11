<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response
        ->assertOk()
        ->assertSee('Profil Saya')
        ->assertSee($user->email);
});

test('guest cannot open the profile page', function () {
    $this->get('/profile')->assertRedirect('/login');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'username' => 'test-user',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test-user', $user->username);
    $this->assertSame('test@example.com', $user->email);
});

test('profile update cannot change role, status, outlet, or pin', function () {
    $user = User::factory()->create()->refresh();
    $original = $user->only(['role', 'status', 'outlet_id', 'pin']);

    $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'username' => $user->username,
            'email' => $user->email,
            'role' => 99,
            'status' => '0',
            'outlet_id' => [1, 2],
            'pin' => '123456',
        ])
        ->assertSessionHasNoErrors();

    $this->assertSame($original, $user->refresh()->only(['role', 'status', 'outlet_id', 'pin']));
});

test('email already used by another active user is rejected', function () {
    $other = User::factory()->create();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $other->email,
        ]);

    $response
        ->assertSessionHasErrors('email')
        ->assertRedirect('/profile');

    $this->assertNotSame($other->email, $user->refresh()->email);
});

test('email of a deleted user can be reused', function () {
    $deleted = User::factory()->create(['deleted' => 1, 'status' => '0']);
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => $user->name,
            'username' => $user->username,
            'email' => $deleted->email,
        ])
        ->assertSessionHasNoErrors();

    $this->assertSame($deleted->email, $user->refresh()->email);
});

test('username already used by another user is rejected', function () {
    $other = User::factory()->create();
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->patch('/profile', [
            'name' => $user->name,
            'username' => $other->username,
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasErrors('username')
        ->assertRedirect('/profile');
});

test('account can no longer be deleted from the profile page', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertStatus(405);

    $this->assertNotNull($user->fresh());
});

test('pin can be set from the profile page', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/profile/pin', [
            'current_password' => 'password',
            'pin' => '123456',
            'pin_confirmation' => '123456',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('123456', $user->refresh()->pin));
});

test('pin can be replaced and the old pin stops working', function () {
    $user = User::factory()->create(['pin' => Hash::make('111111')]);

    $this
        ->actingAs($user)
        ->put('/profile/pin', [
            'current_password' => 'password',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ])
        ->assertSessionHasNoErrors();

    $user->refresh();

    $this->assertTrue(Hash::check('654321', $user->pin));
    $this->assertFalse(Hash::check('111111', $user->pin));
});

test('correct password must be provided to change pin', function () {
    $user = User::factory()->create(['pin' => Hash::make('111111')]);

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/profile/pin', [
            'current_password' => 'wrong-password',
            'pin' => '654321',
            'pin_confirmation' => '654321',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePin', 'current_password')
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('111111', $user->refresh()->pin));
});

test('pin must be six digits and confirmed', function (array $payload) {
    $user = User::factory()->create(['pin' => Hash::make('111111')]);

    $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/profile/pin', ['current_password' => 'password'] + $payload)
        ->assertSessionHasErrorsIn('updatePin', 'pin')
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('111111', $user->refresh()->pin));
})->with([
    'terlalu pendek' => [['pin' => '12345', 'pin_confirmation' => '12345']],
    'terlalu panjang' => [['pin' => '1234567', 'pin_confirmation' => '1234567']],
    'bukan angka' => [['pin' => '12a456', 'pin_confirmation' => '12a456']],
    'konfirmasi beda' => [['pin' => '123456', 'pin_confirmation' => '654321']],
    'kosong' => [['pin' => '', 'pin_confirmation' => '']],
]);

test('guest cannot change pin', function () {
    $this->put('/profile/pin', [
        'current_password' => 'password',
        'pin' => '123456',
        'pin_confirmation' => '123456',
    ])->assertRedirect('/login');
});
