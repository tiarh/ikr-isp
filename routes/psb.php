<?php

use App\Http\Controllers\Psb\AssignmentController;
use App\Http\Controllers\Psb\CoverageController;
use App\Http\Controllers\Psb\DashboardController;
use App\Http\Controllers\Psb\PipelineController;
use App\Http\Controllers\Psb\ProvisioningController;
use App\Http\Controllers\Psb\PsbDocumentController;
use App\Http\Controllers\Psb\PsbOrderController;
use App\Http\Controllers\Psb\ReportController;
use App\Http\Controllers\Psb\SyncController;
use Illuminate\Support\Facades\Route;

Route::prefix('psb')->name('psb.')->middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', DashboardController::class)->name('dashboard');

    // PSB Orders CRUD
    Route::resource('orders', PsbOrderController::class);
    // Manual status transition (drag-drop pipeline)
    Route::patch('/orders/{psbOrder}/status', [PsbOrderController::class, 'transitionStatus'])->name('orders.transition');

    // Pipeline (Kanban)
    Route::get('/pipeline', PipelineController::class)->name('pipeline');

    // Coverage
    Route::get('/coverage', [CoverageController::class, 'index'])->name('coverage.index');
    Route::post('/coverage/{psbOrder}/approve', [CoverageController::class, 'approve'])->name('coverage.approve');
    Route::post('/coverage/{psbOrder}/reject', [CoverageController::class, 'reject'])->name('coverage.reject');

    // Assignment
    Route::get('/assignment', [AssignmentController::class, 'index'])->name('assignment.index');
    Route::post('/assignment/{psbOrder}/assign', [AssignmentController::class, 'assign'])->name('assignment.assign');

    // Provisioning
    Route::get('/provisioning', [ProvisioningController::class, 'index'])->name('provisioning.index');
    Route::post('/provisioning/{psbOrder}/select-olt', [ProvisioningController::class, 'selectOlt'])->name('provisioning.selectOlt');
    Route::post('/provisioning/{psbOrder}/provision', [ProvisioningController::class, 'provision'])->name('provisioning.provision');

    // Documents
    Route::get('/orders/{psbOrder}/documents', [PsbDocumentController::class, 'index'])->name('documents.index');
    Route::post('/orders/{psbOrder}/photo/{type}', [PsbDocumentController::class, 'uploadPhoto'])->name('documents.photo');
    Route::post('/orders/{psbOrder}/photo-api/{type}', [PsbDocumentController::class, 'uploadPhotoApi'])->name('documents.photoApi');
    Route::post('/orders/{psbOrder}/measurements', [PsbDocumentController::class, 'updateMeasurements'])->name('documents.measurements');
    Route::post('/orders/{psbOrder}/bai', [PsbDocumentController::class, 'uploadBai'])->name('documents.bai');
    Route::post('/checklist/{checklist}/toggle', [PsbDocumentController::class, 'toggleHiOSChecklist'])->name('checklist.toggle');

    // Sync
    Route::get('/orders/{psbOrder}/sync', [SyncController::class, 'index'])->name('sync.index');
    Route::get('/orders/{psbOrder}/sync/preview', [SyncController::class, 'preview'])->name('sync.preview');
    Route::post('/orders/{psbOrder}/sync', [SyncController::class, 'sync'])->name('sync.sync');

    // Reports
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'exportCsv'])->name('reports.export');
});
