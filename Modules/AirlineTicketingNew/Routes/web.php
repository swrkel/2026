<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\DashboardController;
use Modules\AirlineTicketingNew\Http\Controllers\SettingsController;
use Modules\AirlineTicketingNew\Http\Middleware\EnsureAirlineTicketingBusinessScope;
use Modules\AirlineTicketingNew\Http\Middleware\EnsureAirlineTicketingNewAccess;

Route::prefix('airline-ticketing-new')
    ->as('airline-ticketing-new.')
    ->middleware([
        'auth',
        EnsureAirlineTicketingNewAccess::class,
        EnsureAirlineTicketingBusinessScope::class,
    ])
    ->group(function (): void {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::middleware('permission:airline_ticketing_new.settings.manage')
            ->prefix('settings')
            ->as('settings.')
            ->group(function (): void {
                Route::get('/', [SettingsController::class, 'index'])->name('index');
                Route::put('/', [SettingsController::class, 'update'])->name('update');
            });

        // Consolidated feature routes from ATN staged parcels.
        require module_path('AirlineTicketingNew', 'Routes/masters.php');
        require module_path('AirlineTicketingNew', 'Routes/profiles.php');
        require module_path('AirlineTicketingNew', 'Routes/transactions.php');
        require module_path('AirlineTicketingNew', 'Routes/ticketing.php');
        require module_path('AirlineTicketingNew', 'Routes/post-ticket.php');
        require module_path('AirlineTicketingNew', 'Routes/settlements.php');
        require module_path('AirlineTicketingNew', 'Routes/operations.php');
        require module_path('AirlineTicketingNew', 'Routes/reports.php');
        require module_path('AirlineTicketingNew', 'Routes/admin.php');
        require module_path('AirlineTicketingNew', 'Routes/supplier-payments.php');
        require module_path('AirlineTicketingNew', 'Routes/incentives.php');
        require module_path('AirlineTicketingNew', 'Routes/advanced-reports.php');
        require module_path('AirlineTicketingNew', 'Routes/notifications.php');
        require module_path('AirlineTicketingNew', 'Routes/corporate-credit.php');
        require module_path('AirlineTicketingNew', 'Routes/visa.php');
        require module_path('AirlineTicketingNew', 'Routes/tours.php');
        require module_path('AirlineTicketingNew', 'Routes/hotels.php');
        require module_path('AirlineTicketingNew', 'Routes/transport.php');
        require module_path('AirlineTicketingNew', 'Routes/enterprise.php');
        require module_path('AirlineTicketingNew', 'Routes/gds.php');
        require module_path('AirlineTicketingNew', 'Routes/flight-operations.php');
        require module_path('AirlineTicketingNew', 'Routes/documents.php');
        require module_path('AirlineTicketingNew', 'Routes/workflow.php');
        require module_path('AirlineTicketingNew', 'Routes/enterprise-admin.php');
        require module_path('AirlineTicketingNew', 'Routes/customer-portal.php');
        require module_path('AirlineTicketingNew', 'Routes/b2b.php');
        require module_path('AirlineTicketingNew', 'Routes/api_v1.php');
        require module_path('AirlineTicketingNew', 'Routes/mobile.php');
        require module_path('AirlineTicketingNew', 'Routes/analytics.php');
        require module_path('AirlineTicketingNew', 'Routes/production.php');
        require module_path('AirlineTicketingNew', 'Routes/travel-services-8.php');
        require module_path('AirlineTicketingNew', 'Routes/post-ticket-advanced.php');
        require module_path('AirlineTicketingNew', 'Routes/reporting-enterprise.php');
        require module_path('AirlineTicketingNew', 'Routes/security-ui.php');
        require module_path('AirlineTicketingNew', 'Routes/performance-admin.php');
    });

// Host-application compatibility route using module.json alias.
Route::prefix('airline-ticketing-new')
    ->middleware([
        'auth',
        EnsureAirlineTicketingNewAccess::class,
        EnsureAirlineTicketingBusinessScope::class,
    ])
    ->group(function (): void {
        Route::get('/module-index', [DashboardController::class, 'index'])->name('airlineticketingnew.index');
    });
