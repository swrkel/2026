<?php

use Illuminate\Support\Facades\Route;
use Modules\PriceChangeNew\Http\Controllers\ApprovalController;
use Modules\PriceChangeNew\Http\Controllers\DashboardController;
use Modules\PriceChangeNew\Http\Controllers\PriceChangeController;
use Modules\PriceChangeNew\Http\Controllers\ProductLookupController;
use Modules\PriceChangeNew\Http\Controllers\ReportController;
use Modules\PriceChangeNew\Http\Controllers\SettingsController;
use Modules\PriceChangeNew\Http\Controllers\WorkflowController;
use Modules\PriceChangeNew\Http\Middleware\EnsurePriceChangeNewSchema;
use Modules\PriceChangeNew\Http\Middleware\InitializePriceChangeNewTenantContext;

Route::group([
    'prefix' => 'pricechangenew',
    'as' => 'pricechangenew.',
    'middleware' => [
        'web',
        InitializePriceChangeNewTenantContext::class,
        'auth',
        'SetSessionData',
        'language',
        'timezone',
        EnsurePriceChangeNewSchema::class,
        'check.route.permission',
        'dynamic.no-store',
    ],
], function (): void {
    /**
     * System 9773 requires the normal [Controller::class, 'method'] action.
     * CheckRoutePermission reads the custom permission action metadata.
     */
    $withPermission = static function ($route, string $permission) {
        $action = $route->getAction();
        $action['permission'] = $permission;
        $route->setAction($action);

        return $route;
    };

    $withPermission(
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard'),
        'pricechangenew.dashboard.view'
    );

    // Approval queue and workflow routes must be registered before /changes/{id}.
    $withPermission(
        Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index'),
        'pricechangenew.approvals.view|pricechangenew.approvals.approve|pricechangenew.approvals.reject'
    );
    $withPermission(
        Route::get('/approvals/data', [ApprovalController::class, 'data'])->name('approvals.data'),
        'pricechangenew.approvals.view|pricechangenew.approvals.approve|pricechangenew.approvals.reject'
    );

    $withPermission(
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index'),
        'pricechangenew.settings.manage'
    );
    $withPermission(
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update'),
        'pricechangenew.settings.manage'
    );

    $withPermission(
        Route::get('/reports/application-history', [ReportController::class, 'history'])->name('reports.history'),
        'pricechangenew.reports.view'
    );
    $withPermission(
        Route::get('/reports/application-history/data', [ReportController::class, 'historyData'])->name('reports.history.data'),
        'pricechangenew.reports.view'
    );

    $withPermission(
        Route::post('/applications/apply-due', [WorkflowController::class, 'applyDue'])->name('applications.apply-due'),
        'pricechangenew.changes.apply'
    );

    $withPermission(
        Route::get('/changes', [PriceChangeController::class, 'index'])->name('changes.index'),
        'pricechangenew.changes.view'
    );
    $withPermission(
        Route::get('/changes/data', [PriceChangeController::class, 'data'])->name('changes.data'),
        'pricechangenew.changes.view'
    );
    $withPermission(
        Route::get('/changes/create', [PriceChangeController::class, 'create'])->name('changes.create'),
        'pricechangenew.changes.create'
    );
    $withPermission(
        Route::post('/changes', [PriceChangeController::class, 'store'])->name('changes.store'),
        'pricechangenew.changes.create'
    );

    $withPermission(
        Route::post('/changes/{id}/submit', [WorkflowController::class, 'submit'])
            ->whereNumber('id')->name('changes.submit'),
        'pricechangenew.changes.submit'
    );
    $withPermission(
        Route::post('/changes/{id}/approve', [WorkflowController::class, 'approve'])
            ->whereNumber('id')->name('changes.approve'),
        'pricechangenew.approvals.approve'
    );
    $withPermission(
        Route::post('/changes/{id}/reject', [WorkflowController::class, 'reject'])
            ->whereNumber('id')->name('changes.reject'),
        'pricechangenew.approvals.reject'
    );
    $withPermission(
        Route::post('/changes/{id}/apply', [WorkflowController::class, 'apply'])
            ->whereNumber('id')->name('changes.apply'),
        'pricechangenew.changes.apply'
    );
    $withPermission(
        Route::post('/changes/{id}/cancel', [WorkflowController::class, 'cancel'])
            ->whereNumber('id')->name('changes.cancel'),
        'pricechangenew.changes.cancel'
    );

    $withPermission(
        Route::get('/changes/{id}', [PriceChangeController::class, 'show'])
            ->whereNumber('id')->name('changes.show'),
        'pricechangenew.changes.view'
    );
    $withPermission(
        Route::get('/changes/{id}/edit', [PriceChangeController::class, 'edit'])
            ->whereNumber('id')->name('changes.edit'),
        'pricechangenew.changes.edit'
    );
    $withPermission(
        Route::put('/changes/{id}', [PriceChangeController::class, 'update'])
            ->whereNumber('id')->name('changes.update'),
        'pricechangenew.changes.edit'
    );
    $withPermission(
        Route::delete('/changes/{id}', [PriceChangeController::class, 'destroy'])
            ->whereNumber('id')->name('changes.destroy'),
        'pricechangenew.changes.delete'
    );

    $withPermission(
        Route::get('/products/search', [ProductLookupController::class, 'search'])->name('products.search'),
        'pricechangenew.changes.create|pricechangenew.changes.edit'
    );
    $withPermission(
        Route::get('/products/{variationId}', [ProductLookupController::class, 'show'])
            ->whereNumber('variationId')->name('products.show'),
        'pricechangenew.changes.create|pricechangenew.changes.edit'
    );
});
