<?php
use Illuminate\Support\Facades\Route;
use Modules\AirlineTicketingNew\Http\Controllers\B2b\B2bAgentController;

Route::get('b2b/agents', [B2bAgentController::class, 'index'])
    ->name('b2b.agents.index')
    ->middleware('permission:airline_ticketing_new.b2b_agents.view');
