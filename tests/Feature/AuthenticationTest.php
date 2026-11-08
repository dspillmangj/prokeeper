<?php

use App\Mail\TwoFactorCodeMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;

test('unverified email user cannot create games or rosters until verified', function () {
    $org = Organization::create(['name' => 'Unverified Org', 'slug' => 'unv-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'New User',
        'email' => 'unverified@example.com',
        'password' => bcrypt('password'),
        'role' => 'coach',
        'email_verified_at' => null, // Unverified
    ]);

    // Roster create blocked
    $this->actingAs($user)->get(route('teams.create'))
        ->assertRedirect(route('verification.notice'));

    // Game create blocked
    $this->actingAs($user)->get(route('games.create'))
        ->assertRedirect(route('verification.notice'));

    // Verify email
    $user->update(['email_verified_at' => now()]);

    // Now accessible
    $this->actingAs($user)->get(route('teams.create'))
        ->assertStatus(200);
});

test('user with email 2fa enabled is prompted for 6-digit code upon password login', function () {
    Mail::fake();

    $org = Organization::create(['name' => '2FA Org', 'slug' => '2fa-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => '2FA User',
        'email' => 'security@example.com',
        'password' => bcrypt('secret-pass'),
        'role' => 'admin',
        'two_factor_email_enabled' => true,
        'email_verified_at' => now(),
    ]);

    $loginResponse = $this->post(route('login.post'), [
        'email' => 'security@example.com',
        'password' => 'secret-pass',
    ]);

    $loginResponse->assertRedirect(route('auth.2fa.show'));
    $this->assertGuest(); // Not authenticated yet

    Mail::assertSent(TwoFactorCodeMail::class, function ($mail) {
        return $mail->hasTo('security@example.com');
    });

    $user->refresh();
    expect($user->two_factor_code)->not->toBeNull();
    expect($user->two_factor_expires_at)->not->toBeNull();

    // Verify invalid code fails
    $invalidResponse = $this->post(route('auth.2fa.verify'), ['code' => '999999']);
    $invalidResponse->assertSessionHasErrors('code');
    $this->assertGuest();

    // Emulate correct code
    $validCode = '123456';
    $user->update(['two_factor_code' => Hash::make($validCode)]);

    $validResponse = $this->post(route('auth.2fa.verify'), ['code' => $validCode]);
    $validResponse->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});

test('password reset link can be requested and used to reset password', function () {
    $org = Organization::create(['name' => 'Reset Org', 'slug' => 'reset-org']);
    $user = User::create([
        'organization_id' => $org->id,
        'name' => 'Reset User',
        'email' => 'reset@example.com',
        'password' => bcrypt('old-password'),
        'role' => 'coach',
        'email_verified_at' => now(),
    ]);

    $token = Password::createToken($user);

    $resetResponse = $this->post(route('password.update'), [
        'token' => $token,
        'email' => 'reset@example.com',
        'password' => 'brand-new-password',
        'password_confirmation' => 'brand-new-password',
    ]);

    $resetResponse->assertRedirect(route('login'));

    $user->refresh();
    expect(Hash::check('brand-new-password', $user->password))->toBeTrue();
});

test('user can register without providing organization name', function () {
    $response = $this->post(route('register.post'), [
        'name' => 'Individual User',
        'email' => 'individual@example.com',
        'password' => 'secret1234',
        'password_confirmation' => 'secret1234',
    ]);

    $response->assertRedirect(route('dashboard'));

    $user = User::where('email', 'individual@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('Individual User');
    expect($user->organization)->not->toBeNull();
    expect($user->organization->name)->toBe("Individual User's Organization");
    expect($user->isEnterprise())->toBeFalse();
});

