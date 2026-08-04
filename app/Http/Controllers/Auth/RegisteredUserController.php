<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    public function __construct(
        private readonly EmailVerificationService $verification,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
        ]);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[A-Za-z0-9._-]+$/',
                'unique:users,username',
            ],
            'email' => ['required', 'string', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->letters()->numbers(),
            ],
            'agreement' => ['accepted'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.',
            'username.unique' => 'Username sudah digunakan peserta lain.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email belum benar.',
            'email.unique' => 'Email ini sudah terdaftar. Silakan masuk.',
            'password.required' => 'Password wajib diisi.',
            'password.confirmed' => 'Konfirmasi password belum sama.',
            'agreement.accepted' => 'Anda harus menyetujui syarat dan kebijakan privasi.',
        ]);

        $user = DB::transaction(function () use ($validated): User {
            $user = User::query()->create([
                'name' => Str::lower(trim($validated['username'])),
                'username' => Str::lower(trim($validated['username'])),
                'email' => Str::lower(trim($validated['email'])),
                'password' => $validated['password'],
                'status' => 'active',
            ]);

            Role::findOrCreate('participant', 'web');
            $user->assignRole('participant');

            return $user;
        });

        $request->session()->regenerate();
        $request->session()->put([
            'pending_verification_user_id' => $user->id,
            'pending_verification_email' => $user->email,
        ]);
        $code = $this->verification->send($user, respectCooldown: false);

        return response()->json([
            'message' => 'Akun berhasil dibuat. Masukkan kode yang dikirim ke email Anda.',
            'requires_verification' => true,
            'email' => $user->email,
            'debug_code' => app()->isLocal() ? $code : null,
        ], 202);
    }
}
