<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetDashboardController;
use App\Http\Controllers\Admin\AssetLabelController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TrainingApplicationController;
use App\Http\Controllers\Admin\TrainingBatchController;
use App\Http\Controllers\Admin\TrainingProgramController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'role:super-admin|admin', 'permission:admin.access'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])
            ->middleware('permission:users.view')
            ->name('users.index');
        Route::post('/users', [UserController::class, 'store'])
            ->middleware('permission:users.manage')
            ->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])
            ->middleware('permission:users.manage')
            ->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])
            ->middleware('permission:users.manage')
            ->name('users.destroy');

        Route::get('/roles', [RoleController::class, 'index'])
            ->middleware('permission:roles.view')
            ->name('roles.index');
        Route::post('/roles', [RoleController::class, 'store'])
            ->middleware('permission:roles.manage')
            ->name('roles.store');
        Route::put('/roles/{role}', [RoleController::class, 'update'])
            ->middleware('permission:roles.manage')
            ->name('roles.update');
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('permission:roles.manage')
            ->name('roles.destroy');

        Route::get('/applications', [TrainingApplicationController::class, 'index'])
            ->middleware('permission:applications.view')
            ->name('applications.index');
        Route::get('/applications/{application}', [TrainingApplicationController::class, 'show'])
            ->middleware('permission:applications.view')
            ->name('applications.show');
        Route::get('/applications/{application}/documents/{document}/preview', [TrainingApplicationController::class, 'previewDocument'])
            ->middleware('permission:applications.view')
            ->name('applications.documents.preview');
        Route::get('/applications/{application}/documents/{document}/download', [TrainingApplicationController::class, 'downloadDocument'])
            ->middleware('permission:applications.view')
            ->name('applications.documents.download');
        Route::patch('/applications/{application}/review', [TrainingApplicationController::class, 'review'])
            ->middleware('permission:applications.approve')
            ->name('applications.review');

        Route::resource('programs', TrainingProgramController::class)
            ->except(['show'])
            ->middlewareFor(['index'], 'permission:programs.view')
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:programs.manage');

        Route::resource('batches', TrainingBatchController::class)
            ->except(['show'])
            ->middlewareFor(['index'], 'permission:batches.view')
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:batches.manage');

        Route::resource('activities', ActivityController::class)
            ->except(['show'])
            ->middlewareFor(['index'], 'permission:activities.view')
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:activities.manage');

        Route::get('/assets/labels', AssetLabelController::class)
            ->middleware('permission:assets.view')
            ->name('assets.labels');

        Route::get('/assets/export', [AssetController::class, 'export'])
            ->middleware('permission:assets.view')
            ->name('assets.export');

        Route::get('/assets/dashboard', AssetDashboardController::class)
            ->middleware('permission:assets.view')
            ->name('assets.dashboard');

        Route::resource('assets', AssetController::class)
            ->except(['show'])
            ->middlewareFor(['index'], 'permission:assets.view')
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:assets.manage');
    });
