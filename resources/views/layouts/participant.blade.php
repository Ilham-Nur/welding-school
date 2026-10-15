@php
    $brand = config('branding');
    $participant = auth()->user();
    $displayName = $participant->name;
    $words = preg_split('/\s+/u', trim($displayName));
    $initials = mb_strtoupper(mb_substr($words[0], 0, 1).(count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
    $avatar = $participant->profileAvatarUrl();
    $dashboardUrl = fn (string $view, array $parameters = []) => route('home', ['participant_view' => $view, ...$parameters]).'#member-programs';
    $participantMenu = [
        ['home', '⌂', 'Dashboard', true],
        ['programs', '▤', 'Program Pelatihan', true],
        ['applications', '□', 'Pendaftaran Saya', $participant->trainingApplications()->exists()],
        ['payments', '◇', 'Pembayaran', $participant->invoices()->exists()],
        ['training', '◉', 'Pelatihan Saya', true],
        ['help', '?', 'Bantuan', true],
    ];
@endphp
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#071e33">
    <title>@yield('title', 'Portal Peserta') · {{ $brand['name'] }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="stylesheet" href="{{ asset('templates/welding-school/style.css') }}?v={{ filemtime(public_path('templates/welding-school/style.css')) }}">
    <link rel="stylesheet" href="{{ asset('templates/welding-school/components.css') }}?v={{ filemtime(public_path('templates/welding-school/components.css')) }}">
    <link rel="stylesheet" href="{{ asset('templates/welding-school/participant-shell.css') }}?v={{ filemtime(public_path('templates/welding-school/participant-shell.css')) }}">
    @stack('head')
    @stack('styles')
</head>
<body class="dashboard-mode participant-portal">
    <a class="skip-link" href="#app">Lewati ke konten utama</a>
    <div class="dashboard-preview applicant-dashboard" data-participant-shell>
        <aside class="dashboard-sidebar" id="participant-sidebar">
            <a class="brand" href="{{ $dashboardUrl('home') }}" aria-label="{{ $brand['name'] }} · Dashboard peserta">
                <span class="brand__mark" aria-hidden="true"><img src="{{ asset($brand['logo']) }}" alt=""></span>
                <span class="brand__copy"><strong>{{ $brand['name'] }}</strong><small>{{ $brand['service'] }}</small></span>
            </a>
            <div class="participant-profile">
                @include('participant.partials.avatar', ['avatarClass' => 'participant-avatar'])
                <div><strong>{{ $displayName }}</strong><small>Peserta Pelatihan</small></div>
            </div>
            <nav aria-label="Menu peserta">
                @foreach($participantMenu as [$section, $icon, $label, $available])
                    @if($available)
                        <a href="{{ $dashboardUrl($section) }}" @class(['is-active' => $section === 'training']) @if($section === 'training') aria-current="true" @endif aria-label="{{ $label }}" title="{{ $label }}">
                            <span aria-hidden="true">{{ $icon }}</span><span class="dashboard-nav-label">{{ $label }}</span>
                        </a>
                    @else
                        <button type="button" disabled><span aria-hidden="true">{{ $icon }}</span><span class="dashboard-nav-label">{{ $label }}</span></button>
                    @endif
                @endforeach
            </nav>
        </aside>
        <button class="dashboard-sidebar-backdrop" data-participant-sidebar-toggle type="button" aria-label="Tutup sidebar"></button>
        <div class="dashboard-body">
            <header>
                <div><span>Portal peserta</span><strong>@yield('page-title', 'Detail Pelatihan')</strong></div>
                <div class="dashboard-header-actions">
                    <button class="dashboard-sidebar-toggle" data-participant-sidebar-toggle type="button" aria-label="Ciutkan sidebar" aria-controls="participant-sidebar" aria-expanded="true"><span aria-hidden="true">«</span></button>
                    <div class="dashboard-account">
                        <span>{{ $participant->email }}</span>
                        <div class="profile-menu" data-participant-profile-menu>
                            <button class="profile-menu__trigger" data-participant-profile-toggle type="button" aria-label="Buka menu profil" aria-expanded="false" aria-controls="participant-profile-menu">
                                @include('participant.partials.avatar', ['avatarClass' => 'avatar'])
                                <span class="profile-menu__chevron" aria-hidden="true">⌄</span>
                            </button>
                            <div class="profile-dropdown" id="participant-profile-menu" hidden>
                                <div class="profile-dropdown__identity">
                                    @include('participant.partials.avatar', ['avatarClass' => 'profile-dropdown__avatar'])
                                    <div><strong>{{ $participant->username ? '@'.$participant->username : $displayName }}</strong><small>{{ $participant->email }}</small></div>
                                </div>
                                <div class="profile-dropdown__divider"></div>
                                <a class="profile-dropdown__item" href="{{ $dashboardUrl('profile') }}"><span aria-hidden="true">●</span>Profil &amp; Data Diri</a>
                                <div class="profile-dropdown__divider"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button class="profile-dropdown__logout" type="submit"><span aria-hidden="true">↗</span>Logout</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            <main id="app" class="dashboard-content" tabindex="-1">
                <x-ui.feedback />
                <div class="participant-detail-toolbar">
                    <a class="participant-detail-back" href="{{ $dashboardUrl('training') }}"><span aria-hidden="true">←</span> Kembali ke daftar pelatihan</a>
                    <div><span class="eyebrow">DETAIL PELATIHAN</span><strong>{{ $batch->trainingProgram->title }}</strong></div>
                </div>
                <section class="training-access-panel">
                    <div class="training-access-panel__heading">
                        <div><span class="eyebrow">AKSES PER PELATIHAN</span><h2>{{ $batch->trainingProgram->title }}</h2><p>{{ $batch->name }}</p></div>
                    </div>
                    @include('participant.partials.training-tabs')
                    <div class="training-access-content">@yield('content')</div>
                </section>
            </main>
        </div>
    </div>
    <x-ui.loading />
    <x-ui.toast-stack />
    <script src="{{ asset('templates/welding-school/components.js') }}?v={{ filemtime(public_path('templates/welding-school/components.js')) }}" defer></script>
    <script src="{{ asset('templates/welding-school/form-feedback.js') }}?v={{ filemtime(public_path('templates/welding-school/form-feedback.js')) }}" defer></script>
    <script src="{{ asset('templates/welding-school/participant-shell.js') }}?v={{ filemtime(public_path('templates/welding-school/participant-shell.js')) }}" defer></script>
    @stack('scripts')
    <script src="{{ asset('templates/welding-school/loading.js') }}?v={{ filemtime(public_path('templates/welding-school/loading.js')) }}" defer></script>
</body>
</html>
