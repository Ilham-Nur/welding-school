<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Throwable;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        if (!$this->isConfigured()) {
            return $this->accountRedirect()
                ->with('auth_error', 'Login Google belum dikonfigurasi. Tambahkan Client ID dan Client Secret Google.');
        }

        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        if (!$this->isConfigured()) {
            return $this->accountRedirect()
                ->with('auth_error', 'Login Google belum dikonfigurasi.');
        }

        try {
            $googleUser = Socialite::driver('google')->user();
            $email = Str::lower(trim((string) $googleUser->getEmail()));

            if ($email === '') {
                throw new DomainException('Google tidak memberikan alamat email.');
            }

            if (!filter_var($googleUser->user['email_verified'] ?? false, FILTER_VALIDATE_BOOL)) {
                throw new DomainException('Alamat email Google belum terverifikasi.');
            }

            $user = DB::transaction(function () use ($googleUser, $email): User {
                $socialAccount = SocialAccount::query()
                    ->where('provider', 'google')
                    ->where('provider_user_id', (string) $googleUser->getId())
                    ->first();

                $user = $socialAccount?->user;

                if (!$user) {
                    $user = User::query()->firstOrCreate(
                        ['email' => $email],
                        [
                            'name' => $googleUser->getName() ?: Str::before($email, '@'),
                            'password' => null,
                            'email_verified_at' => now(),
                            'status' => 'active',
                        ],
                    );
                }

                if ($user->status !== 'active') {
                    throw new DomainException('Akun tidak aktif.');
                }

                $user->socialAccounts()->updateOrCreate(
                    ['provider' => 'google'],
                    [
                        'provider_user_id' => (string) $googleUser->getId(),
                        'provider_email' => $email,
                        'avatar_url' => $googleUser->getAvatar(),
                        'last_used_at' => now(),
                    ],
                );

                $user->forceFill([
                    'email_verified_at' => $user->email_verified_at ?: now(),
                    'last_login_at' => now(),
                ])->save();

                if ($user->roles()->doesntExist()) {
                    Role::findOrCreate('participant', 'web');
                    $user->assignRole('participant');
                }

                return $user;
            });

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect($user->isAdmin() ? route('admin.dashboard') : route('home') . '#member-programs')
                ->with('auth_status', 'Login dengan Google berhasil.');
        } catch (DomainException $exception) {
            return $this->accountRedirect()->with('auth_error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return $this->accountRedirect()
                ->with('auth_error', 'Login Google gagal atau dibatalkan. Silakan coba kembali.');
        }
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id'))
            && filled(config('services.google.client_secret'));
    }

    private function accountRedirect(): RedirectResponse
    {
        return redirect(route('home') . '#account');
    }
}
