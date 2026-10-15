<?php

use App\Http\Controllers\Admin\SessionExecutionController;
use Illuminate\Support\Facades\Route;

Route::prefix('batch-operations/{batch}/sessions/{session}/execution')->name('operations.execution.')->middleware('permission:operations.view')->group(function () {
    Route::get('/', [SessionExecutionController::class, 'show'])->name('show');
    Route::put('/area', [SessionExecutionController::class, 'area'])->name('area');
    Route::post('/area/resolve', [SessionExecutionController::class, 'resolveArea'])->middleware('throttle:10,1')->name('area.resolve');
    Route::post('/start', [SessionExecutionController::class, 'start'])->name('start');
    Route::post('/complete', [SessionExecutionController::class, 'complete'])->name('complete');
    Route::post('/finalize', [SessionExecutionController::class, 'finalize'])->name('finalize');
    Route::put('/attendance/{record}', [SessionExecutionController::class, 'review'])->name('review');
    Route::get('/attendance/{record}/photo/{kind}', [SessionExecutionController::class, 'photo'])->where('kind', 'in|out')->name('photo');
});
