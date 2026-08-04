<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = Str::lower(trim((string) config('admin.seed.email')));

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => config('admin.seed.name'),
                'password' => config('admin.seed.password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
        );

        $admin->syncRoles(['super-admin']);
    }
}
