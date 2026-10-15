<?php

use App\Http\Controllers\Admin\BatchMaterialController;
use App\Http\Controllers\Admin\ProgramMaterialController;
use App\Http\Controllers\Admin\TrainingMaterialController;
use Illuminate\Support\Facades\Route;

Route::prefix('quality-documents/training')->name('training-materials.')->group(function () {
    Route::get('/', [TrainingMaterialController::class, 'index'])->name('index');
    Route::get('/create', [TrainingMaterialController::class, 'create'])->name('create');
    Route::post('/', [TrainingMaterialController::class, 'store'])->name('store');
    Route::get('/programs', [ProgramMaterialController::class, 'index'])->name('programs');
    Route::get('/programs/{program}', [ProgramMaterialController::class, 'show'])->name('program');
    Route::post('/programs/{program}', [ProgramMaterialController::class, 'save'])->name('program.add');
    Route::get('/programs/{program}/{item}/edit', [ProgramMaterialController::class, 'edit'])->name('program.edit');
    Route::put('/programs/{program}/{item}', [ProgramMaterialController::class, 'save'])->name('program.update');
    Route::delete('/programs/{program}/{item}', [ProgramMaterialController::class, 'destroy'])->name('program.remove');
    Route::get('/revisions/{revision}', [TrainingMaterialController::class, 'show'])->name('show');
    Route::get('/revisions/{revision}/edit', [TrainingMaterialController::class, 'edit'])->name('edit');
    Route::put('/revisions/{revision}', [TrainingMaterialController::class, 'update'])->name('update');
    Route::post('/revisions/{revision}/revise', [TrainingMaterialController::class, 'revise'])->name('revise');
    Route::post('/revisions/{revision}/publish', [TrainingMaterialController::class, 'publish'])->name('publish');
    Route::post('/revisions/{revision}/archive', [TrainingMaterialController::class, 'archive'])->name('archive');
    Route::post('/revisions/{revision}/files', [TrainingMaterialController::class, 'upload'])->name('upload');
    Route::delete('/revisions/{revision}/files/{file}', [TrainingMaterialController::class, 'removeFile'])->name('remove-file');
    Route::get('/revisions/{revision}/files/{file}', [TrainingMaterialController::class, 'file'])->name('file');
});
Route::prefix('batch-operations/{batch}/materials')->name('operations.materials.')->group(function () {
    Route::post('/import', [BatchMaterialController::class, 'import'])->name('import');
    Route::post('/', [BatchMaterialController::class, 'store'])->name('store');
    Route::get('/{item}', [BatchMaterialController::class, 'show'])->name('show');
    Route::get('/{item}/edit', [BatchMaterialController::class, 'edit'])->name('edit');
    Route::put('/{item}', [BatchMaterialController::class, 'update'])->name('update');
    Route::delete('/{item}', [BatchMaterialController::class, 'destroy'])->name('destroy');
    Route::get('/{item}/files/{file}', [BatchMaterialController::class, 'file'])->name('file');
});
