@php($brand = config('branding'))
<!doctype html>
<html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="robots" content="noindex, nofollow, noarchive">
        <meta name="description" content="Portal internal {{ $brand['name'] }}">
        <title>Login Portal Internal · {{ $brand['name'] }}</title>
        <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/style.css') }}?v={{ filemtime(public_path('templates/welding-school/style.css')) }}">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/components.css') }}?v={{ filemtime(public_path('templates/welding-school/components.css')) }}">
        <link rel="stylesheet" href="{{ asset('templates/welding-school/admin.css') }}?v={{ filemtime(public_path('templates/welding-school/admin.css')) }}">
    </head>
    <body class="internal-login-page">
        <main class="internal-login-shell">
            <section class="internal-login-visual" aria-label="Portal tim internal Alpha Welding Academy">
                <a class="internal-login-brand" href="{{ route('home') }}" aria-label="Kembali ke situs {{ $brand['name'] }}">
                    <span class="brand__mark" aria-hidden="true"><img src="{{ asset($brand['logo']) }}" alt=""></span>
                    <span>
                        <strong>{{ $brand['name'] }}</strong>
                        <small>{{ $brand['service'] }}</small>
                    </span>
                </a>

                <div class="internal-login-visual__content">
                    <span class="internal-login-eyebrow">PORTAL OPERASIONAL</span>
                    <h1>Satu akses internal, sesuai tanggung jawab setiap tim.</h1>
                    <p>Instruktur, administrasi, storeman, keuangan, dan tim operasional masuk dari portal yang sama. Menu akan disesuaikan otomatis berdasarkan role dan permission akun.</p>
                    <div class="internal-login-points" aria-label="Keunggulan portal internal">
                        <span><b>01</b> Akses berbasis role</span>
                        <span><b>02</b> Data operasional terpusat</span>
                        <span><b>03</b> Aktivitas tercatat</span>
                    </div>
                </div>
            </section>

            <section class="internal-login-panel">
                <div class="internal-login-card">
                    <a class="internal-login-back" href="{{ route('home') }}">← Kembali ke situs utama</a>
                    <span class="internal-login-eyebrow internal-login-eyebrow--dark">AKSES KHUSUS TIM INTERNAL</span>
                    <h2>Selamat datang kembali</h2>
                    <p>Masukkan email atau username akun staf Anda untuk melanjutkan.</p>

                    @if (session('auth_status'))
                        <button type="button" hidden data-flash-toast data-toast="{{ session('auth_status') }}" data-toast-type="success"></button>
                    @endif

                    @if ($errors->any())
                        <button type="button" hidden data-flash-toast data-toast="{{ implode(' • ', $errors->all()) }}" data-toast-type="danger"></button>
                    @endif

                    <form class="internal-login-form" method="POST" action="{{ route('admin.login.store') }}">
                        @csrf
                        <label>
                            <span>Email atau username</span>
                            <input
                                name="login"
                                type="text"
                                value="{{ old('login') }}"
                                placeholder="nama@alphaacademy.id"
                                autocomplete="username"
                                autofocus
                                required
                            >
                        </label>
                        <label>
                            <span>Password</span>
                            <input
                                name="password"
                                type="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >
                        </label>
                        <div class="internal-login-options">
                            <label><input name="remember" type="checkbox" value="1" @checked(old('remember'))> Ingat saya</label>
                            <a href="{{ route('home') }}#forgot-password">Lupa password?</a>
                        </div>
                        <button type="submit">Masuk ke Portal Internal <span>→</span></button>
                    </form>

                    <div class="internal-login-divider"><span>Portal berbeda</span></div>
                    <p class="internal-login-participant">Anda peserta pelatihan? <a href="{{ route('login') }}">Masuk ke Portal Peserta</a></p>
                    <small class="internal-login-security">Akses hanya untuk akun internal yang telah diberi permission oleh administrator.</small>
                </div>
            </section>
        </main>
        <x-ui.toast-stack />
        <script src="{{ asset('templates/welding-school/components.js') }}?v={{ filemtime(public_path('templates/welding-school/components.js')) }}" defer></script>
    </body>
</html>
