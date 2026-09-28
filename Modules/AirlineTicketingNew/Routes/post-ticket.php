<?php

use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\PostTicket\CancellationController;
use Modules\AirlineTicketingNew\Http\Controllers\PostTicket\CreditNoteController;
use Modules\AirlineTicketingNew\Http\Controllers\PostTicket\RefundController;
use Modules\AirlineTicketingNew\Http\Controllers\PostTicket\ReissueController;
use Modules\AirlineTicketingNew\Http\Controllers\PostTicket\VoidController;

Route::get('reissues', [ReissueController::class, 'index'])->name('reissues.index')
    ->middleware('permission:airline_ticketing_new.reissues.view');
Route::get('tickets/{ticket}/reissue', [ReissueController::class, 'create'])->name('reissues.create')
    ->middleware('permission:airline_ticketing_new.reissues.create');
Route::post('tickets/{ticket}/reissue', [ReissueController::class, 'store'])->name('reissues.store')
    ->middleware('permission:airline_ticketing_new.reissues.create');

Route::get('voids', [VoidController::class, 'index'])->name('voids.index')
    ->middleware('permission:airline_ticketing_new.voids.view');
Route::get('tickets/{ticket}/void', [VoidController::class, 'create'])->name('voids.create')
    ->middleware('permission:airline_ticketing_new.voids.create');
Route::post('tickets/{ticket}/void', [VoidController::class, 'store'])->name('voids.store')
    ->middleware('permission:airline_ticketing_new.voids.create');
Route::post('voids/{void}/approve', [VoidController::class, 'approve'])->name('voids.approve')
    ->middleware('permission:airline_ticketing_new.voids.approve');

Route::get('cancellations', [CancellationController::class, 'index'])->name('cancellations.index')
    ->middleware('permission:airline_ticketing_new.cancellations.view');
Route::get('tickets/{ticket}/cancel', [CancellationController::class, 'create'])->name('cancellations.create')
    ->middleware('permission:airline_ticketing_new.cancellations.create');
Route::post('tickets/{ticket}/cancel', [CancellationController::class, 'store'])->name('cancellations.store')
    ->middleware('permission:airline_ticketing_new.cancellations.create');
Route::post('cancellations/{cancellation}/approve-refund', [CancellationController::class, 'approve'])
    ->name('cancellations.approve-refund')
    ->middleware('permission:airline_ticketing_new.refunds.approve');

Route::get('refunds', [RefundController::class, 'index'])->name('refunds.index')
    ->middleware('permission:airline_ticketing_new.refunds.view');
Route::get('refunds/{refund}', [RefundController::class, 'show'])->name('refunds.show')
    ->middleware('permission:airline_ticketing_new.refunds.view');

Route::get('credit-notes', [CreditNoteController::class, 'index'])->name('credit-notes.index')
    ->middleware('permission:airline_ticketing_new.credit_notes.view');
Route::get('credit-notes/{creditNote}', [CreditNoteController::class, 'show'])->name('credit-notes.show')
    ->middleware('permission:airline_ticketing_new.credit_notes.view');
