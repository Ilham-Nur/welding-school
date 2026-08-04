<?php

namespace App\Services\Auth;

use App\Models\EmailVerificationCode;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailVerificationService
{
    public const CODE_TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public function send(User $user, bool $respectCooldown = true): string
    {
        $current = $user->emailVerificationCode;

        if (
            $respectCooldown
            && $current?->sent_at
            && $current->sent_at->isAfter(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))
        ) {
            $seconds = max(
                1,
                (int) ceil(now()->diffInSeconds(
                    $current->sent_at->copy()->addSeconds(self::RESEND_COOLDOWN_SECONDS),
                    false,
                )),
            );

            throw ValidationException::withMessages([
                'email' => "Tunggu {$seconds} detik sebelum mengirim kode baru.",
            ]);
        }

        $code = (string) random_int(100000, 999999);

        EmailVerificationCode::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::CODE_TTL_MINUTES),
                'sent_at' => now(),
            ],
        );

        $user->notify(new EmailVerificationCodeNotification($code));

        return $code;
    }

    public function verify(User $user, string $code): void
    {
        $error = DB::transaction(function () use ($user, $code): ?string {
            $verification = EmailVerificationCode::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $verification) {
                return 'Kode verifikasi tidak ditemukan. Silakan kirim kode baru.';
            }

            if ($verification->expires_at->isPast()) {
                $verification->delete();

                return 'Kode verifikasi sudah kedaluwarsa. Silakan kirim kode baru.';
            }

            if ($verification->attempts >= self::MAX_ATTEMPTS) {
                return 'Batas percobaan telah tercapai. Silakan kirim kode baru.';
            }

            if (! Hash::check($code, $verification->code_hash)) {
                $verification->increment('attempts');
                $remaining = max(0, self::MAX_ATTEMPTS - $verification->attempts);

                return $remaining > 0
                    ? "Kode verifikasi tidak sesuai. Tersisa {$remaining} percobaan."
                    : 'Batas percobaan telah tercapai. Silakan kirim kode baru.';
            }

            if (! $user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }

            $verification->delete();

            return null;
        });

        if ($error) {
            throw ValidationException::withMessages(['code' => $error]);
        }
    }
}
