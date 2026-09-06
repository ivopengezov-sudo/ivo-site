<?php

use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Client portal
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/files', [ProjectController::class, 'storeFiles'])->name('projects.files.store');

    Route::get('/files/{file}/download', [ProjectFileController::class, 'download'])->name('files.download');
});

/*
|--------------------------------------------------------------------------
| Staff dashboard
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/projects', [AdminProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [AdminProjectController::class, 'show'])->name('projects.show');
    Route::patch('/projects/{project}/status', [AdminProjectController::class, 'updateStatus'])->name('projects.status');
    Route::post('/projects/{project}/deliverables', [AdminProjectController::class, 'storeDeliverables'])->name('projects.deliverables');
});
