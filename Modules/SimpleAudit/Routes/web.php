<?php

use Illuminate\Support\Facades\Route;
use Modules\SimpleAudit\Http\Controllers\AssetController;
use Modules\SimpleAudit\Http\Controllers\ContextController;
use Modules\SimpleAudit\Http\Controllers\PurchaseAuditController;
use Modules\SimpleAudit\Http\Controllers\SharedReportController;
use Modules\SimpleAudit\Http\Middleware\EnsureCentralSimpleAuditAccess;

$publicPrefix = trim((string) config('simpleaudit.public_route_prefix', 'simple-audit'), '/');
$centralPrefix = trim((string) config('simpleaudit.route_prefix', 'superadmin/simple-audit'), '/');
$centralMiddleware = array_values(array_unique(array_merge(
    (array) config('simpleaudit.middleware', ['web', 'auth']),
    [EnsureCentralSimpleAuditAccess::class]
)));

/*
 * Module-owned assets and signed report shares stay on the short public prefix.
 * They do not decide which application sidebar/layout is used.
 */
Route::middleware('web')->prefix($publicPrefix)->group(function () {
    Route::get('assets/{type}/{file}', [AssetController::class, 'show'])
        ->where('type', 'css|js')
        ->where('file', '[A-Za-z0-9._-]+')
        ->name('simpleaudit.asset');

    Route::get('share/{tenant}/{token}', [SharedReportController::class, 'show'])
        ->where('tenant', '[A-Za-z0-9._-]+')
        ->where('token', '[A-Za-z0-9_-]+')
        ->name('simpleaudit.share.public');
});

/*
 * Central-only application routes MUST live below /superadmin.
 * layouts/app.blade.php identifies the genuine Central Super Admin area by
 * request segment(1) === 'superadmin'. Keeping the protected SAU pages here
 * therefore guarantees that the Central Super Admin sidebar remains loaded.
 */
Route::middleware($centralMiddleware)
    ->prefix($centralPrefix)
    ->group(function () {
        Route::get('/', [PurchaseAuditController::class, 'index'])->name('simpleaudit.home');
        Route::get('purchase-audit', [PurchaseAuditController::class, 'index'])->name('simpleaudit.purchase-audit');
        Route::get('purchase-audit/data', [PurchaseAuditController::class, 'data'])->name('simpleaudit.purchase-audit.data');
        Route::get('purchase-audit/details', [PurchaseAuditController::class, 'details'])->name('simpleaudit.purchase-audit.details');
        Route::get('purchase-audit/export/{format}', [PurchaseAuditController::class, 'export'])
            ->where('format', 'csv|xls|pdf')
            ->name('simpleaudit.purchase-audit.export');
        Route::get('purchase-audit/print', [PurchaseAuditController::class, 'printView'])->name('simpleaudit.purchase-audit.print');
        Route::post('purchase-audit/share', [PurchaseAuditController::class, 'share'])->name('simpleaudit.purchase-audit.share');
        Route::post('purchase-audit/email', [PurchaseAuditController::class, 'email'])->name('simpleaudit.purchase-audit.email');

        Route::get('context/tenants', [ContextController::class, 'tenants'])->name('simpleaudit.context.tenants');
        Route::get('context/businesses', [ContextController::class, 'businesses'])->name('simpleaudit.context.businesses');
        Route::get('context/locations', [ContextController::class, 'locations'])->name('simpleaudit.context.locations');
        Route::get('context/stores', [ContextController::class, 'stores'])->name('simpleaudit.context.stores');
    });

/*
 * Backward compatibility for links from v1.0.0-v1.0.11. The same central-only
 * middleware runs first; normal business users still receive HTTP 403.
 */
Route::middleware($centralMiddleware)->prefix($publicPrefix)->group(function () {
    Route::get('/', function () {
        return redirect()->route('simpleaudit.purchase-audit');
    })->name('simpleaudit.legacy.home');

    Route::get('purchase-audit', function () {
        return redirect()->route('simpleaudit.purchase-audit');
    })->name('simpleaudit.legacy.purchase-audit');
});
