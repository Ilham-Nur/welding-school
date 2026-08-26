@extends('layouts.admin')

@section('title', 'Role Management')
@section('eyebrow', 'Akses & pengguna')
@section('heading', 'Role Management')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Kelola role dan izin</h1>
            <p>Atur akses setiap role secara ringkas. Super-admin selalu memperoleh seluruh izin sistem.</p>
        </div>
        @can('roles.manage')
            <button class="button button--primary admin-button" type="button" data-modal-open="create-role">+ Tambah role</button>
        @endcan
    </section>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar role</h2>
                <p>{{ $roles->count() }} role tersedia untuk mengatur akses pengguna.</p>
            </div>
        </header>

        @if ($roles->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true">◎</span>
                <h2>Belum ada role</h2>
                <p>Tambahkan role pertama untuk mulai mengatur akses pengguna.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Jenis</th>
                        <th>Pengguna</th>
                        <th>Akses diberikan</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $role)
                        @php
                            $systemRole = in_array($role->name, ['super-admin', 'admin', 'participant'], true);
                            $roleDeleteBlocked = $systemRole || $role->users_count > 0;
                            $permissionCount = $role->name === 'super-admin'
                                ? $permissions->count()
                                : $role->permissions->count();
                        @endphp
                        <tr data-role-name="{{ $role->name }}">
                            <td>
                                <strong>{{ str_replace('-', ' ', ucfirst($role->name)) }}</strong>
                                <small>{{ $role->name }}</small>
                            </td>
                            <td>
                                @if ($role->name === 'super-admin')
                                    <x-admin.status-badge status="active">Sistem · Dikunci</x-admin.status-badge>
                                @elseif ($systemRole)
                                    <x-admin.status-badge status="pending">Role sistem</x-admin.status-badge>
                                @else
                                    <x-admin.status-badge status="valid">Role khusus</x-admin.status-badge>
                                @endif
                            </td>
                            <td>{{ $role->users_count }} pengguna</td>
                            <td>
                                <button class="admin-role-access-summary" type="button" data-modal-open="detail-role-{{ $role->id }}">
                                    <strong>{{ $permissionCount }} izin</strong>
                                    <span>Lihat akses</span>
                                </button>
                            </td>
                            <td>
                                <div class="admin-action-group">
                                    <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="detail-role-{{ $role->id }}">
                                        <x-ui.icon name="eye" size="14" /> Detail
                                    </button>
                                    @can('roles.manage')
                                        @if ($role->name !== 'super-admin')
                                            <button class="admin-action-button admin-action-button--edit" type="button" data-modal-open="edit-role-{{ $role->id }}">
                                                <x-ui.icon name="edit" size="14" /> Edit
                                            </button>
                                        @endif
                                        <form method="POST" action="{{ route('admin.roles.destroy', $role) }}" data-confirm-dialog="delete-role-{{ $role->id }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                class="admin-action-button admin-action-button--delete"
                                                type="submit"
                                                @disabled($roleDeleteBlocked)
                                                title="{{ $roleDeleteBlocked ? ($systemRole ? 'Role bawaan sistem tidak dapat dihapus' : 'Role masih digunakan pengguna') : 'Hapus role' }}"
                                            >
                                                <x-ui.icon name="trash" size="14" /> Hapus
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </x-ui.table>
        @endif
    </section>

    @foreach ($roles as $role)
        @php
            $systemRole = in_array($role->name, ['super-admin', 'admin', 'participant'], true);
            $roleDeleteBlocked = $systemRole || $role->users_count > 0;
            $selectedRolePermissions = $role->name === 'super-admin'
                ? $permissions->pluck('name')
                : $role->permissions->pluck('name');
        @endphp

        <x-ui.modal
            :id="'detail-role-'.$role->id"
            title="Detail akses role"
            :description="str_replace('-', ' ', ucfirst($role->name))"
            size="large"
        >
            <dl class="admin-modal-details">
                <div>
                    <dt>Nama role</dt>
                    <dd>{{ str_replace('-', ' ', ucfirst($role->name)) }}</dd>
                </div>
                <div>
                    <dt>Jenis role</dt>
                    <dd>{{ $systemRole ? 'Role bawaan sistem' : 'Role khusus' }}</dd>
                </div>
                <div>
                    <dt>Pengguna</dt>
                    <dd>{{ $role->users_count }} pengguna</dd>
                </div>
                <div>
                    <dt>Total akses</dt>
                    <dd>{{ $selectedRolePermissions->count() }} izin</dd>
                </div>
            </dl>

            <div class="admin-modal-permissions admin-role-detail-permissions">
                <strong>Akses yang dimiliki</strong>
                @if ($selectedRolePermissions->isNotEmpty())
                    <div class="admin-permission-detail-groups">
                        @foreach ($permissionGroups as $group)
                            @php
                                $groupPermissions = $group['permissions']->filter(
                                    fn ($permission) => $selectedRolePermissions->contains($permission->name),
                                );
                            @endphp
                            @if ($groupPermissions->isNotEmpty())
                                <section>
                                    <h3>{{ $group['label'] }}</h3>
                                    <div class="admin-permission-tags">
                                        @foreach ($groupPermissions as $permission)
                                            <span>
                                                {{ $permissionLabels[$permission->name] ?? $permission->name }}
                                                <small>{{ $permission->name }}</small>
                                            </span>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="admin-permission-tags">
                        <small>Role ini belum memiliki akses.</small>
                    </div>
                @endif
            </div>

            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Tutup</button>
            </x-slot:footer>
        </x-ui.modal>

        @can('roles.manage')
            @if ($role->name !== 'super-admin')
                <x-ui.modal
                    :id="'edit-role-'.$role->id"
                    title="Edit role dan akses"
                    :description="'Atur izin untuk role '.str_replace('-', ' ', $role->name).'.'"
                    size="large"
                >
                    <form method="POST" action="{{ route('admin.roles.update', $role) }}" class="admin-role-permission-form">
                        @csrf
                        @method('PUT')
                        <label class="admin-field">
                            <span>Nama role</span>
                            <input
                                name="name"
                                value="{{ $role->name }}"
                                @disabled($systemRole)
                                required
                            >
                            @if ($systemRole)
                                <input name="name" type="hidden" value="{{ $role->name }}">
                                <small>Nama role bawaan sistem tidak dapat diubah.</small>
                            @else
                                <small>Gunakan huruf kecil dan tanda hubung, tanpa spasi.</small>
                            @endif
                        </label>

                        <div class="admin-role-permission-scroll" aria-label="Daftar izin role {{ $role->name }}">
                            @include('admin.roles._permission-groups', [
                                'selectedPermissions' => $role->permissions->pluck('name'),
                                'disabled' => false,
                            ])
                        </div>

                        <div class="admin-form-actions">
                            <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                            <button class="button button--primary admin-button" type="submit">Simpan perubahan</button>
                        </div>
                    </form>
                </x-ui.modal>
            @endif

            @unless ($roleDeleteBlocked)
                <x-ui.confirmation
                    :id="'delete-role-'.$role->id"
                    title="Hapus role?"
                    confirm-label="Ya, hapus role"
                >
                    Role <strong>{{ $role->name }}</strong> akan dihapus permanen.
                </x-ui.confirmation>
            @endunless
        @endcan
    @endforeach

    @can('roles.manage')
        <x-ui.modal id="create-role" title="Tambah role" description="Buat role khusus lalu pilih akses yang boleh digunakan." size="large">
            <form method="POST" action="{{ route('admin.roles.store') }}" class="admin-role-permission-form">
                @csrf
                <label class="admin-field">
                    <span>Nama role</span>
                    <input name="name" value="{{ old('name') }}" placeholder="contoh: storeman" pattern="[a-z0-9-]+" required>
                    <small>Gunakan huruf kecil dan tanda hubung, tanpa spasi.</small>
                </label>

                <div class="admin-role-permission-scroll" aria-label="Daftar izin role baru">
                    @include('admin.roles._permission-groups', [
                        'selectedPermissions' => collect(old('permissions', [])),
                        'disabled' => false,
                    ])
                </div>

                <div class="admin-form-actions">
                    <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                    <button class="button button--primary admin-button" type="submit">Buat role</button>
                </div>
            </form>
        </x-ui.modal>
    @endcan
@endsection
