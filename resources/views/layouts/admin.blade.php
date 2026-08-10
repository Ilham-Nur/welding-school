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

                    @canany(['users.view', 'roles.view'])
                        <div class="admin-sidebar__label">Akses & pengguna</div>
                    @endcanany
                    @can('users.view')
                        <a class="{{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}" data-label="User Management" title="User Management">
                            <span aria-hidden="true"><x-ui.icon name="users" /></span><span class="admin-nav-label">User Management</span>
                        </a>
                    @endcan
                    @can('roles.view')
                        <a class="{{ request()->routeIs('admin.roles.*') ? 'is-active' : '' }}" href="{{ route('admin.roles.index') }}" data-label="Role Management" title="Role Management">
                            <span aria-hidden="true"><x-ui.icon name="shield" /></span><span class="admin-nav-label">Role Management</span>
                        </a>
                    @endcan
                </nav>

                <div class="admin-sidebar__footer">
                    <span>Mode akses</span>
                    <strong>{{ str_replace('-', ' ', auth()->user()->primaryRoleName()) }}</strong>
                    <a href="{{ route('home') }}#member-programs">Lihat tampilan peserta →</a>
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
                        <x-ui.alert type="success" title="Berhasil" dismissible>
                            {{ session('success') }}
                        </x-ui.alert>
                        <button
                            type="button"
                            hidden
                            data-admin-flash-toast
                            data-toast="{{ session('success') }}"
                            data-toast-type="success"
                        ></button>
                    @endif

                    @if ($errors->any())
                        <x-ui.alert type="danger" title="Ada data yang perlu diperbaiki" dismissible>
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </x-ui.alert>
                    @endif

                    @yield('content')
                </main>
            </div>
        </div>

        <div class="admin-backdrop" data-admin-backdrop hidden></div>
        <x-ui.toast-stack />
        <script src="{{ asset('templates/welding-school/components.js') }}" defer></script>
        <script src="{{ asset('templates/welding-school/admin.js') }}" defer></script>
        @stack('scripts')
    </body>
</html>
