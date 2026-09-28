<?php

namespace Modules\ProductsNew\Http\Controllers\RouteClosures;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use Modules\ProductsNew\Http\Controllers\Admin\DeploymentReadinessController;
use Modules\ProductsNew\Http\Controllers\Admin\IntegrationBridgeController;
use Modules\ProductsNew\Http\Controllers\Admin\LegacyComparisonController;
use Modules\ProductsNew\Http\Controllers\Admin\MigrationReadinessController;
use Modules\ProductsNew\Http\Controllers\Admin\ProductionAuditController;
use Modules\ProductsNew\Http\Controllers\Admin\TestingChecklistController;
use Modules\ProductsNew\Http\Controllers\BarcodeController;
use Modules\ProductsNew\Http\Controllers\Batch\BatchController;
use Modules\ProductsNew\Http\Controllers\Batch\ExpiryController;
use Modules\ProductsNew\Http\Controllers\Batch\RecallController;
use Modules\ProductsNew\Http\Controllers\CommandCenter\ProductCommandCenterController;
use Modules\ProductsNew\Http\Controllers\DashboardController;
use Modules\ProductsNew\Http\Controllers\Dashboard\KpiDashboardController;
use Modules\ProductsNew\Http\Controllers\Framework\BulkOperationController;
use Modules\ProductsNew\Http\Controllers\Framework\CustomFieldController;
use Modules\ProductsNew\Http\Controllers\Framework\ExceptionCentreController;
use Modules\ProductsNew\Http\Controllers\Framework\ProductTemplateController;
use Modules\ProductsNew\Http\Controllers\Framework\ProductTypeController;
use Modules\ProductsNew\Http\Controllers\Framework\RuleEngineController;
use Modules\ProductsNew\Http\Controllers\Framework\SavedFilterController;
use Modules\ProductsNew\Http\Controllers\Framework\VersionHistoryController;
use Modules\ProductsNew\Http\Controllers\ImportExportController;
use Modules\ProductsNew\Http\Controllers\ImportExport\DataCleanupController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductAvailabilityController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductDuplicateController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductIntelligenceController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductNoteController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductRelationshipController;
use Modules\ProductsNew\Http\Controllers\Intelligence\ProductWorkflowController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\AbcXyzController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\CostAnalysisController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\ExecutiveInventoryAnalyticsController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\InventoryPlanningController;
use Modules\ProductsNew\Http\Controllers\InventoryIntelligence\StockIntelligenceController;
use Modules\ProductsNew\Http\Controllers\InventoryMovementController;
use Modules\ProductsNew\Http\Controllers\Media\ProductMediaController;
use Modules\ProductsNew\Http\Controllers\OpeningStockController;
use Modules\ProductsNew\Http\Controllers\PriceCenterController;
use Modules\ProductsNew\Http\Controllers\ProductController;
use Modules\ProductsNew\Http\Controllers\ProductHealthController;
use Modules\ProductsNew\Http\Controllers\ProductOpeningStockController;
use Modules\ProductsNew\Http\Controllers\ProductTimelineController;
use Modules\ProductsNew\Http\Controllers\Serial\OwnershipController;
use Modules\ProductsNew\Http\Controllers\Serial\SerialNumberController;
use Modules\ProductsNew\Http\Controllers\Serial\WarrantyController;
use Modules\ProductsNew\Http\Controllers\SettingsController;
use Modules\ProductsNew\Http\Controllers\Settings\BrandController;
use Modules\ProductsNew\Http\Controllers\Settings\CategoryController;
use Modules\ProductsNew\Http\Controllers\Settings\UnitController;
use Modules\ProductsNew\Http\Controllers\Settings\VariationController;
use Modules\ProductsNew\Http\Controllers\StockCenterController;
use Modules\ProductsNew\Http\Controllers\StockHistory\ProductStockHistoryController;
use Modules\ProductsNew\Http\Middleware\InitializeProductsNewTenantContext;

/**
 * MA-002 - route closures moved out of Modules/ProductsNew/Routes/web.php.
 *
 * WHY: Laravel cannot run `php artisan route:cache` while ANY route is defined
 * with a closure. This installation has 8,613 routes across 349 files, and
 * without the cache every one is parsed and compiled on EVERY request,
 * including the login page. That is the multi-second delay.
 *
 * Only 35 closures across 14 files were blocking it.
 *
 * The method bodies are BYTE-IDENTICAL to the closures they replace. Nothing
 * was rewritten - the code simply lives in a class so the route can be cached.
 */
class WebRouteController
{
    public function handle1()
    {
        return redirect()->route('products-new.settings.categories.index');
    }
}
