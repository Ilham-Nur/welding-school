<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $permissions = Permission::query()->orderBy('name')->get();

        return view('admin.roles.index', [
            'roles' => Role::query()
                ->where('name', '!=', 'participant')
                ->with('permissions')
                ->withCount('users')
                ->orderBy('name')
                ->get(),
            'permissions' => $permissions,
            'permissionGroups' => $this->groupPermissions($permissions),
            'permissionLabels' => config('admin.permission_labels'),
        ]);
    }

    /**
     * @param  Collection<int, Permission>  $permissions
     * @return Collection<int, array{key: string, label: string, description: string, permissions: Collection<int, Permission>}>
     */
    private function groupPermissions(Collection $permissions): Collection
    {
        $permissionsByName = $permissions->keyBy('name');
        $groupedNames = collect();

        $groups = collect(config('admin.permission_groups', []))
            ->map(function (array $group, string $key) use ($permissionsByName, $groupedNames): array {
                $groupPermissions = collect($group['permissions'] ?? [])
                    ->map(fn (string $name) => $permissionsByName->get($name))
                    ->filter()
                    ->values();

                $groupedNames->push(...$groupPermissions->pluck('name'));

                return [
                    'key' => $key,
                    'label' => $group['label'] ?? $key,
                    'description' => $group['description'] ?? '',
                    'permissions' => $groupPermissions,
                ];
            })
            ->filter(fn (array $group): bool => $group['permissions']->isNotEmpty())
            ->values();

        $ungrouped = $permissions
            ->reject(fn (Permission $permission): bool => $groupedNames->contains($permission->name))
            ->values();

        if ($ungrouped->isNotEmpty()) {
            $groups->push([
                'key' => 'other',
                'label' => 'Izin lainnya',
                'description' => 'Izin tambahan yang belum ditempatkan pada kelompok khusus.',
                'permissions' => $ungrouped,
            ]);
        }

        return $groups;
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRole($request);
        $name = Str::lower($validated['name']);

        if (in_array($name, ['super-admin', 'admin', 'participant'], true)) {
            throw ValidationException::withMessages([
                'name' => 'Nama role sistem tersebut sudah dicadangkan.',
            ]);
        }

        $role = Role::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Role baru berhasil dibuat.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($role->name === 'super-admin') {
            throw ValidationException::withMessages([
                'role' => 'Role super-admin dikunci agar akses utama tidak terputus.',
            ]);
        }

        $validated = $this->validateRole($request, $role);
        $name = Str::lower($validated['name']);

        if (
            in_array($role->name, ['admin', 'participant'], true)
            && $name !== $role->name
        ) {
            throw ValidationException::withMessages([
                'name' => 'Nama role sistem tidak dapat diubah.',
            ]);
        }

        $role->update(['name' => $name]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', 'Role dan izin berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if (in_array($role->name, ['super-admin', 'admin', 'participant'], true)) {
            throw ValidationException::withMessages([
                'role' => 'Role bawaan sistem tidak dapat dihapus.',
            ]);
        }

        if ($role->users()->exists()) {
            throw ValidationException::withMessages([
                'role' => 'Role masih digunakan oleh pengguna. Pindahkan pengguna ke role lain terlebih dahulu.',
            ]);
        }

        $role->delete();

        return to_route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'web')
                    ->ignore($role),
            ],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ], [
            'name.regex' => 'Gunakan huruf kecil, angka, dan tanda hubung untuk nama role.',
        ]);
    }
}
