<?php

use Illuminate\Support\Facades\Route;
use Modules\Customers\Http\Middleware\EnsureDealerApiToken;

Route::post('/login', 'AuthController@login')->name('customers.api.dealer.login');

Route::middleware([EnsureDealerApiToken::class])->group(function () {
    Route::post('/logout', 'AuthController@logout')->name('customers.api.dealer.logout');
    Route::get('/me', 'AuthController@me')->name('customers.api.dealer.me');
    Route::get('/profile', 'ProfileController@show')->name('customers.api.dealer.profile');

    Route::get('/dashboard', 'DashboardController@index')->name('customers.api.dealer.dashboard');
    Route::get('/analytics', 'DashboardController@analytics')->name('customers.api.dealer.analytics');
    Route::get('/credit-summary', 'DashboardController@creditSummary')->name('customers.api.dealer.credit_summary');

    Route::get('/ledger', 'LedgerController@ledger')->name('customers.api.dealer.ledger');
    Route::get('/statements', 'LedgerController@statement')->name('customers.api.dealer.statements');

    Route::get('/invoices', 'InvoiceController@index')->name('customers.api.dealer.invoices');
    Route::get('/invoice/{id}', 'InvoiceController@show')->name('customers.api.dealer.invoice.show');

    Route::get('/payments', 'PaymentController@index')->name('customers.api.dealer.payments');
    Route::get('/payment/{id}', 'PaymentController@show')->name('customers.api.dealer.payment.show');

    Route::get('/orders', 'OrderController@index')->name('customers.api.dealer.orders');
    Route::post('/orders', 'OrderController@store')->name('customers.api.dealer.orders.store');
    Route::get('/order/{id}', 'OrderController@show')->name('customers.api.dealer.order.show');

    Route::get('/deliveries', 'DeliveryController@index')->name('customers.api.dealer.deliveries');
    Route::get('/delivery/{id}', 'DeliveryController@show')->name('customers.api.dealer.delivery.show');
    Route::get('/tracking', 'DeliveryController@tracking')->name('customers.api.dealer.tracking');
    Route::get('/delivery/{id}/tracking', 'DeliveryController@trackingShow')->name('customers.api.dealer.delivery.tracking');
    Route::get('/vehicle-location', 'DeliveryController@vehicleLocation')->name('customers.api.dealer.vehicle_location');

    Route::get('/notifications', 'NotificationController@notifications')->name('customers.api.dealer.notifications');
    Route::post('/notifications/{id}/read', 'NotificationController@markRead')->name('customers.api.dealer.notifications.read');
    Route::get('/messages', 'NotificationController@messages')->name('customers.api.dealer.messages');

    Route::get('/loyalty', 'LoyaltyController@index')->name('customers.api.dealer.loyalty');
    Route::get('/rewards', 'LoyaltyController@index')->name('customers.api.dealer.rewards');
});
