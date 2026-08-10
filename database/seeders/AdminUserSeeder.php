<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) config('admin.seed.email')));
        $admin = User::query()->where('email', $email)->first();

        if (! $admin) {
            $password = (string) config('admin.seed.password');

            if ($password === '') {
                throw new RuntimeException(
                    'ADMIN_PASSWORD wajib dikonfigurasi saat membuat akun super-admin pertama kali.',
                );
            }

            $admin = User::query()->create([
                'email' => $email,
                'name' => config('admin.seed.name'),
                'password' => $password,
                'email_verified_at' => now(),
                'status' => 'active',
            ]);
        } else {
            $admin->forceFill([
                'name' => config('admin.seed.name'),
                'email_verified_at' => $admin->email_verified_at ?: now(),
                'status' => 'active',
            ])->save();
        }

        $admin->syncRoles(['super-admin']);
    }
}
