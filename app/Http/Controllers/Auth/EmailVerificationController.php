<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verification,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Kode verifikasi wajib diisi.',
            'code.digits' => 'Kode verifikasi harus terdiri dari 6 angka.',
        ]);

        $user = $this->pendingUser($request);
        $this->verification->verify($user, $validated['code']);

        $this->clearPendingUser($request);
        $user->forceFill(['last_login_at' => now()])->save();
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json([
            'message' => 'Email berhasil diverifikasi.',
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

    public function resend(Request $request): JsonResponse
    {
        $user = $this->pendingUser($request);
        $code = $this->verification->send($user);

        return response()->json([
            'message' => 'Kode verifikasi baru telah dikirim.',
            'debug_code' => app()->isLocal() ? $code : null,
        ]);
    }

    private function pendingUser(Request $request): User
    {
        $userId = $request->session()->get('pending_verification_user_id');
        $user = $userId ? User::query()->find($userId) : null;

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Sesi verifikasi tidak ditemukan. Silakan daftar atau login kembali.',
            ]);
        }

        return $user;
    }

    private function clearPendingUser(Request $request): void
    {
        $request->session()->forget([
            'pending_verification_user_id',
            'pending_verification_email',
        ]);
    }
}
