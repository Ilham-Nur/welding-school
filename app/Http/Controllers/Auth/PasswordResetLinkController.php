<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetLinkController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
        ]);

        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format alamat email belum benar.',
        ]);

        Password::sendResetLink(['email' => $validated['email']]);

        return response()->json([
            'message' => 'Jika email terdaftar, tautan reset password telah dikirim. Periksa inbox atau folder spam.',
        ]);
    }
}
