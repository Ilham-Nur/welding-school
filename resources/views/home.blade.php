@extends('layouts.public')

@section('title', 'Alpha Welding Academy Batam | Pelatihan Welder Bersertifikasi')
@section('description', 'Pelatihan welding dan sertifikasi welder profesional di Batam dari PT. Alpha Teknik Pratama. Pilih program kompetensi untuk meningkatkan kesiapan kerja.')
@section('canonical', route('home'))

@if ($passwordReset)
    @section('robots', 'noindex, nofollow, noarchive')
    @push('head')
        <meta name="referrer" content="no-referrer">
    @endpush
@endif
@section('social-image-alt', 'Alpha Welding Academy Batam, Kompeten, Tersertifikasi, Siap Kerja')

@push('structured-data')
    @php
        $homeUrl = route('home');
        $organizationId = $homeUrl.'#organization';
        $websiteId = $homeUrl.'#website';
        $webpageId = $homeUrl.'#webpage';
        $primaryImageId = $homeUrl.'#primaryimage';
        $seoDescription = 'Pelatihan welding dan sertifikasi welder profesional di Batam dari PT. Alpha Teknik Pratama. Pilih program kompetensi untuk meningkatkan kesiapan kerja.';
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'EducationalOrganization',
                    '@id' => $organizationId,
                    'name' => 'Alpha Welding Academy',
                    'alternateName' => 'Alpha Academy Welding School',
                    'legalName' => config('branding.company'),
                    'url' => $homeUrl,
                    'logo' => [
                        '@type' => 'ImageObject',
                        'url' => asset(config('branding.logo')),
                        'contentUrl' => asset(config('branding.logo')),
                        'caption' => 'Logo Alpha Welding Academy',
                    ],
                    'image' => ['@id' => $primaryImageId],
                    'description' => $seoDescription,
                    'slogan' => config('branding.tagline'),
                    'telephone' => '+62895603502918',
                    'email' => 'info@alphaacademy.id',
                    'address' => [
                        '@type' => 'PostalAddress',
                        'streetAddress' => 'Komplek PT. Komoko Batam Centre Blok A No. 7',
                        'addressLocality' => 'Batam',
                        'addressCountry' => 'ID',
                    ],
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $websiteId,
                    'url' => $homeUrl,
                    'name' => 'Alpha Welding Academy',
                    'alternateName' => 'Alpha Academy Welding School',
                    'publisher' => ['@id' => $organizationId],
                    'inLanguage' => 'id-ID',
                ],
                [
                    '@type' => 'ImageObject',
                    '@id' => $primaryImageId,
                    'url' => url('alpha-academy-og.png'),
                    'contentUrl' => url('alpha-academy-og.png'),
                    'width' => 1730,
                    'height' => 909,
                    'caption' => 'Alpha Welding Academy, Kompeten, Tersertifikasi, Siap Kerja',
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $webpageId,
                    'url' => $homeUrl,
                    'name' => 'Alpha Welding Academy Batam | Pelatihan Welder Bersertifikasi',
                    'description' => $seoDescription,
                    'isPartOf' => ['@id' => $websiteId],
                    'about' => ['@id' => $organizationId],
                    'primaryImageOfPage' => ['@id' => $primaryImageId],
                    'inLanguage' => 'id-ID',
                ],
            ],
        ];
    @endphp
    <script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
    <main id="app" tabindex="-1"></main>
    <div id="toast" class="toast" role="status" aria-live="polite" hidden></div>
@endsection

@push('scripts')
    <script>
        window.WeldingSchool = {{ Illuminate\Support\Js::from([
            'branding' => [
                'name' => config('branding.name'),
                'service' => config('branding.service'),
                'company' => config('branding.company'),
                'tagline' => config('branding.tagline'),
                'logo' => asset(config('branding.logo')),
            ],
            'auth' => [
                'authenticated' => auth()->check(),
                'user' => auth()->check() ? [
                    'name' => auth()->user()->name,
                    'username' => auth()->user()->username,
                    'email' => auth()->user()->email,
                    'role' => auth()->user()->primaryRoleName(),
                    'avatar' => auth()->user()->profileAvatarUrl(),
                    'is_admin' => auth()->user()->isAdmin(),
                    'profile' => auth()->user()->participantProfileData(),
                ] : null,
            ],
            'routes' => [
                'home' => route('home'),
                'login' => route('login.store'),
                'register' => route('register.store'),
                'forgotPassword' => route('password.email'),
                'resetPassword' => route('password.update'),
                'logout' => route('logout'),
                'google' => route('auth.google.redirect'),
                'verifyEmail' => route('verification.code.verify'),
                'resendVerification' => route('verification.code.resend'),
                'admin' => route('admin.dashboard'),
                'applicationCurrent' => route('applications.current'),
                'applicationStore' => route('applications.store'),
                'profileCurrent' => route('profile.show'),
                'profileStore' => route('profile.update'),
                'invoiceStore' => route('invoices.store'),
                'paymentStore' => route('payments.store'),
            ],
            'catalog' => [
                'programs' => $catalog,
            ],
            'activities' => $activities,
            'verification' => [
                'pending' => config('auth.email_verification_required', true)
                    && session()->has('pending_verification_user_id'),
                'email' => session('pending_verification_email'),
            ],
            'passwordReset' => $passwordReset,
            'googleConfigured' => filled(config('services.google.client_id'))
                && filled(config('services.google.client_secret')),
            'billing' => [
                'administrationFee' => config('billing.administration_fee'),
                'invoiceDueHours' => config('billing.invoice_due_hours'),
            ],
            'flash' => [
                'status' => session('auth_status'),
                'error' => session('auth_error'),
            ],
        ]) }};
    </script>
    <script src="{{ asset('templates/welding-school/app.js') }}?v={{ filemtime(public_path('templates/welding-school/app.js')) }}" defer></script>
@endpush
