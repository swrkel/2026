<?php
use Illuminate\Support\Facades\Route;
use Modules\ProductsNew\Http\Controllers\DashboardController;
use Modules\ProductsNew\Http\Controllers\Dashboard\KpiDashboardController;
use Modules\ProductsNew\Http\Controllers\ProductController;
use Modules\ProductsNew\Http\Controllers\ProductOpeningStockController;
use Modules\ProductsNew\Http\Controllers\ProductHealthController;
use Modules\ProductsNew\Http\Controllers\ProductTimelineController;
use Modules\ProductsNew\Http\Controllers\StockCenterController;
use Modules\ProductsNew\Http\Controllers\InventoryMovementController;
use Modules\ProductsNew\Http\Controllers\StockHistory\ProductStockHistoryController;
use Modules\ProductsNew\Http\Controllers\OpeningStockController;
use Modules\ProductsNew\Http\Controllers\PriceCenterController;
use Modules\ProductsNew\Http\Controllers\BarcodeController;
use Modules\ProductsNew\Http\Controllers\ImportExportController;
use Modules\ProductsNew\Http\Controllers\Media\ProductMediaController;
use Modules\ProductsNew\Http\Controllers\SettingsController;
use Modules\ProductsNew\Http\Controllers\Settings\CategoryController;
use Modules\ProductsNew\Http\Controllers\Settings\BrandController;
use Modules\ProductsNew\Http\Controllers\Settings\UnitController;
use Modules\ProductsNew\Http\Controllers\Settings\VariationController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductIntelligenceController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductRelationshipController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductWorkflowController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductDuplicateController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductNoteController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductAvailabilityController;
use Modules\ProductsNew\Http\Controllers\Batch\BatchController;
use Modules\ProductsNew\Http\Controllers\Batch\ExpiryController;
use Modules\ProductsNew\Http\Controllers\Batch\RecallController;
use Modules\ProductsNew\Http\Controllers\Serial\SerialNumberController;
use Modules\ProductsNew\Http\Controllers\Serial\WarrantyController;
use Modules\ProductsNew\Http\Controllers\Serial\OwnershipController;
use Modules\ProductsNew\Http\Controllers\ImportExport\DataCleanupController;
use Modules\ProductsNew\Http\Controllers\Admin\ProductionAuditController;
use Modules\ProductsNew\Http\Controllers\Admin\IntegrationBridgeController;
use Modules\ProductsNew\Http\Controllers\Admin\MigrationReadinessController;
use Modules\ProductsNew\Http\Controllers\Admin\LegacyComparisonController;
use Modules\ProductsNew\Http\Controllers\Admin\TestingChecklistController;
use Modules\ProductsNew\Http\Controllers\Admin\DeploymentReadinessController;
use Modules\ProductsNew\Http\Controllers\Framework\ProductTypeController;
use Modules\ProductsNew\Http\Controllers\Framework\CustomFieldController;
use Modules\ProductsNew\Http\Controllers\Framework\RuleEngineController;
use Modules\ProductsNew\Http\Controllers\Framework\ProductTemplateController;
use Modules\ProductsNew\Http\Controllers\Framework\SavedFilterController;
use Modules\ProductsNew\Http\Controllers\Framework\BulkOperationController;
use Modules\ProductsNew\Http\Controllers\Framework\ExceptionCentreController;
use Modules\ProductsNew\Http\Controllers\Framework\VersionHistoryController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\InventoryPlanningController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\CostAnalysisController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\StockIntelligenceController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\ExecutiveInventoryAnalyticsController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\AbcXyzController;
use Modules\ProductsNew\Http\Controllers\CommandCenter\ProductCommandCenterController;
use Modules\ProductsNew\Http\Middleware\InitializeProductsNewTenantContext;


