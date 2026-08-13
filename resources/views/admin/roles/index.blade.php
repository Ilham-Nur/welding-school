@extends('layouts.admin')

@section('title', 'Role Management')
@section('eyebrow', 'Akses & pengguna')
@section('heading', 'Role Management')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Kelola role dan izin</h1>
            <p>Tentukan kemampuan setiap role. Super-admin selalu memperoleh seluruh izin sistem.</p>
        </div>
        @can('roles.manage')
            <button class="button button--primary admin-button" type="button" data-modal-open="create-role">+ Tambah role</button>
        @endcan
    </section>

    <section class="admin-role-grid">
        @foreach ($roles as $role)
            @php
                $systemRole = in_array($role->name, ['super-admin', 'admin', 'participant'], true);
                $roleDeleteBlocked = $systemRole || $role->users_count > 0;
            @endphp
            <article class="admin-role-card">
                <header class="admin-role-card__heading">
                    <div>
                        <h2>{{ str_replace('-', ' ', ucfirst($role->name)) }}</h2>
                        <p>{{ $role->users_count }} pengguna · {{ $role->permissions->count() }} izin</p>
                    </div>
                    <div class="admin-action-group">
                        @if ($role->name === 'super-admin')
                            <x-admin.status-badge status="active">Dikunci</x-admin.status-badge>
                        @endif
                        <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="detail-role-{{ $role->id }}">
                            <x-ui.icon name="eye" size="14" /> Detail
                        </button>
                        @can('roles.manage')
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
                </header>

                <form method="POST" action="{{ route('admin.roles.update', $role) }}">
                    @csrf
                    @method('PUT')
                    <label class="admin-field" style="margin-bottom: 15px">
                        <span>Nama role</span>
                        <input
                            name="name"
                            value="{{ $role->name }}"
                            @disabled(in_array($role->name, ['super-admin', 'admin', 'participant'], true))
                            required
                        >
                        @if (in_array($role->name, ['super-admin', 'admin', 'participant'], true))
                            <input name="name" type="hidden" value="{{ $role->name }}">
                        @endif
                    </label>

                    @include('admin.roles._permission-groups', [
                        'selectedPermissions' => $role->name === 'super-admin'
                            ? $permissions->pluck('name')
                            : $role->permissions->pluck('name'),
                        'disabled' => $role->name === 'super-admin' || ! auth()->user()->can('roles.manage'),
                    ])

                    @can('roles.manage')
                        @if ($role->name !== 'super-admin')
                            <div class="admin-form-actions">
                                <button class="button button--primary admin-button" type="submit">
                                    <x-ui.icon name="edit" size="15" /> Update role
                                </button>
                            </div>
                        @endif
                    @endcan
                </form>
            </article>
        @endforeach
    </section>

    @foreach ($roles as $role)
        @php
            $systemRole = in_array($role->name, ['super-admin', 'admin', 'participant'], true);
            $roleDeleteBlocked = $systemRole || $role->users_count > 0;
        @endphp

        <x-ui.modal :id="'detail-role-'.$role->id" title="Detail role" :description="$role->name">
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
                    <dt>Total izin</dt>
                    <dd>{{ $role->permissions->count() }} izin</dd>
                </div>
            </dl>
            <div class="admin-modal-permissions">
                <strong>Izin yang dimiliki</strong>
                @if ($role->name === 'super-admin' || $role->permissions->isNotEmpty())
                    <div class="admin-permission-detail-groups">
                        @foreach ($permissionGroups as $group)
                            @php
                                $groupPermissions = $role->name === 'super-admin'
                                    ? $group['permissions']
                                    : $group['permissions']->filter(
                                        fn ($permission) => $role->permissions->contains('name', $permission->name),
                                    );
                            @endphp
                            @if ($groupPermissions->isNotEmpty())
                                <section>
                                    <h3>{{ $group['label'] }}</h3>
                                    <div class="admin-permission-tags">
                                        @foreach ($groupPermissions as $permission)
                                            <span>{{ $permissionLabels[$permission->name] ?? $permission->name }}</span>
                                        @endforeach
                                    </div>
                                </section>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="admin-permission-tags">
                        <small>Role ini belum memiliki izin.</small>
                    </div>
                @endif
            </div>
            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Tutup</button>
            </x-slot:footer>
        </x-ui.modal>

        @can('roles.manage')
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
        <x-ui.modal id="create-role" title="Tambah role" description="Buat role khusus lalu pilih izin yang boleh digunakan.">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <label class="admin-field">
                    <span>Nama role</span>
                    <input name="name" placeholder="contoh: verifier" pattern="[a-z0-9-]+" required>
                    <small>Gunakan huruf kecil dan tanda hubung, tanpa spasi.</small>
                </label>
                <div style="margin-top: 16px">
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
