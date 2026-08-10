<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

class NewPasswordController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)->letters()->numbers(),
            ],
        ], [
            'token.required' => 'Tautan reset password tidak lengkap.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email belum benar.',
            'password.required' => 'Password baru wajib diisi.',
            'password.confirmed' => 'Konfirmasi password belum sama.',
        ]);

        $status = Password::reset(
            [
                'email' => $validated['email'],
                'password' => $validated['password'],
                'password_confirmation' => $request->input('password_confirmation'),
                'token' => $validated['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                DB::table((string) config('session.table', 'sessions'))
                    ->where('user_id', $user->getKey())
                    ->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Tautan reset password tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            ]);
        }

        return response()->json([
            'message' => 'Password berhasil diperbarui. Silakan masuk menggunakan password baru.',
        ]);
    }
}