Route::group(['prefix'=>'products-new','as'=>'products-new.','middleware'=>['web', InitializeProductsNewTenantContext::class, 'auth', 'check.route.permission']],function(){
    Route::get('/',[DashboardController::class,'index'])->name('dashboard');
    Route::get('/kpi-centre',[KpiDashboardController::class,'index'])->name('kpi.index');
    Route::post('/kpi-centre/snapshot',[KpiDashboardController::class,'snapshot'])->name('kpi.snapshot');
    Route::get('/products/data',[ProductController::class,'data'])->name('products.data');
    /*
     * The four routes below pass their action as an array with an explicit
     * 'uses' key, so a custom 'permission' value can travel with the route for
     * the check.route.permission middleware.
     *
     * 'uses' must be a STRING. Laravel only normalises the shorthand form
     *     [Controller::class, 'method']
     * when it IS the whole action array. When it appears as the VALUE of
     * 'uses', it is left as an array, and the first request carrying a route
     * parameter fails in RouteSignatureParameters:
     *
     *     ReflectionFunction::__construct(): Argument #1 ($function)
     *     must be of type Closure|string, array given
     *
     * That is why it only shows on these four: each has a {product} parameter,
     * so SubstituteBindings resolves implicit bindings and asks the route for
     * its signature parameters. The parameterless routes in this group never
     * reach that code.
     *
     * Written as Controller::class . '@method' below - the string form - which
     * keeps the 'permission' key exactly as it was.
     */
    Route::get('/products/{product}/opening-stock', [
        'uses' => ProductOpeningStockController::class . '@edit',
        'permission' => 'products_new.opening_stock.view|products_new.opening_stock.create|products_new.update',
    ])->name('products.opening-stock.edit');
    Route::post('/products/{product}/opening-stock', [
        'uses' => ProductOpeningStockController::class . '@update',
        'permission' => 'products_new.opening_stock.create|products_new.update',
    ])->name('products.opening-stock.update');
    Route::patch('/products/{product}/status', [
        'uses' => ProductController::class . '@status',
        'permission' => 'products_new.update',
    ])->name('products.status');
    Route::delete('/products/{product}', [
        'uses' => ProductController::class . '@destroy',
        'permission' => 'products_new.delete',
    ])->name('products.destroy');
    Route::resource('/products',ProductController::class)->except(['destroy']);
    Route::get('/products/{product}/timeline',[ProductTimelineController::class,'show'])->name('products.timeline');
    Route::get('/products/{product}/health',[ProductHealthController::class,'show'])->name('products.health');
    Route::get('/stock-center',[StockCenterController::class,'index'])->name('stock-center.index');
    Route::get('/stock-center/data',[StockCenterController::class,'data'])->name('stock-center.data');
    Route::get('/stock-center/{product}/details',[StockCenterController::class,'details'])->whereNumber('product')->name('stock-center.details');
    Route::get('/inventory-movements',[InventoryMovementController::class,'index'])->name('inventory-movements.index');
    Route::get('/stock-history',[ProductStockHistoryController::class,'index'])->name('stock-history.index');
    Route::post('/inventory-movements',[InventoryMovementController::class,'store'])->name('inventory-movements.store');
    Route::get('/opening-stock',[OpeningStockController::class,'index'])->name('opening-stock.index');
    Route::post('/opening-stock',[OpeningStockController::class,'store'])->name('opening-stock.store');
    Route::get('/opening-stock/{opening_stock}',[OpeningStockController::class,'show'])->name('opening-stock.show');
    Route::post('/opening-stock/{opening_stock}/line',[OpeningStockController::class,'line'])->name('opening-stock.line');
    Route::get('/opening-stock-import/template',[OpeningStockController::class,'template'])->name('opening-stock.import.template');
    Route::post('/opening-stock/{opening_stock}/import',[OpeningStockController::class,'import'])->name('opening-stock.import');
    Route::get('/price-center',[PriceCenterController::class,'index'])->name('price-center.index');
    Route::post('/price-center',[PriceCenterController::class,'store'])->name('price-center.store');
    Route::get('/barcode-center',[BarcodeController::class,'index'])->name('barcode.index');
    Route::post('/barcode-center/queue',[BarcodeController::class,'queue'])->name('barcode.queue');
    Route::get('/barcode-center/templates',[BarcodeController::class,'templates'])->name('barcode.templates');
    Route::post('/barcode-center/templates',[BarcodeController::class,'saveTemplate'])->name('barcode.templates.store');
    Route::get('/media-center',[ProductMediaController::class,'index'])->name('media.index');
    Route::post('/media-center',[ProductMediaController::class,'store'])->name('media.store');
    Route::delete('/media-center/{media}',[ProductMediaController::class,'destroy'])->name('media.destroy');

    Route::get('/intelligence',[ProductIntelligenceController::class,'index'])->name('intelligence.index');
    Route::get('/intelligence/relationships',[ProductRelationshipController::class,'index'])->name('intelligence.relationships.index');
    Route::post('/intelligence/relationships',[ProductRelationshipController::class,'store'])->name('intelligence.relationships.store');
    Route::get('/intelligence/workflow',[ProductWorkflowController::class,'index'])->name('intelligence.workflow.index');
    Route::post('/intelligence/workflow/{product}/transition',[ProductWorkflowController::class,'transition'])->name('intelligence.workflow.transition');
    Route::get('/intelligence/duplicates',[ProductDuplicateController::class,'index'])->name('intelligence.duplicates.index');
    Route::post('/intelligence/duplicates/scan',[ProductDuplicateController::class,'scan'])->name('intelligence.duplicates.scan');
    Route::post('/intelligence/duplicates/{duplicate}/resolve',[ProductDuplicateController::class,'resolve'])->name('intelligence.duplicates.resolve');
    Route::get('/intelligence/notes',[ProductNoteController::class,'index'])->name('intelligence.notes.index');
    Route::post('/intelligence/notes',[ProductNoteController::class,'store'])->name('intelligence.notes.store');
    Route::get('/intelligence/availability',[ProductAvailabilityController::class,'index'])->name('intelligence.availability.index');
    Route::post('/intelligence/availability/refresh',[ProductAvailabilityController::class,'refresh'])->name('intelligence.availability.refresh');

    Route::get('/batch-centre',[BatchController::class,'index'])->name('batch.index');
    Route::post('/batch-centre',[BatchController::class,'store'])->name('batch.store');
    Route::get('/batch-centre/{batch}',[BatchController::class,'show'])->name('batch.show');
    Route::post('/batch-centre/{batch}/movement',[BatchController::class,'movement'])->name('batch.movement');
    Route::get('/expiry-centre',[ExpiryController::class,'index'])->name('expiry.index');
    Route::post('/expiry-centre/refresh',[ExpiryController::class,'refresh'])->name('expiry.refresh');
    Route::post('/expiry-centre/{alert}/resolve',[ExpiryController::class,'resolve'])->name('expiry.resolve');
    Route::get('/recall-centre',[RecallController::class,'index'])->name('recalls.index');
    Route::post('/recall-centre',[RecallController::class,'store'])->name('recalls.store');
    Route::post('/recall-centre/{recall}/close',[RecallController::class,'close'])->name('recalls.close');

    Route::get('/serial-centre',[SerialNumberController::class,'index'])->name('serial.index');
    Route::post('/serial-centre',[SerialNumberController::class,'store'])->name('serial.store');
    Route::get('/serial-centre/{serial}',[SerialNumberController::class,'show'])->name('serial.show');
    Route::post('/serial-centre/{serial}/status',[SerialNumberController::class,'status'])->name('serial.status');
    Route::get('/warranty-centre',[WarrantyController::class,'index'])->name('warranty.index');
    Route::post('/warranty-centre/register',[WarrantyController::class,'register'])->name('warranty.register');
    Route::post('/warranty-centre/claim',[WarrantyController::class,'claim'])->name('warranty.claim');
    Route::post('/warranty-centre/claim/{claim}',[WarrantyController::class,'updateClaim'])->name('warranty.claim.update');
    Route::get('/ownership-history',[OwnershipController::class,'index'])->name('ownership.index');
    Route::post('/ownership-history',[OwnershipController::class,'store'])->name('ownership.store');
    Route::get('/import-export',[ImportExportController::class,'index'])->name('import-export.index');
    Route::post('/import-export/import',[ImportExportController::class,'import'])->name('import-export.import');
    Route::get('/import-export/export',[ImportExportController::class,'export'])->name('import-export.export');
    Route::get('/import-export/template',[ImportExportController::class,'template'])->name('import-export.template');
    Route::get('/import-export/{session}/review',[ImportExportController::class,'review'])->whereNumber('session')->name('import-export.review');
    Route::post('/import-export/{session}/commit',[ImportExportController::class,'commit'])->whereNumber('session')->name('import-export.commit');
    Route::get('/data-cleanup',[DataCleanupController::class,'index'])->name('data-cleanup.index');
    Route::post('/data-cleanup',[DataCleanupController::class,'store'])->name('data-cleanup.store');
    Route::get('/production-audit',[ProductionAuditController::class,'index'])->name('production-audit.index');
    Route::get('/integration-bridge',[IntegrationBridgeController::class,'index'])->name('integration-bridge.index');
    Route::get('/migration-readiness',[MigrationReadinessController::class,'index'])->name('migration-readiness.index');
    Route::get('/legacy-comparison',[LegacyComparisonController::class,'index'])->name('legacy-comparison.index');
    Route::get('/testing-checklist',[TestingChecklistController::class,'index'])->name('testing-checklist.index');
    Route::get('/deployment-readiness',[DeploymentReadinessController::class,'index'])->name('deployment-readiness.index');

    Route::get('/framework/product-types',[ProductTypeController::class,'index'])->name('framework.product-types.index');
    Route::post('/framework/product-types',[ProductTypeController::class,'store'])->name('framework.product-types.store');
    Route::get('/framework/product-types/{id}',[ProductTypeController::class,'show'])->name('framework.product-types.show');
    Route::get('/framework/custom-fields',[CustomFieldController::class,'index'])->name('framework.custom-fields.index');
    Route::post('/framework/custom-fields',[CustomFieldController::class,'store'])->name('framework.custom-fields.store');
    Route::get('/framework/custom-fields/{product}/values',[CustomFieldController::class,'values'])->name('framework.custom-fields.values');
    Route::post('/framework/custom-fields/{product}/values',[CustomFieldController::class,'saveValues'])->name('framework.custom-fields.values.store');
    Route::get('/framework/rules',[RuleEngineController::class,'index'])->name('framework.rules.index');
    Route::post('/framework/rules',[RuleEngineController::class,'store'])->name('framework.rules.store');
    Route::get('/framework/rules/validate/{product}',[RuleEngineController::class,'validateProduct'])->name('framework.rules.validate');
    Route::get('/framework/templates',[ProductTemplateController::class,'index'])->name('framework.templates.index');
    Route::post('/framework/templates',[ProductTemplateController::class,'store'])->name('framework.templates.store');
    Route::get('/framework/templates/{template}/apply',[ProductTemplateController::class,'apply'])->name('framework.templates.apply');
    Route::get('/framework/saved-filters',[SavedFilterController::class,'index'])->name('framework.saved-filters.index');
    Route::post('/framework/saved-filters',[SavedFilterController::class,'store'])->name('framework.saved-filters.store');
    Route::get('/framework/bulk-operations',[BulkOperationController::class,'index'])->name('framework.bulk-operations.index');
    Route::post('/framework/bulk-operations/preview',[BulkOperationController::class,'preview'])->name('framework.bulk-operations.preview');
    Route::post('/framework/bulk-operations/commit',[BulkOperationController::class,'commit'])->name('framework.bulk-operations.commit');
    Route::get('/framework/exceptions',[ExceptionCentreController::class,'index'])->name('framework.exceptions.index');
    Route::get('/framework/version-history',[VersionHistoryController::class,'index'])->name('framework.version-history.index');
    Route::get('/framework/version-history/{id}',[VersionHistoryController::class,'show'])->name('framework.version-history.show');


    Route::get('/inventory-intelligence/planning',[InventoryPlanningController::class,'index'])->name('inventory-intelligence.planning.index');
    Route::post('/inventory-intelligence/planning',[InventoryPlanningController::class,'store'])->name('inventory-intelligence.planning.store');
    Route::post('/inventory-intelligence/planning/build-proposals',[InventoryPlanningController::class,'buildProposals'])->name('inventory-intelligence.planning.build-proposals');
    Route::get('/inventory-intelligence/cost-analysis',[CostAnalysisController::class,'index'])->name('inventory-intelligence.cost-analysis.index');
    Route::post('/inventory-intelligence/cost-analysis',[CostAnalysisController::class,'store'])->name('inventory-intelligence.cost-analysis.store');
    Route::get('/inventory-intelligence/stock-intelligence',[StockIntelligenceController::class,'index'])->name('inventory-intelligence.stock-intelligence.index');
    Route::post('/inventory-intelligence/stock-intelligence',[StockIntelligenceController::class,'store'])->name('inventory-intelligence.stock-intelligence.store');
    Route::get('/inventory-intelligence/abc-xyz',[AbcXyzController::class,'index'])->name('inventory-intelligence.abc-xyz.index');
    Route::post('/inventory-intelligence/abc-xyz',[AbcXyzController::class,'store'])->name('inventory-intelligence.abc-xyz.store');
    Route::get('/inventory-intelligence/executive',[ExecutiveInventoryAnalyticsController::class,'index'])->name('inventory-intelligence.executive.index');

    Route::get('/command-center',[ProductCommandCenterController::class,'index'])->name('command-center.index');
    Route::get('/command-center/search',[ProductCommandCenterController::class,'search'])->name('command-center.search');
    Route::get('/command-center/{product}',[ProductCommandCenterController::class,'show'])->name('command-center.show');
    Route::post('/command-center/{product}/snapshot',[ProductCommandCenterController::class,'snapshot'])->name('command-center.snapshot');

    Route::get('/settings',[SettingsController::class,'index'])->name('settings.index');
    Route::post('/settings',[SettingsController::class,'store'])->name('settings.store');

    Route::get('/categories',[CategoryController::class,'index'])->name('settings.categories.index');
    Route::post('/categories',[CategoryController::class,'store'])->name('settings.categories.store');
    Route::post('/categories/import',[CategoryController::class,'import'])->name('settings.categories.import');
    Route::get('/categories/import/template',[CategoryController::class,'importTemplate'])->name('settings.categories.import-template');
    Route::put('/categories/{category}',[CategoryController::class,'update'])->whereNumber('category')->name('settings.categories.update');
    Route::delete('/categories/{category}',[CategoryController::class,'destroy'])->whereNumber('category')->name('settings.categories.destroy');
    Route::get('/settings/categories', [\Modules\ProductsNew\Http\Controllers\RouteClosures\WebRouteController::class, 'handle1'])->name('settings.categories.legacy');
    Route::get('/settings/brands',[BrandController::class,'index'])->name('settings.brands.index');
    Route::post('/settings/brands',[BrandController::class,'store'])->name('settings.brands.store');
    Route::get('/settings/units',[UnitController::class,'index'])->name('settings.units.index');
    Route::post('/settings/units',[UnitController::class,'store'])->name('settings.units.store');
    Route::get('/settings/variations',[VariationController::class,'index'])->name('settings.variations.index');
    Route::post('/settings/variations',[VariationController::class,'store'])->name('settings.variations.store');
});
