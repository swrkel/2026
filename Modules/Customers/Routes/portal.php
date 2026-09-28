<?php

use Illuminate\Support\Facades\Route;

Route::prefix('distribution-dealer')->group(function () {
    Route::get('/login', 'CustomerPortalController@showLogin')->name('customers.portal.login');
    Route::post('/login', 'CustomerPortalController@login')->name('customers.portal.login.post');

    // CUSTOMERS_SOURCE_HARDENING_V1_PORTAL_AUTH
    Route::middleware('customers.portal.access')->group(function () {

    Route::get('/dashboard', 'CustomerPortalController@dashboard')->name('customers.portal.dashboard');
    Route::get('/ledger', 'CustomerPortalController@ledger')->name('customers.portal.ledger');
    Route::get('/statement', 'CustomerPortalController@statement')->name('customers.portal.statement');
    Route::get('/statement/print', 'CustomerPortalController@printStatement')->name('customers.portal.statement.print');
    Route::get('/statement/export', 'CustomerPortalController@exportStatement')->name('customers.portal.statement.export');
    Route::get('/invoices', 'CustomerPortalController@invoices')->name('customers.portal.invoices');
    Route::get('/payments', 'CustomerPortalController@payments')->name('customers.portal.payments');
    Route::get('/orders', 'CustomerPortalOrderController@index')->name('customers.portal.orders');
    Route::get('/outstanding', 'CustomerPortalController@outstanding')->name('customers.portal.outstanding');
    Route::get('/profile', 'CustomerPortalController@profile')->name('customers.portal.profile');
    Route::get('/notifications', 'CustomerPortalController@notifications')->name('customers.portal.notifications');
    Route::get('/announcements', 'CustomerPortalController@announcements')->name('customers.portal.announcements');
    Route::get('/messages', 'CustomerPortalController@messages')->name('customers.portal.messages');
    Route::get('/documents', 'CustomerPortalController@documents')->name('customers.portal.documents');
    Route::get('/analytics', 'CustomerPortalAnalyticsController@index')->name('customers.portal.analytics');
    // CUS-035: Distribution Dealer AI Assistant
    Route::get('/assistant', 'CustomerPortalAIController@index')->name('customers.portal.ai');
    Route::post('/assistant/ask', 'CustomerPortalAIController@ask')->name('customers.portal.ai.ask');
    // CUS-037: Distribution Dealer Business Health Score
    Route::get('/business-health', 'CustomerBusinessHealthController@index')->name('customers.portal.health');

    // CUS-026: Distribution Dealer Delivery Tracking
    Route::get('/deliveries', 'CustomerPortalDeliveryController@index')->name('customers.portal.deliveries');
    Route::get('/live-tracking', 'CustomerPortalDeliveryController@tracking')->name('customers.portal.tracking');
    Route::get('/deliveries/{id}', 'CustomerPortalDeliveryController@show')->name('customers.portal.deliveries.show');
    Route::get('/deliveries/{id}/track', 'CustomerPortalDeliveryController@track')->name('customers.portal.deliveries.track');
    Route::get('/deliveries/{id}/print', 'CustomerPortalDeliveryController@print')->name('customers.portal.deliveries.print');

    // CUS-024: Distribution Dealer Ordering Portal
    Route::get('/products', 'CustomerPortalOrderController@products')->name('customers.portal.products');
    Route::get('/place-order', 'CustomerPortalOrderController@create')->name('customers.portal.orders.create');
    Route::post('/place-order', 'CustomerPortalOrderController@store')->name('customers.portal.orders.store');
    // CUS-033: Distribution Dealer E-Ordering Workflow
    Route::get('/order-favourites', 'CustomerPortalOrderController@favourites')->name('customers.portal.orders.favourites');
    Route::post('/order-favourites', 'CustomerPortalOrderController@storeFavourite')->name('customers.portal.orders.favourites.store');
    Route::delete('/order-favourites/{productId}', 'CustomerPortalOrderController@removeFavourite')->name('customers.portal.orders.favourites.remove');

    Route::get('/orders/{id}', 'CustomerPortalOrderController@show')->name('customers.portal.orders.show');
    Route::get('/orders/{id}/print', 'CustomerPortalOrderController@print')->name('customers.portal.orders.print');
    Route::get('/orders/{id}/workflow', 'CustomerPortalOrderController@workflow')->name('customers.portal.orders.workflow');
    Route::get('/orders/{id}/repeat', 'CustomerPortalOrderController@repeat')->name('customers.portal.orders.repeat');
    Route::get('/orders/{id}/amend', 'CustomerPortalOrderController@amend')->name('customers.portal.orders.amend');
    Route::post('/orders/{id}/amend', 'CustomerPortalOrderController@storeAmendment')->name('customers.portal.orders.amend.store');
    Route::get('/orders/{id}/cancel', 'CustomerPortalOrderController@cancel')->name('customers.portal.orders.cancel');
    Route::post('/orders/{id}/cancel', 'CustomerPortalOrderController@storeCancellation')->name('customers.portal.orders.cancel.store');
    Route::get('/logout', 'CustomerPortalController@logout')->name('customers.portal.logout');
    });
});
