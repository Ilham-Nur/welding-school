<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verification,
    ) {}

    public function store(Request $request): JsonResponse
    {
        [$user, $errorField] = $this->authenticate($request);

        if ($user->isInternal()) {
            $this->logoutAttempt($request);

            throw ValidationException::withMessages([
                $errorField => 'Gunakan Portal Internal untuk masuk dengan akun ini.',
            ]);
        }

        if (config('auth.email_verification_required', true) && ! $user->hasVerifiedEmail()) {
            Auth::guard('web')->logout();
            $request->session()->regenerate();
            $request->session()->put([
                'pending_verification_user_id' => $user->id,
                'pending_verification_email' => $user->email,
            ]);

            $code = $this->verification->send($user, respectCooldown: false);

            return response()->json([
                'message' => 'Email belum terverifikasi. Kode baru telah dikirim.',
                'requires_verification' => true,
                'email' => $user->email,
                'debug_code' => app()->isLocal() ? $code : null,
            ], 409);
        }

        $request->session()->regenerate();
        $request->session()->forget([
            'pending_verification_user_id',
            'pending_verification_email',
        ]);
        $user->forceFill(['last_login_at' => now()])->save();

        $intendedUrl = $request->session()->pull('url.intended');
        $redirectTo = is_string($intendedUrl) && Str::startsWith($intendedUrl, url('/'))
            ? $intendedUrl
            : ($user->isAdmin() ? route('admin.dashboard') : null);

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->primaryRoleName(),
                'avatar' => $user->profileAvatarUrl(),
                'is_admin' => $user->isAdmin(),
                'profile' => $user->participantProfileData(),
            ],
            'redirect_to' => $redirectTo,
        ]);
    }

    public function storeInternal(Request $request): RedirectResponse
    {
        [$user, $errorField] = $this->authenticate($request);

        if (! $user->isInternal()) {
            $this->logoutAttempt($request);

            throw ValidationException::withMessages([
                $errorField => 'Akun ini tidak memiliki akses ke Portal Internal.',
            ]);
        }

        if (config('auth.email_verification_required', true) && ! $user->hasVerifiedEmail()) {
            $this->logoutAttempt($request);

            throw ValidationException::withMessages([
                $errorField => 'Email akun belum terverifikasi. Hubungi super-admin.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget([
            'pending_verification_user_id',
            'pending_verification_email',
        ]);
        $user->forceFill(['last_login_at' => now()])->save();

        $intendedUrl = $request->session()->pull('url.intended');
        $redirectTo = is_string($intendedUrl) && Str::startsWith($intendedUrl, [
            url('/admin'),
            url('/assets/inspect'),
            url('/assets/resolve'),
        ]) ? $intendedUrl : route('admin.dashboard');

        return redirect($redirectTo)
            ->with('success', 'Selamat datang di Portal Internal.');
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        $wasInternal = $request->user()?->isInternal() ?? false;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Anda telah keluar dari akun.',
            ]);
        }

        return redirect($wasInternal ? route('admin.login') : route('home').'#account')
            ->with('auth_status', 'Anda telah keluar dari akun.');
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function authenticate(Request $request): array
    {
        $credentials = $request->validate([
            'login' => ['nullable', 'required_without:email', 'string', 'max:255'],
            'email' => ['nullable', 'required_without:login', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ], [
            'login.required_without' => 'Email atau username wajib diisi.',
            'email.required_without' => 'Email atau username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $login = Str::lower(trim($credentials['login'] ?? $credentials['email']));
        $loginField = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $errorField = array_key_exists('login', $credentials) ? 'login' : 'email';
        $remember = (bool) ($credentials['remember'] ?? false);

        if (! Auth::attempt([
            $loginField => $login,
            'password' => $credentials['password'],
            'status' => 'active',
        ], $remember)) {
            throw ValidationException::withMessages([
                $errorField => 'Email, username, atau password tidak sesuai.',
            ]);
        }

        return [$request->user(), $errorField];
    }

    private function logoutAttempt(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->regenerate();
        $request->session()->regenerateToken();
    }
}
