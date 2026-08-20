@php
    $brand = config('branding');
@endphp
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="Dashboard administrasi {{ $brand['name'] }}">
        <meta name="robots" content="noindex, nofollow">
        <title>@yield('title', 'Dashboard Admin') · {{ $brand['name'] }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/style.css') }}?v={{ filemtime(public_path('templates/welding-school/style.css')) }}">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/components.css') }}?v={{ filemtime(public_path('templates/welding-school/components.css')) }}">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/admin.css') }}?v={{ filemtime(public_path('templates/welding-school/admin.css')) }}">
        @can('assets.inspect')
            <link rel="stylesheet" href="{{ asset('templates/welding-school/assets.css') }}?v={{ filemtime(public_path('templates/welding-school/assets.css')) }}">
        @endcan
        @stack('styles')
    </head>
    <body class="admin-page">
        <a class="skip-link" href="#admin-content">Lewati ke konten utama</a>

        <div class="admin-shell">
            <aside class="admin-sidebar" id="admin-sidebar">
                <a class="admin-brand" href="{{ route('admin.dashboard') }}" title="{{ $brand['name'] }}">
                    <span class="brand__mark" aria-hidden="true"><img src="{{ asset($brand['logo']) }}" alt=""></span>
                    <span class="admin-brand__copy">
                        <strong>{{ $brand['name'] }}</strong>
                        <small>{{ $brand['service'] }} · ADMIN</small>
                    </span>
                </a>

                <div class="admin-sidebar__label">Menu utama</div>
                <nav class="admin-navigation" aria-label="Navigasi admin">
                    <a class="{{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.dashboard') }}" data-label="Ringkasan" title="Ringkasan">
                        <span aria-hidden="true"><x-ui.icon name="home" /></span><span class="admin-nav-label">Ringkasan</span>
                    </a>
                    @can('applications.view')
                        <a class="{{ request()->routeIs('admin.applications.*') ? 'is-active' : '' }}" href="{{ route('admin.applications.index') }}" data-label="Approval Pendaftaran" title="Approval Pendaftaran">
                            <span aria-hidden="true"><x-ui.icon name="clipboard-check" /></span><span class="admin-nav-label">Approval Pendaftaran</span>
                            @php
                                $pendingApplicationCount = \App\Models\TrainingApplication::query()
                                    ->whereIn('status', ['submitted', 'under_review'])
                                    ->count();
                            @endphp
                            @if ($pendingApplicationCount)
                                <b>{{ $pendingApplicationCount }}</b>
                            @endif
                        </a>
                    @endcan
                    @can('programs.view')
                        <a class="{{ request()->routeIs('admin.programs.*') ? 'is-active' : '' }}" href="{{ route('admin.programs.index') }}" data-label="Program Pelatihan" title="Program Pelatihan">
                            <span aria-hidden="true"><x-ui.icon name="book-open" /></span><span class="admin-nav-label">Program Pelatihan</span>
                        </a>
                    @endcan
                    @can('batches.view')
                        <a class="{{ request()->routeIs('admin.batches.*') ? 'is-active' : '' }}" href="{{ route('admin.batches.index') }}" data-label="Batch Pelatihan" title="Batch Pelatihan">
                            <span aria-hidden="true"><x-ui.icon name="calendar" /></span><span class="admin-nav-label">Batch Pelatihan</span>
                        </a>
                    @endcan
                    @can('activities.view')
                        <a class="{{ request()->routeIs('admin.activities.*') ? 'is-active' : '' }}" href="{{ route('admin.activities.index') }}" data-label="Aktivitas" title="Aktivitas">
                            <span aria-hidden="true"><x-ui.icon name="file" /></span><span class="admin-nav-label">Aktivitas</span>
                        </a>
                    @endcan
                    @canany(['assets.view', 'assets.inspect'])
                        @php($assetMenuOpen = request()->routeIs('admin.assets.*', 'assets.inspections.*'))
                        <div @class(['admin-nav-group', 'is-open' => $assetMenuOpen]) data-admin-nav-group>
                            <button
                                type="button"
                                class="admin-nav-group__toggle {{ $assetMenuOpen ? 'is-active' : '' }}"
                                data-admin-nav-toggle
                                aria-expanded="{{ $assetMenuOpen ? 'true' : 'false' }}"
                                aria-controls="admin-assets-submenu"
                                data-label="Manajemen Aset"
                                title="Manajemen Aset"
                            >
                                <span aria-hidden="true"><x-ui.icon name="asset" /></span>
                                <span class="admin-nav-label">Manajemen Aset</span>
                                <span class="admin-nav-group__chevron" aria-hidden="true"><x-ui.icon name="chevron-down" size="14" /></span>
                            </button>
                            <div class="admin-nav-group__items" id="admin-assets-submenu" data-admin-nav-items @if (! $assetMenuOpen) hidden @endif>
                                @can('assets.view')
                                    <a class="{{ request()->routeIs('admin.assets.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.assets.dashboard') }}" data-label="Dashboard Aset" title="Dashboard Aset">
                                        <span aria-hidden="true"><x-ui.icon name="home" size="16" /></span><span class="admin-nav-label">Dashboard Aset</span>
                                    </a>
                                    <a class="{{ request()->routeIs('admin.assets.index', 'admin.assets.create', 'admin.assets.edit', 'admin.assets.labels') ? 'is-active' : '' }}" href="{{ route('admin.assets.index') }}" data-label="Daftar Aset" title="Daftar Aset">
                                        <span aria-hidden="true"><x-ui.icon name="list" size="16" /></span><span class="admin-nav-label">Daftar Aset</span>
                                    </a>
                                @endcan
                                @can('assets.inspect')
                                    <button class="admin-nav-submenu-button" type="button" data-open-asset-scanner data-label="Inspeksi Aset" title="Inspeksi Aset">
                                        <span aria-hidden="true"><x-ui.icon name="scan" size="16" /></span><span class="admin-nav-label">Inspeksi Aset</span>
                                    </button>
                                @endcan
                            </div>
                        </div>
                    @endcanany

                    @canany(['storage.view', 'storage.items.manage', 'storage.transactions.manage', 'storage.loans.manage', 'storage.stocktakes.manage', 'storage.reports.view'])
                        @php($storageMenuOpen = request()->routeIs('admin.storage.*', 'admin.storage-items.*'))
                        <div @class(['admin-nav-group', 'is-open' => $storageMenuOpen]) data-admin-nav-group>
                            <button
                                type="button"
                                class="admin-nav-group__toggle {{ $storageMenuOpen ? 'is-active' : '' }}"
                                data-admin-nav-toggle
                                aria-expanded="{{ $storageMenuOpen ? 'true' : 'false' }}"
                                aria-controls="admin-storage-submenu"
                                data-label="Storage"
                                title="Storage"
                            >
                                <span aria-hidden="true"><x-ui.icon name="storage" /></span>
                                <span class="admin-nav-label">Storage</span>
                                <span class="admin-nav-group__chevron" aria-hidden="true"><x-ui.icon name="chevron-down" size="14" /></span>
                            </button>
                            <div class="admin-nav-group__items" id="admin-storage-submenu" data-admin-nav-items @if (! $storageMenuOpen) hidden @endif>
                                @can('storage.view')
                                    <a class="{{ request()->routeIs('admin.storage.dashboard') ? 'is-active' : '' }}" href="{{ route('admin.storage.dashboard') }}" data-label="Dashboard Storage" title="Dashboard Storage">
                                        <span aria-hidden="true"><x-ui.icon name="home" size="16" /></span><span class="admin-nav-label">Dashboard</span>
                                    </a>
                                    <a class="{{ request()->routeIs('admin.storage-items.*') ? 'is-active' : '' }}" href="{{ route('admin.storage-items.index') }}" data-label="Stok Consumable" title="Stok Consumable">
                                        <span aria-hidden="true"><x-ui.icon name="list" size="16" /></span><span class="admin-nav-label">Stok Consumable</span>
                                    </a>
                                    <a class="{{ request()->routeIs('admin.storage.receipts.*') ? 'is-active' : '' }}" href="{{ route('admin.storage.receipts.index') }}" data-label="Penerimaan Barang" title="Penerimaan Barang">
                                        <span aria-hidden="true"><x-ui.icon name="upload" size="16" /></span><span class="admin-nav-label">Penerimaan Barang</span>
                                    </a>
                                    <a class="{{ request()->routeIs('admin.storage.issues.*') ? 'is-active' : '' }}" href="{{ route('admin.storage.issues.index') }}" data-label="Pengeluaran Barang" title="Pengeluaran Barang">
                                        <span aria-hidden="true"><x-ui.icon name="download" size="16" /></span><span class="admin-nav-label">Pengeluaran Barang</span>
                                    </a>
                                    <a class="{{ request()->routeIs('admin.storage.loans.*') ? 'is-active' : '' }}" href="{{ route('admin.storage.loans.index') }}" data-label="Pinjaman Keluar" title="Pinjaman Keluar">
                                        <span aria-hidden="true"><x-ui.icon name="asset" size="16" /></span><span class="admin-nav-label">Pinjaman Keluar</span>
                                    </a>
                                    <a class="{{ request()->routeIs('admin.storage.opnames.*') ? 'is-active' : '' }}" href="{{ route('admin.storage.opnames.index') }}" data-label="Stock Opname" title="Stock Opname">
                                        <span aria-hidden="true"><x-ui.icon name="clipboard-check" size="16" /></span><span class="admin-nav-label">Stock Opname</span>
                                    </a>
                                @endcan
                                @can('storage.reports.view')
                                    <a class="{{ request()->routeIs('admin.storage.reports.*') ? 'is-active' : '' }}" href="{{ route('admin.storage.reports.index') }}" data-label="Laporan Storage" title="Laporan Storage">
                                        <span aria-hidden="true"><x-ui.icon name="file" size="16" /></span><span class="admin-nav-label">Laporan</span>
                                    </a>
                                @endcan
                            </div>
                        </div>
                    @endcanany

                    @canany(['users.view', 'roles.view', 'locations.view', 'employees.view'])
                        <div class="admin-sidebar__label">Master data</div>
                    @endcanany
                    @can('employees.view')
                        <a class="{{ request()->routeIs('admin.employees.*') ? 'is-active' : '' }}" href="{{ route('admin.employees.index') }}" data-label="Data Karyawan" title="Data Karyawan">
                            <span aria-hidden="true"><x-ui.icon name="users" /></span><span class="admin-nav-label">Data Karyawan</span>
                        </a>
                        <a class="{{ request()->routeIs('admin.employee-positions.*') ? 'is-active' : '' }}" href="{{ route('admin.employee-positions.index') }}" data-label="Master Jabatan" title="Master Jabatan">
                            <span aria-hidden="true"><x-ui.icon name="shield" /></span><span class="admin-nav-label">Master Jabatan</span>
                        </a>
                    @endcan
                    @can('roles.view')
                        <a class="{{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}" href="{{ route('admin.roles.index') }}" data-label="Role Management" title="Role Management">
                            <span aria-hidden="true"><x-ui.icon name="shield" /></span><span class="admin-nav-label">Role Management</span>
                        </a>
                    @endcan
                    @can('users.view')
                        <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}" data-label="User Management" title="User Management">
                            <span aria-hidden="true"><x-ui.icon name="users" /></span><span class="admin-nav-label">User Management</span>
                        </a>
                    @endcan
                    @can('locations.view')
                        <a class="{{ request()->routeIs('admin.locations.*') ? 'is-active' : '' }}" href="{{ route('admin.locations.index') }}" data-label="Lokasi" title="Lokasi">
                            <span aria-hidden="true"><x-ui.icon name="location" /></span><span class="admin-nav-label">Lokasi</span>
                        </a>
                    @endcan
                </nav>

                <div class="admin-sidebar__footer">
                    <span>Mode akses</span>
                    <strong>{{ str_replace('-', ' ', auth()->user()->primaryRoleName()) }}</strong>
                </div>
            </aside>

            <div class="admin-workspace">
                <header class="admin-topbar">
                    <button class="admin-menu-toggle" type="button" data-admin-menu aria-controls="admin-sidebar" aria-expanded="false">
                        <x-ui.icon name="menu" />
                        <span class="sr-only">Buka menu admin</span>
                    </button>
                    <button
                        class="admin-sidebar-toggle"
                        type="button"
                        data-admin-sidebar-collapse
                        aria-controls="admin-sidebar"
                        aria-expanded="true"
                        title="Ciutkan sidebar"
                    >
                        <span aria-hidden="true"><x-ui.icon name="panel-left" /></span>
                        <span class="sr-only">Ciutkan sidebar</span>
                    </button>
                    <div class="admin-topbar__title">
                        <span>@yield('eyebrow', $brand['name'])</span>
                        <strong>@yield('heading', 'Dashboard Admin')</strong>
                    </div>
                    <div class="admin-account">
                        <button type="button" data-admin-account aria-expanded="false">
                            <span class="admin-account__avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                            <span>
                                <strong>{{ auth()->user()->name }}</strong>
                                <small>{{ auth()->user()->email }}</small>
                            </span>
                            <x-ui.icon name="chevron-down" size="15" />
                        </button>
                        <div class="admin-account__menu" data-admin-account-menu hidden>
                            <div>
                                <small>Masuk sebagai</small>
                                <strong>{{ str_replace('-', ' ', auth()->user()->primaryRoleName()) }}</strong>
                            </div>
                            <a href="{{ route('home') }}">Buka situs utama</a>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit">Keluar dari akun</button>
                            </form>
                        </div>
                    </div>
                </header>

                <main id="admin-content" class="admin-content" tabindex="-1">
                    @if (session('success'))
                        <button
                            type="button"
                            hidden
                            data-flash-toast
                            data-toast="{{ session('success') }}"
                            data-toast-type="success"
                        ></button>
                    @endif

                    @if (session('warning'))
                        <button
                            type="button"
                            hidden
                            data-flash-toast
                            data-toast="{{ session('warning') }}"
                            data-toast-type="warning"
                        ></button>
                    @endif

                    @if (session('error'))
                        <button
                            type="button"
                            hidden
                            data-flash-toast
                            data-toast="{{ session('error') }}"
                            data-toast-type="danger"
                        ></button>
                    @endif

                    @if ($errors->any())
                        <button
                            type="button"
                            hidden
                            data-flash-toast
                            data-toast="{{ implode(' • ', $errors->all()) }}"
                            data-toast-type="danger"
                        ></button>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        <div class="admin-backdrop" data-admin-backdrop hidden></div>

        @can('assets.inspect')
            <dialog class="asset-scan-dialog" data-asset-scan-dialog aria-labelledby="asset-scan-modal-title">
                <main
                    class="asset-scan-shell"
                    data-asset-scanner
                    data-inspection-url="{{ route('assets.inspections.create', ['asset' => 'ASSET_PUBLIC_ID']) }}"
                    data-lookup-url="{{ route('assets.inspections.resolve') }}"
                >
                    <header class="asset-scan-brand">
                        <div class="asset-scan-modal-title">
                            <span aria-hidden="true"><x-ui.icon name="scan" size="21" /></span>
                            <div><strong id="asset-scan-modal-title">Inspeksi Aset</strong><small>Scan QR atau masukkan Asset ID</small></div>
                        </div>
                        <button type="button" data-close-asset-scanner aria-label="Tutup scanner"><x-ui.icon name="x-circle" size="20" /></button>
                    </header>

                    <section class="asset-scan-heading">
                        <span aria-hidden="true"><x-ui.icon name="scan" size="28" /></span>
                        <div><small>PEMINDAI ASET</small><h1>Scan QR pada label aset</h1><p>Arahkan kamera ke QR yang tertempel pada alat. Checklist akan terbuka otomatis setelah aset dikenali.</p></div>
                    </section>

                    <section class="asset-scan-camera">
                        <div id="asset-qr-reader" class="asset-scan-camera__reader"></div>
                        <div class="asset-scan-camera__placeholder" data-scanner-placeholder>
                            <span aria-hidden="true"><x-ui.icon name="camera" size="42" /></span>
                            <strong>Kamera belum aktif</strong>
                            <p>Tekan tombol di bawah dan izinkan browser menggunakan kamera.</p>
                        </div>
                        <div class="asset-scan-target" aria-hidden="true" hidden data-scanner-target><i></i><i></i><i></i><i></i></div>
                    </section>

                    <div class="asset-scan-status" data-scanner-status aria-live="polite">Siap memindai QR aset.</div>

                    <div class="asset-scan-actions">
                        <button type="button" class="asset-scan-start" data-scanner-start><x-ui.icon name="camera" size="17" /> Aktifkan kamera</button>
                        <button type="button" class="asset-scan-stop" data-scanner-stop hidden>Matikan kamera</button>
                        <label class="asset-scan-file">Pilih foto QR<input type="file" accept="image/*" capture="environment" data-scanner-file></label>
                    </div>

                    <section class="asset-scan-manual">
                        <div><h2>Masukkan Asset ID</h2><p>Gunakan pilihan ini jika kamera tidak tersedia atau label sulit dipindai.</p></div>
                        <div class="asset-inspection-message asset-inspection-message--error" data-scanner-lookup-error hidden></div>
                        <form data-scanner-manual>
                            <label for="asset-code-modal">Asset ID</label>
                            <div><input id="asset-code-modal" name="asset_code" placeholder="ATP-WLD-001" autocomplete="off" required><button type="submit">Buka checklist</button></div>
                        </form>
                    </section>

                    <footer>Hanya pengguna dengan izin inspeksi aset yang dapat membuka dan menyimpan checklist.</footer>
                </main>
            </dialog>
        @endcan

        <x-ui.toast-stack />
        <script src="{{ asset('templates/welding-school/components.js') }}" defer></script>
        <script src="{{ asset('templates/welding-school/admin.js') }}" defer></script>
        @can('assets.inspect')
            @vite('resources/js/asset-scanner.js')
        @endcan
        @stack('scripts')
    </body>
</html>
