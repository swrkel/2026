<?php

use Illuminate\Support\Facades\Route;
use Modules\POS2\Http\Controllers\Pos2Controller;
use Modules\SMS\Http\Controllers\SmsSendController;

/*
|--------------------------------------------------------------------------
| POS Standalone Fallback Loader - S356
|--------------------------------------------------------------------------
| The new standalone POS module lives in Modules/POS and uses /pos-module.
| Some installations are still loading the old POS2 module but not scanning
| the new POS module provider yet. This fallback makes /pos-module routes
| visible whenever the already-registered POS2 module is loaded.
|
| This does NOT make POS depend on POS2 for its functionality. POS2 only acts
| as a route bootstrap fallback to prevent 404 while module cache/status files
| are being refreshed on multi-tenant servers.
*/
/*
 * MA-002: this fallback had TWO faults, and together they produced
 *
 *     Invalid route action:
 *     [Modules\POS2\Http\Controllers\Modules\POS\Http\Controllers\PageFix\ReturnsPageController]
 *
 * on login - the namespace doubled up.
 *
 * 1. THE GUARD KEY DID NOT MATCH.
 *    Modules/POS/Providers/RouteServiceProvider sets
 *        app()->instance('pos.routes.loaded', true)     <- dots
 *    while this file checked
 *        app()->bound('pos.routes_loaded')              <- underscore
 *    Two different keys, so the guard never saw POS's flag and this
 *    fallback ran EVERY TIME - including when POS had already loaded its
 *    own routes correctly. Every POS route was being registered twice.
 *
 * 2. THE SECOND REGISTRATION USED POS2's NAMESPACE.
 *    POS2's RouteServiceProvider loads this file inside
 *        ->namespace('Modules\POS2\Http\Controllers')
 *    and a nested group inherits that. POS's route files refer to their
 *    controllers with ReturnsPageController::class, which produces a name
 *    with NO leading backslash - so Laravel treats it as relative and
 *    glues POS2's namespace on the front. Hence the doubled class name.
 *
 * Both are fixed below. The guard now reads the key POS actually sets, so
 * this only runs when POS genuinely has not loaded. And when it does run,
 * the namespace is reset first: passing a namespace that begins with a
 * backslash REPLACES the inherited one rather than appending to it, which
 * is what lets POS's ::class references resolve as written.
 */
$standalonePosRoutes = base_path('Modules/POS/Routes/web.php');

$posAlreadyLoaded = app()->bound('pos.routes.loaded')
    || app()->bound('pos.routes_loaded');   // the old key, in case anything else sets it

if (is_file($standalonePosRoutes) && ! $posAlreadyLoaded) {
    app()->instance('pos.routes.loaded', true);
    app()->instance('pos.routes_loaded', true);

    Route::middleware('web')
        ->namespace('\\')
        ->group($standalonePosRoutes);
}

$standalonePosReports = base_path('Modules/POS/Routes/reports.php');
if (is_file($standalonePosReports) && ! app()->bound('pos.reports_routes_loaded')) {
    app()->instance('pos.reports_routes_loaded', true);
    Route::middleware('web')->group($standalonePosReports);
}

$standalonePosKitchen = base_path('Modules/POS/Routes/kitchen.php');
if (is_file($standalonePosKitchen) && ! app()->bound('pos.kitchen_routes_loaded')) {
    app()->instance('pos.kitchen_routes_loaded', true);
    Route::middleware('web')->group($standalonePosKitchen);
}

Route::middleware(['web', 'tenant.context'])->prefix('pos2')->name('pos2.')->group(function () {
    Route::get('/toggle-subscription/{id}', [Pos2Controller::class, 'toggleRecurringInvoices'])->name('subscription.toggle');
    Route::get('/toggle_popup', [Pos2Controller::class, 'toggle_popup'])->name('popup.toggle');
    Route::post('/get-customer-details', [Pos2Controller::class, 'getCustomerDueDetails'])->name('customers.due_details');
    Route::get('/get-customer-infos', [Pos2Controller::class, 'getCustomerInfos'])->name('customers.infos');
    Route::post('/sells/pos/get-types-of-service-details', [Pos2Controller::class, 'getTypesOfServiceDetails'])->name('sells.service_type_details');
    Route::get('/sells/subscriptions', [Pos2Controller::class, 'listSubscriptions'])->name('sells.subscriptions.index');
    Route::get('/sells/invoice-url/{id}', [Pos2Controller::class, 'showInvoiceUrl'])->name('sells.invoice_url');
    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', [Pos2Controller::class, 'getProductRow'])->name('sells.product_row');
    Route::get('sells/pos/get_product_row_temp/{variation_id}/{location_id}/{temp_qty}', [Pos2Controller::class, 'getProductRowTemp'])->name('sells.product_row_temp');
    Route::post('/sells/pos/get_payment_row', [Pos2Controller::class, 'getPaymentRow'])->name('sells.payment_row');
    Route::get('/sells/pos/get_payment_method', [Pos2Controller::class, 'getPaymentMethods'])->name('sells.payment_methods');
    Route::get('/sales/pos/get_payment_account_id/{payment_method}', [Pos2Controller::class, 'getPaymentRowAccountId'])->name('sales.payment_account');
    Route::post('/sells/pos/get-reward-details', [Pos2Controller::class, 'getRewardDetails'])->name('sells.reward_details');
    Route::get('/sells/pos/get-recent-transactions', [Pos2Controller::class, 'getRecentTransactions'])->name('sells.recent_transactions');
    Route::get('/sells/pos/get-recent-transactions-popup', [Pos2Controller::class, 'getRecentTransactionPopup'])->name('sells.recent_transactions_popup');
    Route::get('/sells/{transaction_id}/print', [Pos2Controller::class, 'printInvoice'])->name('sell.printInvoice');
    Route::get('/sells/{ref_number}/print_invoice', [Pos2Controller::class, 'printOustandingInvoice'])->name('sell.printOustandingInvoice');
    Route::get('/sells/pos/get-product-suggestion', [Pos2Controller::class, 'getProductSuggestion'])->name('sells.product_suggestion');
    Route::get('pos/get_customer_details', [Pos2Controller::class, 'getCustomerDetails'])->name('pos.customer_details');
    Route::post('/pos/manager/send-otp', [SmsSendController::class, 'sendManagerOtp'])->name('manager.sendOtp');
    Route::get('/pos/settings', [Pos2Controller::class, 'settings'])->name('pos.settings');
    Route::post('/pos/settings', [Pos2Controller::class, 'updateSettings'])->name('pos.settings.update');
    Route::get('pos2/create', [Pos2Controller::class, 'create'])->name('legacy_pos.create');
    Route::resource('pos', Pos2Controller::class);
});
