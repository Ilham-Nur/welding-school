@extends('layouts.public')

@section('title', config('branding.name').' Welding School · Kompeten, Tersertifikasi, Siap Kerja')
@section('description', 'Company profile dan platform pelatihan welding profesional PT. Alpha Teknik Pratama.')

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
                'login' => route('login.store'),
                'register' => route('register.store'),
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
            'verification' => [
                'pending' => config('auth.email_verification_required', true)
                    && session()->has('pending_verification_user_id'),
                'email' => session('pending_verification_email'),
            ],
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
