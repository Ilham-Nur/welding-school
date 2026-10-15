<?php

use App\Http\Controllers\Admin\PracticeController;
use Illuminate\Support\Facades\Route;

Route::prefix('batch-operations/{batch}/sessions/{session}/practice')->name('operations.practice.')->middleware('permission:operations.view')->group(function () {
    Route::get('/', [PracticeController::class, 'show'])->name('show');
    Route::put('/', [PracticeController::class, 'configure'])->name('configure');
    Route::put('/submissions/{record}', [PracticeController::class, 'review'])->name('review');
    Route::get('/submissions/{record}/photo', [PracticeController::class, 'photo'])->name('photo');
});
