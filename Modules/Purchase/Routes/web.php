<?php

use Illuminate\Support\Facades\Route;
use Modules\Purchase\Http\Controllers\Dashboard\PurchaseDashboardController;

Route::get('/', [PurchaseDashboardController::class, 'index'])->name('index');

require __DIR__ . '/purchase_dashboard.php';
require __DIR__ . '/purchase_entry.php';
require __DIR__ . '/purchase_order.php';
require __DIR__ . '/purchase_return.php';
require __DIR__ . '/purchase_bill.php';
require __DIR__ . '/supplier_payment.php';
require __DIR__ . '/purchase_report.php';
require __DIR__ . '/purchase_setting.php';
