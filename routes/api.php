<?php

use App\Http\Controllers\Api\EbillingSyncController;
use App\Http\Controllers\Api\FieldOpsController;
use App\Http\Controllers\Api\OltProvisioningController;
use App\Http\Controllers\Api\TeknisiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    // eBilling sync
    Route::post('/psb-orders/{psb_order}/sync', [EbillingSyncController::class, 'sync']);
    Route::get('/psb-orders/{psb_order}/sync-status', [EbillingSyncController::class, 'status']);

    // FieldOps
    Route::get('/odp-assets', [FieldOpsController::class, 'odpAssets']);
    Route::get('/odc-assets', [FieldOpsController::class, 'odcAssets']); // ODP-A
    Route::get('/olt-assets', [FieldOpsController::class, 'oltAssets']);

    // OLT provisioning
    Route::post('/olt/provision', [OltProvisioningController::class, 'provision']);

    // Teknisi list
    Route::get('/teknisi', [TeknisiController::class, 'index']);
});
