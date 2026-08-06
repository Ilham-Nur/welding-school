<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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

        $user = $request->user();

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
            'redirect_to' => $user->isAdmin() ? route('admin.dashboard') : null,
        ]);
    }

    public function destroy(Request $request): JsonResponse|RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Anda telah keluar dari akun.',
            ]);
        }

        return redirect(route('home').'#account')
            ->with('auth_status', 'Anda telah keluar dari akun.');
    }
}
