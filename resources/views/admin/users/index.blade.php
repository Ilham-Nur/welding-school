@extends('layouts.admin')

@section('title', 'User Management')
@section('eyebrow', 'Akses & pengguna')
@section('heading', 'User Management')

@section('content')
    <section class="admin-page-heading">
        <div>
            <h1>Kelola pengguna</h1>
            <p>Atur identitas, status akun, password, dan role setiap pengguna.</p>
        </div>
        @can('users.manage')
            <button class="button button--primary admin-button" type="button" data-modal-open="create-user">+ Tambah pengguna</button>
        @endcan
    </section>

    <form class="admin-filter" method="GET" action="{{ route('admin.users.index') }}">
        <label class="admin-field">
            <span>Cari pengguna</span>
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Nama atau alamat email">
        </label>
        <label class="admin-field">
            <span>Role</span>
            <select name="role">
                <option value="">Semua role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(request('role') === $role->name)>{{ str_replace('-', ' ', ucfirst($role->name)) }}</option>
                @endforeach
            </select>
        </label>
        <label class="admin-field">
            <span>Status</span>
            <select name="status">
                <option value="">Semua status</option>
                <option value="active" @selected(request('status') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
            </select>
        </label>
        <div class="admin-actions">
            <button class="button button--primary admin-button" type="submit">Terapkan</button>
            <a class="button button--outline admin-button" href="{{ route('admin.users.index') }}">Reset</a>
        </div>
    </form>

    <section class="admin-panel">
        <header class="admin-panel__header">
            <div>
                <h2>Daftar pengguna</h2>
                <p>{{ $users->total() }} pengguna ditemukan.</p>
            </div>
        </header>

        @if ($users->isEmpty())
            <div class="admin-empty">
                <span aria-hidden="true">◎</span>
                <h2>Pengguna tidak ditemukan</h2>
                <p>Ubah kata kunci atau filter untuk melihat data lainnya.</p>
            </div>
        @else
            <x-ui.table class="admin-table-wrap">
                    <thead>
                        <tr>
                            <th>Pengguna</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Verifikasi email</th>
                            <th>Login terakhir</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php
                                $userHasHistory = $user->training_applications_count > 0
                                    || $user->invoices_count > 0
                                    || $user->enrollments_count > 0;
                                $userDeleteBlocked = auth()->id() === $user->id
                                    || $userHasHistory
                                    || ($user->hasRole('super-admin') && $superAdminCount <= 1);
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $user->name }}</strong>
                                    <small>{{ $user->email }}</small>
                                </td>
                                <td>{{ str_replace('-', ' ', ucfirst($user->primaryRoleName())) }}</td>
                                <td><x-admin.status-badge :status="$user->status" /></td>
                                <td>
                                    @if ($user->hasVerifiedEmail())
                                        <x-admin.status-badge status="valid">Terverifikasi</x-admin.status-badge>
                                    @else
                                        <x-admin.status-badge status="pending">Belum verifikasi</x-admin.status-badge>
                                    @endif
                                </td>
                                <td>{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</td>
                                <td>
                                    <div class="admin-action-group">
                                        <button class="admin-action-button admin-action-button--view" type="button" data-modal-open="detail-user-{{ $user->id }}">
                                            <x-ui.icon name="eye" size="14" /> Detail
                                        </button>
                                        @can('users.manage')
                                            <button class="admin-action-button admin-action-button--edit" type="button" data-modal-open="edit-user-{{ $user->id }}">
                                                <x-ui.icon name="edit" size="14" /> Edit
                                            </button>
                                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm-dialog="delete-user-{{ $user->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    class="admin-action-button admin-action-button--delete"
                                                    type="submit"
                                                    @disabled($userDeleteBlocked)
                                                    title="{{ $userDeleteBlocked ? ($userHasHistory ? 'Pengguna memiliki riwayat dan tidak dapat dihapus' : 'Akun ini dilindungi') : 'Hapus pengguna' }}"
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
            <x-ui.pagination :paginator="$users" />
        @endif
    </section>

    @foreach ($users as $user)
        <x-ui.modal :id="'detail-user-'.$user->id" title="Detail pengguna" :description="$user->email">
            <dl class="admin-modal-details">
                <div>
                    <dt>Nama lengkap</dt>
                    <dd>{{ $user->name }}</dd>
                </div>
                <div>
                    <dt>Role</dt>
                    <dd>{{ str_replace('-', ' ', ucfirst($user->primaryRoleName())) }}</dd>
                </div>
                <div>
                    <dt>Status akun</dt>
                    <dd>{{ $user->status === 'active' ? 'Aktif' : 'Nonaktif' }}</dd>
                </div>
                <div>
                    <dt>Verifikasi email</dt>
                    <dd>{{ $user->hasVerifiedEmail() ? 'Terverifikasi' : 'Belum verifikasi' }}</dd>
                </div>
                <div>
                    <dt>Terakhir login</dt>
                    <dd>{{ $user->last_login_at?->translatedFormat('d M Y, H:i') ?? 'Belum pernah' }}</dd>
                </div>
                <div>
                    <dt>Riwayat pendaftaran</dt>
                    <dd>{{ $user->training_applications_count }} data</dd>
                </div>
            </dl>
            <x-slot:footer>
                <button class="button button--outline admin-button" type="button" data-modal-close>Tutup</button>
            </x-slot:footer>
        </x-ui.modal>
    @endforeach

    @can('users.manage')
        <x-ui.modal id="create-user" title="Tambah pengguna" description="Akun yang dibuat admin langsung dianggap sudah terverifikasi.">
            <form method="POST" action="{{ route('admin.users.store') }}">
                @csrf
                <div class="admin-form-grid">
                    <label class="admin-field admin-field--full">
                        <span>Nama lengkap</span>
                        <input name="name" value="{{ old('name') }}" required>
                    </label>
                    <label class="admin-field admin-field--full">
                        <span>Alamat email</span>
                        <input name="email" type="email" value="{{ old('email') }}" required>
                    </label>
                    <label class="admin-field">
                        <span>Password</span>
                        <input name="password" type="password" minlength="8" required>
                    </label>
                    <label class="admin-field">
                        <span>Konfirmasi password</span>
                        <input name="password_confirmation" type="password" minlength="8" required>
                    </label>
                    <label class="admin-field">
                        <span>Role</span>
                        <select name="role" required>
                            @foreach ($roles as $role)
                                @if ($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                                    <option value="{{ $role->name }}">{{ str_replace('-', ' ', ucfirst($role->name)) }}</option>
                                @endif
                            @endforeach
                        </select>
                    </label>
                    <label class="admin-field">
                        <span>Status</span>
                        <select name="status" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                    </label>
                </div>
                <div class="admin-form-actions">
                    <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                    <button class="button button--primary admin-button" type="submit">Simpan pengguna</button>
                </div>
            </form>
        </x-ui.modal>

        @foreach ($users as $user)
            <x-ui.modal :id="'edit-user-'.$user->id" title="Edit pengguna" :description="$user->email">
                <form method="POST" action="{{ route('admin.users.update', $user) }}">
                    @csrf
                    @method('PUT')
                    <div class="admin-form-grid">
                        <label class="admin-field admin-field--full">
                            <span>Nama lengkap</span>
                            <input name="name" value="{{ $user->name }}" required>
                        </label>
                        <label class="admin-field admin-field--full">
                            <span>Alamat email</span>
                            <input name="email" type="email" value="{{ $user->email }}" required>
                        </label>
                        <label class="admin-field">
                            <span>Role</span>
                            <select name="role" required>
                                @foreach ($roles as $role)
                                    @if ($role->name !== 'super-admin' || auth()->user()->hasRole('super-admin'))
                                        <option value="{{ $role->name }}" @selected($user->hasRole($role))>{{ str_replace('-', ' ', ucfirst($role->name)) }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>Status</span>
                            <select name="status" required>
                                <option value="active" @selected($user->status === 'active')>Aktif</option>
                                <option value="inactive" @selected($user->status === 'inactive')>Nonaktif</option>
                            </select>
                        </label>
                        <label class="admin-field">
                            <span>Password baru</span>
                            <input name="password" type="password" minlength="8">
                            <small>Kosongkan jika password tidak diubah.</small>
                        </label>
                        <label class="admin-field">
                            <span>Konfirmasi password</span>
                            <input name="password_confirmation" type="password" minlength="8">
                        </label>
                    </div>
                    <div class="admin-form-actions">
                        <button class="button button--outline admin-button" type="button" data-modal-close>Batal</button>
                        <button class="button button--primary admin-button" type="submit">Update pengguna</button>
                    </div>
                </form>
            </x-ui.modal>

            @php
                $userHasHistory = $user->training_applications_count > 0
                    || $user->invoices_count > 0
                    || $user->enrollments_count > 0;
                $userDeleteBlocked = auth()->id() === $user->id
                    || $userHasHistory
                    || ($user->hasRole('super-admin') && $superAdminCount <= 1);
            @endphp
            @unless ($userDeleteBlocked)
                <x-ui.confirmation
                    :id="'delete-user-'.$user->id"
                    title="Hapus pengguna?"
                    confirm-label="Ya, hapus pengguna"
                >
                    Akun <strong>{{ $user->name }}</strong> akan dihapus permanen dan tidak dapat digunakan untuk login kembali.
                </x-ui.confirmation>
            @endunless
        @endforeach
    @endcan
@endsection
