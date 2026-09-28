<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Ticketing\InvoiceController;
use Modules\AirlineTicketingNew\Http\Controllers\Ticketing\PaymentController;
use Modules\AirlineTicketingNew\Http\Controllers\Ticketing\ReceiptController;
use Modules\AirlineTicketingNew\Http\Controllers\Ticketing\TicketController;

Route::middleware('permission:airline_ticketing_new.tickets.view')->group(function (): void {
    Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
    Route::get('tickets/{ticket}', [TicketController::class, 'show'])->name('tickets.show');
});

Route::middleware('permission:airline_ticketing_new.tickets.issue')->group(function (): void {
    Route::get('reservations/{reservation}/issue-ticket', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('reservations/{reservation}/issue-ticket', [TicketController::class, 'store'])->name('tickets.store');
});

Route::middleware('permission:airline_ticketing_new.invoices.view')->group(function (): void {
    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
});

Route::middleware('permission:airline_ticketing_new.invoices.create')->group(function (): void {
    Route::get('tickets/{ticket}/create-invoice', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('tickets/{ticket}/create-invoice', [InvoiceController::class, 'store'])->name('invoices.store');
});

Route::middleware('permission:airline_ticketing_new.payments.view')->group(function (): void {
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
    Route::get('payments/{payment}', [PaymentController::class, 'show'])->name('payments.show');
});

Route::middleware('permission:airline_ticketing_new.payments.create')->group(function (): void {
    Route::get('invoices/{invoice}/receive-payment', [PaymentController::class, 'create'])->name('payments.create');
    Route::post('invoices/{invoice}/receive-payment', [PaymentController::class, 'store'])->name('payments.store');
});

Route::get('receipts/{receipt}', [ReceiptController::class, 'show'])
    ->name('receipts.show')
    ->middleware('permission:airline_ticketing_new.receipts.view');

Route::get('receipts/{receipt}/print', [ReceiptController::class, 'print'])
    ->name('receipts.print')
    ->middleware('permission:airline_ticketing_new.receipts.print');
