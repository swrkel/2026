<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Documents\ManagedDocumentController;
Route::get('documents',[ManagedDocumentController::class,'index'])->name('documents.index')->middleware('permission:airline_ticketing_new.documents.view');
Route::post('documents',[ManagedDocumentController::class,'store'])->name('documents.store')->middleware('permission:airline_ticketing_new.documents.create');
