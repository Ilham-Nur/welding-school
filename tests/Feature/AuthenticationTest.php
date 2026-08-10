<?php

namespace Tests\Feature;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_can_register_with_email_and_password(): void
    {
        Notification::fake();

        $response = $this->postJson(route('register.store'), [
            'username' => 'budi.welder',
            'email' => 'BUDI@example.com',
            'password' => 'Welding123',
            'password_confirmation' => 'Welding123',
            'agreement' => true,
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('requires_verification', true)
            ->assertJsonPath('email', 'budi@example.com')
            ->assertSessionHas('pending_verification_email', 'budi@example.com');

        $this->assertGuest();
        $this->assertDatabaseHas('users', [
            'username' => 'budi.welder',
            'email' => 'budi@example.com',
            'status' => 'active',
            'email_verified_at' => null,
        ]);
        $this->assertTrue(
            User::query()->where('email', 'budi@example.com')->firstOrFail()->hasRole('participant'),
        );
        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => User::query()->where('email', 'budi@example.com')->value('id'),
            'attempts' => 0,
        ]);
        $this->assertTrue(Hash::check(
            'Welding123',
            User::query()->where('email', 'budi@example.com')->value('password'),
        ));

        Notification::assertSentTo(
            User::query()->where('email', 'budi@example.com')->firstOrFail(),
            EmailVerificationCodeNotification::class,
        );
    }

    public function test_participant_can_verify_email_with_the_sent_code(): void
    {
        Notification::fake();
        $code = null;

        $this->postJson(route('register.store'), [
            'username' => 'budi.welder',
            'email' => 'budi@example.com',
            'password' => 'Welding123',
            'password_confirmation' => 'Welding123',
            'agreement' => true,
        ])->assertAccepted();

        $user = User::query()->where('email', 'budi@example.com')->firstOrFail();
        Notification::assertSentTo(
            $user,
            EmailVerificationCodeNotification::class,
            function (EmailVerificationCodeNotification $notification) use (&$code): bool {
                $code = $notification->code;

                return true;
            },
        );

        $this->postJson(route('verification.code.verify'), [
            'code' => $code,
        ])->assertOk()
            ->assertJsonPath('message', 'Email berhasil diverifikasi.')
            ->assertJsonPath('user.email', 'budi@example.com');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_verification_codes', [
            'user_id' => $user->id,
        ]);
    }

    public function test_participant_can_register_and_open_portal_when_verification_is_disabled(): void
    {
        config()->set('auth.email_verification_required', false);
        Notification::fake();

        $response = $this->postJson(route('register.store'), [
            'username' => 'tanpa.otp',
            'email' => 'tanpa-otp@example.com',
            'password' => 'Welding123',
            'password_confirmation' => 'Welding123',
            'agreement' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('requires_verification', false)
            ->assertJsonPath('user.email', 'tanpa-otp@example.com');

        $user = User::query()->where('email', 'tanpa-otp@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseMissing('email_verification_codes', ['user_id' => $user->id]);
        Notification::assertNothingSent();

        $this->getJson(route('profile.show'))->assertOk();
    }

    public function test_unverified_participant_can_login_when_verification_is_disabled(): void
    {
        config()->set('auth.email_verification_required', false);
        Notification::fake();
        $user = User::factory()->unverified()->create([
            'email' => 'belum@example.com',
            'password' => 'Welding123',
            'status' => 'active',
        ]);

        $this->postJson(route('login.store'), [
            'email' => 'belum@example.com',
            'password' => 'Welding123',
        ])->assertOk()
            ->assertJsonPath('user.email', 'belum@example.com');

        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
        $this->getJson(route('profile.show'))->assertOk();
    }

    public function test_registration_requires_password_confirmation_and_agreement(): void
    {
        $this->postJson(route('register.store'), [
            'username' => 'budi.welder',
            'email' => 'budi@example.com',
            'password' => 'Welding123',
            'password_confirmation' => 'berbeda',
            'agreement' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password', 'agreement']);

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
    }

    public function test_active_participant_can_login_and_logout(): void
    {
        $user = User::factory()->create([
            'email' => 'peserta@example.com',
            'password' => 'Welding123',
            'status' => 'active',
        ]);
        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => 'google-login-avatar',
            'provider_email' => 'peserta@example.com',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $this->postJson(route('login.store'), [
            'email' => 'PESERTA@example.com',
            'password' => 'Welding123',
            'remember' => true,
        ])->assertOk()
            ->assertJsonPath('user.email', 'peserta@example.com')
            ->assertJsonPath('user.avatar', 'https://example.com/avatar.jpg');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);

        $this->postJson(route('logout'))
            ->assertOk()
            ->assertJsonPath('message', 'Anda telah keluar dari akun.');

        $this->assertGuest();
    }

    public function test_active_participant_can_login_with_username(): void
    {
        $user = User::factory()->create([
            'username' => 'peserta.welder',
            'email' => 'peserta@example.com',
            'password' => 'Welding123',
            'status' => 'active',
        ]);

        $this->postJson(route('login.store'), [
            'login' => 'PESERTA.WELDER',
            'password' => 'Welding123',
        ])->assertOk()
            ->assertJsonPath('user.username', 'peserta.welder')
            ->assertJsonPath('user.email', 'peserta@example.com');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_uses_a_generic_error_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'peserta@example.com',
            'password' => 'Welding123',
        ]);

        $this->postJson(route('login.store'), [
            'email' => 'peserta@example.com',
            'password' => 'salah-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email'])
            ->assertJsonPath('errors.email.0', 'Email, username, atau password tidak sesuai.');

        $this->assertGuest();
    }

    public function test_unverified_participant_is_sent_to_code_verification_on_login(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create([
            'email' => 'belum@example.com',
            'password' => 'Welding123',
        ]);

        $this->postJson(route('login.store'), [
            'email' => 'belum@example.com',
            'password' => 'Welding123',
        ])->assertStatus(409)
            ->assertJsonPath('requires_verification', true)
            ->assertJsonPath('email', 'belum@example.com')
            ->assertSessionHas('pending_verification_user_id', $user->id);

        $this->assertGuest();
        Notification::assertSentTo($user, EmailVerificationCodeNotification::class);
    }

    public function test_verification_code_expires_after_ten_minutes(): void
    {
        $user = User::factory()->unverified()->create();
        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->subSecond(),
            'sent_at' => now()->subMinutes(11),
        ]);

        $this->withSession([
            'pending_verification_user_id' => $user->id,
            'pending_verification_email' => $user->email,
        ])->postJson(route('verification.code.verify'), [
            'code' => '123456',
        ])->assertUnprocessable()
            ->assertJsonPath(
                'errors.code.0',
                'Kode verifikasi sudah kedaluwarsa. Silakan kirim kode baru.',
            );

        $this->assertGuest();
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseMissing('email_verification_codes', [
            'user_id' => $user->id,
        ]);
    }

    public function test_verification_code_is_locked_after_five_wrong_attempts(): void
    {
        $user = User::factory()->unverified()->create();
        EmailVerificationCode::query()->create([
            'user_id' => $user->id,
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'sent_at' => now(),
        ]);
        $session = [
            'pending_verification_user_id' => $user->id,
            'pending_verification_email' => $user->email,
        ];

        foreach (range(1, 5) as $attempt) {
            $this->withSession($session)
                ->postJson(route('verification.code.verify'), ['code' => '999999'])
                ->assertUnprocessable();
        }

        $this->assertDatabaseHas('email_verification_codes', [
            'user_id' => $user->id,
            'attempts' => 5,
        ]);

        $this->withSession($session)
            ->postJson(route('verification.code.verify'), ['code' => '123456'])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.code.0',
                'Batas percobaan telah tercapai. Silakan kirim kode baru.',
            );
        $this->assertGuest();
    }

    public function test_verification_code_can_only_be_resent_after_cooldown(): void
    {
        Notification::fake();

        $this->postJson(route('register.store'), [
            'username' => 'budi.welder',
            'email' => 'budi@example.com',
            'password' => 'Welding123',
            'password_confirmation' => 'Welding123',
            'agreement' => true,
        ])->assertAccepted();

        $user = User::query()->where('email', 'budi@example.com')->firstOrFail();

        $this->postJson(route('verification.code.resend'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->travel(61)->seconds();

        $this->postJson(route('verification.code.resend'))
            ->assertOk()
            ->assertJsonPath('message', 'Kode verifikasi baru telah dikirim.');

        Notification::assertSentToTimes(
            $user,
            EmailVerificationCodeNotification::class,
            2,
        );
    }

    public function test_participant_can_request_a_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'reset@example.com']);
        $token = null;

        $this->postJson(route('password.email'), ['email' => 'RESET@example.com'])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Jika email terdaftar, tautan reset password telah dikirim. Periksa inbox atau folder spam.',
            );

        Notification::assertSentTo(
            $user,
            ResetPasswordNotification::class,
            function (ResetPasswordNotification $notification) use (&$token): bool {
                $token = $notification->token;

                return $token !== '';
            },
        );

        $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
        $resetPage = $this->get(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ]));

        $resetPage
            ->assertOk()
            ->assertSee($token)
            ->assertSee($user->email)
            ->assertSee('noindex, nofollow, noarchive')
            ->assertSee('<meta name="referrer" content="no-referrer">', false);
        $this->assertStringContainsString(
            'no-store',
            (string) $resetPage->headers->get('Cache-Control'),
        );
    }

    public function test_password_reset_request_does_not_reveal_unknown_email(): void
    {
        Notification::fake();

        $this->postJson(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Jika email terdaftar, tautan reset password telah dikirim. Periksa inbox atau folder spam.',
            );

        Notification::assertNothingSent();
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'unknown@example.com',
        ]);
    }

    public function test_participant_can_reset_password_and_old_sessions_are_revoked(): void
    {
        $user = User::factory()->create([
            'email' => 'password-baru@example.com',
            'password' => 'PasswordLama123',
        ]);
        $token = Password::createToken($user);

        DB::table('sessions')->insert([
            'id' => 'old-participant-session',
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'old-session',
            'last_activity' => now()->timestamp,
        ]);

        $this->postJson(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'PasswordBaru456',
            'password_confirmation' => 'PasswordBaru456',
        ])
            ->assertOk()
            ->assertJsonPath(
                'message',
                'Password berhasil diperbarui. Silakan masuk menggunakan password baru.',
            );

        $this->assertTrue(Hash::check('PasswordBaru456', $user->fresh()->password));
        $this->assertFalse(Hash::check('PasswordLama123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);

        $this->postJson(route('login.store'), [
            'login' => $user->email,
            'password' => 'PasswordBaru456',
        ])->assertOk();
    }

    public function test_invalid_password_reset_token_cannot_change_password(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid-token@example.com',
            'password' => 'PasswordLama123',
        ]);

        $this->postJson(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'PasswordBaru456',
            'password_confirmation' => 'PasswordBaru456',
        ])
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.email.0',
                'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            );

        $this->assertTrue(Hash::check('PasswordLama123', $user->fresh()->password));
    }

    public function test_google_login_asks_the_user_to_select_an_account(): void
    {
        $this->configureGoogle();

        $response = $this->get(route('auth.google.redirect'));

        $response->assertRedirect();

        parse_str(
            (string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY),
            $query,
        );

        $this->assertSame('select_account', $query['prompt'] ?? null);
    }

    public function test_authenticated_user_does_not_restart_google_login(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->get(route('auth.google.redirect'))
            ->assertRedirect(route('home'));
    }

    public function test_google_callback_creates_a_passwordless_participant(): void
    {
        $this->configureGoogle();
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Siti Rahma',
            'email' => 'SITI@example.com',
            'avatar' => 'https://example.com/siti.jpg',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home').'#member-programs')
            ->assertSessionHas('auth_status', 'Login dengan Google berhasil.');

        $user = User::query()->where('email', 'siti@example.com')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-123',
            'provider_email' => 'siti@example.com',
        ]);
    }

    public function test_google_login_links_an_existing_email_account(): void
    {
        $this->configureGoogle();
        $user = User::factory()->create([
            'email' => 'siti@example.com',
            'password' => 'Welding123',
            'email_verified_at' => null,
        ]);
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-existing',
            'name' => 'Siti Rahma',
            'email' => 'siti@example.com',
            'email_verified' => true,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home').'#member-programs');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('users', 1);
        $this->assertTrue(Hash::check('Welding123', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider_user_id' => 'google-existing',
        ]);
    }

    public function test_google_login_rejects_an_unverified_email(): void
    {
        $this->configureGoogle();
        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-unverified',
            'email' => 'belum@example.com',
            'email_verified' => false,
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('home').'#account')
            ->assertSessionHas('auth_error', 'Alamat email Google belum terverifikasi.');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'belum@example.com']);
    }

    private function configureGoogle(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
        config()->set('services.google.redirect', '/auth/google/callback');
    }
}
