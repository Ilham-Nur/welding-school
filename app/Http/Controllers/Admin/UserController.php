<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->with('roles')
            ->withCount(['trainingApplications', 'invoices', 'enrollments'])
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.trim((string) $request->string('search')).'%';
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('name', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when(
                $request->filled('role'),
                fn ($query) => $query->role((string) $request->string('role')),
            )
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'roles' => Role::query()->orderBy('name')->get(),
            'superAdminCount' => User::role('super-admin')->count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $this->ensureRoleCanBeAssigned($request, $validated['role']);

        DB::transaction(function () use ($validated): void {
            $user = User::query()->create([
                'name' => trim($validated['name']),
                'email' => Str::lower(trim($validated['email'])),
                'password' => $validated['password'],
                'email_verified_at' => now(),
                'status' => $validated['status'],
            ]);

            $user->syncRoles([$validated['role']]);
        });

        return to_route('admin.users.index')
            ->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        $this->ensureRoleCanBeAssigned($request, $validated['role']);
        $this->protectAdministratorAccount($request, $user, $validated);

        DB::transaction(function () use ($validated, $user): void {
            $attributes = [
                'name' => trim($validated['name']),
                'email' => Str::lower(trim($validated['email'])),
                'status' => $validated['status'],
            ];

            if (filled($validated['password'] ?? null)) {
                $attributes['password'] = $validated['password'];
            }

            $user->update($attributes);
            $user->syncRoles([$validated['role']]);
        });

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Anda tidak dapat menghapus akun yang sedang digunakan.',
            ]);
        }

        $user->loadCount(['trainingApplications', 'invoices', 'enrollments']);

        if (
            $user->training_applications_count > 0
            || $user->invoices_count > 0
            || $user->enrollments_count > 0
        ) {
            throw ValidationException::withMessages([
                'user' => 'Pengguna memiliki riwayat pendaftaran atau transaksi. Nonaktifkan akun agar riwayat tetap aman.',
            ]);
        }

        if ($user->hasRole('super-admin') && User::role('super-admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Minimal satu akun super-admin harus tetap tersedia.',
            ]);
        }

        DB::transaction(function () use ($user): void {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();
        });

        return to_route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function protectAdministratorAccount(Request $request, User $user, array $validated): void
    {
        if ($request->user()->is($user) && $validated['status'] !== 'active') {
            throw ValidationException::withMessages([
                'status' => 'Anda tidak dapat menonaktifkan akun yang sedang digunakan.',
            ]);
        }

        if (
            $user->hasRole('super-admin')
            && $validated['role'] !== 'super-admin'
            && User::role('super-admin')->count() <= 1
        ) {
            throw ValidationException::withMessages([
                'role' => 'Minimal satu akun super-admin harus tetap tersedia.',
            ]);
        }
    }

    private function ensureRoleCanBeAssigned(Request $request, string $role): void
    {
        if ($role === 'super-admin' && ! $request->user()->hasRole('super-admin')) {
            throw ValidationException::withMessages([
                'role' => 'Hanya super-admin yang dapat memberikan role super-admin.',
            ]);
        }
    }
}
