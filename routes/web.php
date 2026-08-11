<?php

use App\Http\Controllers\ActivityViewController;
use App\Http\Controllers\AssetInspectionController;
use App\Http\Controllers\AssetInspectionScanController;
use App\Http\Controllers\AssetVerificationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Participant\InvoiceController as ParticipantInvoiceController;
use App\Http\Controllers\Participant\ParticipantProfileController;
use App\Http\Controllers\Participant\PaymentController as ParticipantPaymentController;
use App\Http\Controllers\Participant\TrainingApplicationController as ParticipantTrainingApplicationController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\Webhooks\MidtransWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/reset-password/{token}', HomeController::class)
    ->middleware(['guest', 'cache.headers:no_store'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('password.reset');

Route::post('/activities/{activity}/view', ActivityViewController::class)
    ->middleware('throttle:60,1')
    ->name('activities.view');

Route::get('/assets/verify/{asset:public_id}', AssetVerificationController::class)
    ->middleware('throttle:60,1')
    ->name('assets.verify');

Route::get('/assets/verify/{asset:public_id}/calibration-certificate', [AssetVerificationController::class, 'certificate'])
    ->middleware('throttle:30,1')
    ->name('assets.calibration-certificate');

Route::middleware(['auth', 'verified.when_required', 'permission:assets.inspect'])->group(function (): void {
    Route::get('/assets/resolve', AssetInspectionScanController::class)
        ->middleware('throttle:60,1')
        ->name('assets.inspections.resolve');
    Route::get('/assets/inspect/{asset:public_id}', [AssetInspectionController::class, 'create'])
        ->name('assets.inspections.create');
    Route::post('/assets/inspect/{asset:public_id}', [AssetInspectionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('assets.inspections.store');
});

Route::post('/payments/midtrans/webhook', MidtransWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('payments.midtrans.webhook');

Route::get('/payments/midtrans/finish', [ParticipantPaymentController::class, 'finish'])
    ->name('payments.midtrans.finish');

Route::get('/login', fn () => redirect(route('home').'#account'))
    ->middleware('guest')
    ->name('login');

Route::get('/register', fn () => redirect(route('home').'#account'))
    ->middleware('guest')
    ->name('register');

Route::middleware('guest')->group(function (): void {
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');

    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.update');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('login.store');

    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('register.store');

    Route::post('/email/verify', [EmailVerificationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('verification.code.verify');

    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('verification.code.resend');

    Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])
        ->middleware('throttle:10,1')
        ->name('auth.google.redirect');

    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->middleware('throttle:10,1')
        ->name('auth.google.callback');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'verified.when_required'])->group(function (): void {
    Route::get('/profile', [ParticipantProfileController::class, 'show'])
        ->name('profile.show');
    Route::post('/profile', [ParticipantProfileController::class, 'update'])
        ->name('profile.update');
    Route::get('/applications/current', [ParticipantTrainingApplicationController::class, 'current'])
        ->name('applications.current');
    Route::post('/applications', [ParticipantTrainingApplicationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('applications.store');
    Route::post('/invoices', [ParticipantInvoiceController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('invoices.store');
    Route::post('/payments', [ParticipantPaymentController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('payments.store');
});

Route::get('/template/components', TemplateController::class)
    ->name('template.components');

require __DIR__.'/admin.php';
