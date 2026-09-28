<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\Workflow\WorkflowController;
Route::get('workflow',[WorkflowController::class,'index'])->name('workflow.index')->middleware('permission:airline_ticketing_new.workflow.view');
Route::post('workflow/{workflow}/approve',[WorkflowController::class,'approve'])->name('workflow.approve')->middleware('permission:airline_ticketing_new.workflow.approve');
