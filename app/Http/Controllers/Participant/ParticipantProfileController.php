<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParticipantProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('participantProfile');

        return response()->json([
            'profile' => $user->participantProfileData(),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('participantProfile');

        $request->merge([
            'username' => Str::lower(trim((string) $request->input('username'))),
            'identity_number' => trim((string) $request->input('identity_number')),
        ]);

        $validated = $request->validate([
            'username' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/^[A-Za-z0-9._-]+$/',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'full_name' => ['required', 'string', 'min:3', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'identity_type' => ['required', Rule::in(['ktp', 'passport'])],
            'identity_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('participant_profiles', 'identity_number')
                    ->ignore($user->participantProfile?->id),
            ],
            'birth_place' => ['required', 'string', 'max:100'],
            'birth_date' => ['required', 'date', 'before:-17 years'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'address' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'last_education' => ['required', 'string', 'max:50'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['required', 'string', 'max:255'],
            'emergency_contact_phone' => ['required', 'string', 'max:30'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, atau tanda hubung.',
            'username.unique' => 'Username sudah digunakan peserta lain.',
            'full_name.required' => 'Nama lengkap wajib diisi.',
            'identity_number.unique' => 'Nomor identitas sudah digunakan peserta lain.',
            'birth_date.before' => 'Peserta harus berusia minimal 17 tahun.',
        ]);

        DB::transaction(function () use ($user, $validated): void {
            $user->update([
                'username' => Str::lower(trim($validated['username'])),
                'name' => trim($validated['full_name']),
            ]);

            $user->participantProfile()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'phone' => trim($validated['phone']),
                    'identity_type' => $validated['identity_type'],
                    'identity_number' => trim($validated['identity_number']),
                    'birth_place' => trim($validated['birth_place']),
                    'birth_date' => $validated['birth_date'],
                    'gender' => $validated['gender'],
                    'address' => trim($validated['address']),
                    'city' => trim($validated['city']),
                    'province' => trim($validated['province']),
                    'postal_code' => trim($validated['postal_code'] ?? ''),
                    'last_education' => $validated['last_education'],
                    'occupation' => trim($validated['occupation'] ?? ''),
                    'emergency_contact_name' => trim($validated['emergency_contact_name']),
                    'emergency_contact_phone' => trim($validated['emergency_contact_phone']),
                ],
            );
        });

        $user->refresh()->load('participantProfile');

        return response()->json([
            'message' => 'Profil dan data diri berhasil disimpan.',
            'profile' => $user->participantProfileData(),
        ]);
    }
}
