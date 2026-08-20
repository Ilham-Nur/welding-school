<?php

use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\AssetController;
use App\Http\Controllers\Admin\AssetDashboardController;
use App\Http\Controllers\Admin\AssetExternalLoanController;
use App\Http\Controllers\Admin\AssetLabelController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmployeeController;
use App\Http\Controllers\Admin\EmployeePositionController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\StorageDashboardController;
use App\Http\Controllers\Admin\StorageItemController;
use App\Http\Controllers\Admin\StorageReportController;
use App\Http\Controllers\Admin\StorageStockOpnameController;
use App\Http\Controllers\Admin\StorageTransactionController;
use App\Http\Controllers\Admin\TrainingApplicationController;
use App\Http\Controllers\Admin\TrainingBatchController;
use App\Http\Controllers\Admin\TrainingProgramController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['guest', 'cache.headers:no_store'])->group(function (): void {
    Route::view('/admin/login', 'auth.internal-login')
        ->name('admin.login');
    Route::post('/admin/login', [AuthenticatedSessionController::class, 'storeInternal'])
        ->middleware('throttle:6,1')
        ->name('admin.login.store');
});

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'verified', 'permission:admin.access'])
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

        Route::get('/employees/{employee}/documents/{document}/preview', [EmployeeController::class, 'previewDocument'])
            ->middleware('permission:employees.view')
            ->name('employees.documents.preview');
        Route::get('/employees/{employee}/documents/{document}/download', [EmployeeController::class, 'downloadDocument'])
            ->middleware('permission:employees.view')
            ->name('employees.documents.download');
        Route::get('/employees/{employee}/educations/{education}/preview', [EmployeeController::class, 'previewEducation'])
            ->middleware('permission:employees.view')
            ->name('employees.educations.preview');
        Route::get('/employees/{employee}/educations/{education}/download', [EmployeeController::class, 'downloadEducation'])
            ->middleware('permission:employees.view')
            ->name('employees.educations.download');
        Route::get('/employees/{employee}/last-education/preview', [EmployeeController::class, 'previewLastEducation'])
            ->middleware('permission:employees.view')
            ->name('employees.last-education.preview');
        Route::get('/employees/{employee}/last-education/download', [EmployeeController::class, 'downloadLastEducation'])
            ->middleware('permission:employees.view')
            ->name('employees.last-education.download');

        Route::post('/employees/{employee}/educations', [EmployeeController::class, 'storeEducation'])
            ->middleware('permission:employees.manage')
            ->name('employees.educations.store');
        Route::delete('/employees/{employee}/educations/{education}', [EmployeeController::class, 'destroyEducation'])
            ->middleware('permission:employees.manage')
            ->name('employees.educations.destroy');

        Route::post('/employees/{employee}/documents', [EmployeeController::class, 'storeDocument'])
            ->middleware('permission:employees.manage')
            ->name('employees.documents.store');
        Route::delete('/employees/{employee}/documents/{document}', [EmployeeController::class, 'destroyDocument'])
            ->middleware('permission:employees.manage')
            ->name('employees.documents.destroy');

        Route::resource('employees', EmployeeController::class)
            ->middlewareFor(['index', 'show'], 'permission:employees.view')
            ->middlewareFor(['create', 'store', 'edit', 'update', 'destroy'], 'permission:employees.manage');

        Route::resource('employee-positions', EmployeePositionController::class)
            ->except(['create', 'show', 'edit'])
            ->middlewareFor(['index'], 'permission:employees.view')
            ->middlewareFor(['store', 'update', 'destroy'], 'permission:employees.manage');

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

        Route::post('/locations/{location}/children', [LocationController::class, 'storeChildren'])
            ->middleware('permission:locations.manage')
            ->name('locations.children.store');
        Route::resource('locations', LocationController::class)
            ->except(['show', 'destroy'])
            ->middlewareFor(['index'], 'permission:locations.view')
            ->middlewareFor(['create', 'store', 'edit', 'update'], 'permission:locations.manage');

        Route::get('/storage/dashboard', StorageDashboardController::class)
            ->middleware('permission:storage.view')->name('storage.dashboard');
        Route::resource('storage-items', StorageItemController::class)
            ->except(['destroy'])
            ->middlewareFor(['index', 'show'], 'permission:storage.view')
            ->middlewareFor(['create', 'store', 'edit', 'update'], 'permission:storage.items.manage');

        Route::get('/storage/receipts', [StorageTransactionController::class, 'receipts'])
            ->middleware('permission:storage.view')->name('storage.receipts.index');
        Route::get('/storage/receipts/create', [StorageTransactionController::class, 'createReceipt'])
            ->middleware('permission:storage.transactions.manage')->name('storage.receipts.create');
        Route::post('/storage/receipts', [StorageTransactionController::class, 'storeReceipt'])
            ->middleware('permission:storage.transactions.manage')->name('storage.receipts.store');
        Route::get('/storage/issues', [StorageTransactionController::class, 'issues'])
            ->middleware('permission:storage.view')->name('storage.issues.index');
        Route::get('/storage/issues/create', [StorageTransactionController::class, 'createIssue'])
            ->middleware('permission:storage.transactions.manage')->name('storage.issues.create');
        Route::post('/storage/issues', [StorageTransactionController::class, 'storeIssue'])
            ->middleware('permission:storage.transactions.manage')->name('storage.issues.store');

        Route::get('/storage/loans', [AssetExternalLoanController::class, 'index'])
            ->middleware('permission:storage.view')->name('storage.loans.index');
        Route::get('/storage/loans/create', [AssetExternalLoanController::class, 'create'])
            ->middleware('permission:storage.loans.manage')->name('storage.loans.create');
        Route::post('/storage/loans', [AssetExternalLoanController::class, 'store'])
            ->middleware('permission:storage.loans.manage')->name('storage.loans.store');
        Route::patch('/storage/loans/{loan}/return', [AssetExternalLoanController::class, 'returnLoan'])
            ->middleware('permission:storage.loans.manage')->name('storage.loans.return');

        Route::get('/storage/opnames', [StorageStockOpnameController::class, 'index'])
            ->middleware('permission:storage.view')->name('storage.opnames.index');
        Route::get('/storage/opnames/create', [StorageStockOpnameController::class, 'create'])
            ->middleware('permission:storage.stocktakes.manage')->name('storage.opnames.create');
        Route::post('/storage/opnames', [StorageStockOpnameController::class, 'store'])
            ->middleware('permission:storage.stocktakes.manage')->name('storage.opnames.store');
        Route::get('/storage/opnames/{opname}', [StorageStockOpnameController::class, 'show'])
            ->middleware('permission:storage.view')->name('storage.opnames.show');
        Route::patch('/storage/opnames/{opname}/complete', [StorageStockOpnameController::class, 'complete'])
            ->middleware('permission:storage.stocktakes.manage')->name('storage.opnames.complete');
        Route::get('/storage/reports/export/excel', [StorageReportController::class, 'excel'])
            ->middleware('permission:storage.reports.view')->name('storage.reports.excel');
        Route::get('/storage/reports/export/pdf', [StorageReportController::class, 'pdf'])
            ->middleware('permission:storage.reports.view')->name('storage.reports.pdf');
        Route::get('/storage/reports', [StorageReportController::class, 'index'])
            ->middleware('permission:storage.reports.view')->name('storage.reports.index');

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
