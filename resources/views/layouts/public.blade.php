@php
    $brand = config('branding');
@endphp
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="description" content="@yield('description', $brand['service'].' '.$brand['name'])">
        <meta property="og:type" content="website">
        <meta property="og:title" content="@yield('title', $brand['name'])">
        <meta property="og:description" content="@yield('description', $brand['service'].' '.$brand['name'])">
        <meta property="og:image" content="{{ url('alpha-academy-directory-og.png') }}">
        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('title', $brand['name'])">
        <meta name="twitter:description" content="@yield('description', $brand['service'].' '.$brand['name'])">
        <meta name="twitter:image" content="{{ url('alpha-academy-directory-og.png') }}">
        <title>@yield('title', $brand['name'])</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/style.css') }}?v={{ filemtime(public_path('templates/welding-school/style.css')) }}">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/components.css') }}?v={{ filemtime(public_path('templates/welding-school/components.css')) }}">
        @stack('styles')
    </head>
    <body class="@yield('body-class')">
        <a class="skip-link" href="#app">Lewati ke konten utama</a>

        <header class="site-header">
            <a class="brand" href="{{ route('home') }}" data-action="go-home" aria-label="{{ $brand['name'] }}">
                <span class="brand__mark" aria-hidden="true"><img src="{{ asset($brand['logo']) }}" alt=""></span>
                <span class="brand__copy">
                    <strong>{{ $brand['name'] }}</strong>
                    <small>{{ $brand['service'] }}</small>
                </span>
            </a>

            <nav class="public-nav" id="public-navigation" aria-label="Navigasi utama">
                <a class="{{ request()->routeIs('home') ? 'is-active' : '' }}" href="{{ route('home') }}#home" data-action="go-home" data-public-route="home">Beranda</a>
                <a href="{{ route('home') }}#about" data-action="go-public-page" data-target="about" data-public-route="about">Tentang</a>
                <a href="{{ route('home') }}#programs" data-action="go-programs" data-public-route="programs">Program</a>
                <a href="{{ route('home') }}#news" data-action="go-public-page" data-target="news" data-public-route="news">Aktivitas</a>
                <a href="{{ route('home') }}#welders" data-action="go-public-page" data-target="welders" data-public-route="welders">Daftar Welder</a>
                <a href="{{ route('home') }}#certificate" data-action="go-public-page" data-target="certificate" data-public-route="certificate">Verifikasi</a>
                <a class="public-nav__account" href="{{ route('home') }}#{{ auth()->check() ? 'member-programs' : 'account' }}" data-action="go-account">{{ auth()->check() ? 'Buka Dashboard' : 'Login' }}</a>
            </nav>

            <a style="text-decoration: none;" class="login-button" href="{{ route('home') }}#{{ auth()->check() ? 'member-programs' : 'account' }}" data-action="go-account">
                {{ auth()->check() ? 'Dashboard' : 'Login' }}
            </a>

            <button
                class="mobile-menu-button"
                @if(request()->routeIs('home')) data-action="toggle-menu" @else data-ui-menu @endif
                type="button"
                aria-label="Buka menu"
                aria-controls="public-navigation"
                aria-expanded="false"
            >☰</button>
        </header>

        @yield('content')

        <footer class="site-footer">
            <div class="site-footer__main page-shell">
                <div>
                    <a class="brand" href="{{ route('home') }}" data-action="go-home">
                        <span class="brand__mark" aria-hidden="true"><img src="{{ asset($brand['logo']) }}" alt=""></span>
                        <span class="brand__copy">
                            <strong>{{ $brand['name'] }}</strong>
                            <small>{{ $brand['service'] }}</small>
                        </span>
                    </a>
                    <p>Pelatihan, sertifikasi, dan direktori talenta welder profesional dari PT. Alpha Teknik Pratama.</p>
                </div>
                <div>
                    <strong>Jelajahi</strong>
                    <a href="{{ route('home') }}#about" data-action="go-public-page" data-target="about">Tentang Kami</a>
                    <a href="{{ route('home') }}#programs" data-action="go-programs">Program Pelatihan</a>
                    <a href="{{ route('home') }}#news" data-action="go-public-page" data-target="news">Aktivitas</a>
                </div>
                <div>
                    <strong>Terhubung</strong>
                    <a href="{{ route('home') }}#welders" data-action="go-public-page" data-target="welders">Daftar Welder &amp; Alumni</a>
                    <a href="{{ route('home') }}#recruiter-account" data-action="go-public-page" data-target="recruiter-account">Login Recruiter</a>
                    <a href="{{ route('home') }}#certificate" data-action="go-public-page" data-target="certificate">Verifikasi Sertifikat</a>
                </div>
                <div>
                    <strong>Hubungi Kami</strong>
                    <span>Komplek PT. Komoko Batam Centre Blok A No. 7, Batam</span>
                    <span>info@alphaacademy.id</span>
                    <span>+62 254 123 456</span>
                </div>
            </div>
            <div class="site-footer__bottom">© {{ date('Y') }} {{ $brand['name'] }}. All rights reserved.</div>
        </footer>

        @stack('scripts')
    </body>
</html>
