<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

// Pesan yang sengaja sama untuk email terdaftar maupun tidak
const RESET_LINK_STATUS_PREFIX = 'Jika email tersebut terdaftar';

test('login screen links to the forgot password page', function () {
    $this->get('/login')->assertOk()->assertSee(route('password.request'));
});

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200)->assertSee('Lupa password?');
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

    $response->assertSessionHasNoErrors()->assertRedirect('/forgot-password');
    expect(session('status'))->toStartWith(RESET_LINK_STATUS_PREFIX);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('unknown email gets the same response and no email is sent', function () {
    Notification::fake();

    $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'tidak-ada@example.com']);

    $response->assertSessionHasNoErrors()->assertRedirect('/forgot-password');
    expect(session('status'))->toStartWith(RESET_LINK_STATUS_PREFIX);

    Notification::assertNothingSent();
});

test('deleted user does not receive a reset link', function () {
    Notification::fake();

    $user = User::factory()->create(['deleted' => 1]);

    $response = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);

    $response->assertSessionHasNoErrors();
    expect(session('status'))->toStartWith(RESET_LINK_STATUS_PREFIX);

    Notification::assertNothingSent();
});

test('reset link request requires a valid email format', function () {
    Notification::fake();

    $this->from('/forgot-password')
        ->post('/forgot-password', ['email' => 'bukan-email'])
        ->assertSessionHasErrors('email')
        ->assertRedirect('/forgot-password');

    Notification::assertNothingSent();
});

test('reset link requests are rate limited per email', function () {
    Notification::fake();

    foreach (range(1, 5) as $attempt) {
        $this->post('/forgot-password', ['email' => 'siapa@example.com'])->assertSessionHasNoErrors();
    }

    $this->post('/forgot-password', ['email' => 'siapa@example.com'])->assertSessionHasErrors('email');

    // email lain tidak ikut terblokir
    $this->post('/forgot-password', ['email' => 'lain@example.com'])->assertSessionHasNoErrors();
});

test('reset email is in Indonesian and links to the reset page', function () {
    $user = User::factory()->create(['name' => 'Rina Kartika']);

    $mail = (new ResetPassword('token-contoh'))->toMail($user);
    $html = view($mail->view, $mail->viewData)->render();

    expect($mail->subject)->toStartWith('Atur Ulang Password');
    expect($html)
        ->toContain('Halo Rina Kartika')
        ->toContain('Atur Ulang Password')
        ->toContain('/reset-password/token-contoh?email='.urlencode($user->email));
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email));

        $response->assertStatus(200)->assertSee('Atur ulang password')->assertSee($user->email);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login'))
            ->assertSessionHas('status');

        expect(Hash::check('password-baru', $user->refresh()->password))->toBeTrue();

        // link hanya bisa dipakai satu kali
        $this->from('/reset-password/'.$notification->token)
            ->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'password-lain',
                'password_confirmation' => 'password-lain',
            ])
            ->assertSessionHasErrors('email');

        expect(Hash::check('password-baru', $user->refresh()->password))->toBeTrue();

        return true;
    });
});

test('password is not reset with an invalid token', function () {
    $user = User::factory()->create();

    $this->from('/reset-password/token-salah')
        ->post('/reset-password', [
            'token' => 'token-salah',
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
        ->assertSessionHasErrors('email')
        ->assertRedirect('/reset-password/token-salah');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('new password must be at least 8 characters and confirmed on reset', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        foreach ([['pendek', 'pendek'], ['password-baru', 'password-beda']] as [$password, $confirmation]) {
            $this->post('/reset-password', [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => $password,
                'password_confirmation' => $confirmation,
            ])->assertSessionHasErrors('password');
        }

        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();

        return true;
    });
});

test('deleted user cannot reset password even with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $user->update(['deleted' => 1]);

        $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])->assertSessionHasErrors('email');

        expect(Hash::check('password', $user->refresh()->password))->toBeTrue();

        return true;
    });
});
