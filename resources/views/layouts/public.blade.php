@php
    $brand = config('branding');
    $siteName = 'Alpha Welding Academy';
    $defaultTitle = $siteName.' | '.$brand['tagline'];
    $defaultDescription = 'Pelatihan welding profesional dan sertifikasi welder dari '.$brand['company'].'.';
    $seoTitle = trim($__env->yieldContent('title', $defaultTitle));
    $seoDescription = trim($__env->yieldContent('description', $defaultDescription));
    $canonicalUrl = trim($__env->yieldContent('canonical', request()->url()));
    $robots = trim($__env->yieldContent('robots', 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1'));
    $socialImage = trim($__env->yieldContent('social-image', url('alpha-academy-og.png')));
    $socialImageAlt = trim($__env->yieldContent('social-image-alt', $siteName.', '.$brand['tagline']));
@endphp
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ $seoTitle }}</title>
        <meta name="description" content="{{ $seoDescription }}">
        <meta name="robots" content="{{ $robots }}">
        <meta name="author" content="{{ $brand['company'] }}">
        <meta name="theme-color" content="#071e33">
        @stack('head')

        <link rel="canonical" href="{{ $canonicalUrl }}">
        <link rel="alternate" hreflang="id-ID" href="{{ $canonicalUrl }}">
        <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:locale" content="id_ID">
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:title" content="{{ $seoTitle }}">
        <meta property="og:description" content="{{ $seoDescription }}">
        <meta property="og:image" content="{{ $socialImage }}">
        <meta property="og:image:secure_url" content="{{ $socialImage }}">
        <meta property="og:image:type" content="image/png">
        <meta property="og:image:width" content="1730">
        <meta property="og:image:height" content="909">
        <meta property="og:image:alt" content="{{ $socialImageAlt }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $seoTitle }}">
        <meta name="twitter:description" content="{{ $seoDescription }}">
        <meta name="twitter:image" content="{{ $socialImage }}">
        <meta name="twitter:image:alt" content="{{ $socialImageAlt }}">

        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/style.css') }}?v={{ filemtime(public_path('templates/welding-school/style.css')) }}">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/components.css') }}?v={{ filemtime(public_path('templates/welding-school/components.css')) }}">
        @stack('structured-data')
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
                <a href="{{ route('home') }}#welders" data-action="go-public-page" data-target="welders" data-public-route="welders">Daftar Alumni</a>
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
                    <p>Pelatihan, sertifikasi, dan direktori talenta welding profesional dari PT. Alpha Teknik Pratama.</p>
                </div>
                <div>
                    <strong>Jelajahi</strong>
                    <a href="{{ route('home') }}#about" data-action="go-public-page" data-target="about">Tentang Kami</a>
                    <a href="{{ route('home') }}#programs" data-action="go-programs">Program Pelatihan</a>
                    <a href="{{ route('home') }}#news" data-action="go-public-page" data-target="news">Aktivitas</a>
                </div>
                <div>
                    <strong>Terhubung</strong>
                    <a href="{{ route('home') }}#welders" data-action="go-public-page" data-target="welders">Daftar Alumni</a>
                    <a href="{{ route('home') }}#recruiter-account" data-action="go-public-page" data-target="recruiter-account">Login Recruiter</a>
                    <a href="{{ route('admin.login') }}">Login Internal</a>
                    <a href="{{ route('home') }}#certificate" data-action="go-public-page" data-target="certificate">Verifikasi Sertifikat</a>
                </div>
                <div>
                    <strong>Hubungi Kami</strong>
                    <span>Komplek PT. Komoko Batam Centre Blok A No. 7, Batam</span>
                    <span>info@alphaacademy.id</span>
                    <span>+62 895-6035-02918</span>
                </div>
            </div>
            <div class="site-footer__bottom">© {{ date('Y') }} {{ $brand['name'] }}. All rights reserved.</div>
        </footer>

        <a
            class="whatsapp-float"
            href="https://wa.me/62895603502918?text=Halo%20Alpha%20Academy%2C%20saya%20ingin%20bertanya%20tentang%20program%20pelatihan%20welding."
            target="_blank"
            rel="noopener noreferrer"
            aria-label="Hubungi Alpha Academy melalui WhatsApp di 0895-6035-02918"
            hidden
        >
            <span class="whatsapp-float__icon" aria-hidden="true">&#9742;</span>
            <span class="whatsapp-float__copy"><small>Butuh informasi?</small><strong>Chat WhatsApp</strong></span>
        </a>

        @stack('scripts')
    </body>
</html>
