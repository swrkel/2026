<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Petro General - Pumper Management Routes
|--------------------------------------------------------------------------
| PG018 keeps the existing working business logic untouched and introduces
| small wrapper controllers so each pumper function can be maintained in its
| own file. The legacy controllers are not removed in this package.
*/

/*
| Pumper Management: this URL now serves the WORKING page.
|
| Same defect as Dip Management and Daily Status Report - the third page hit by
| the same unfinished rebuild.
|
|   pump_operators/index.blade.php     - the complete one. 1755 lines, driven by
|                                        PumpOperatorController@index, which also
|                                        serves its datatable over ajax.
|
|   pumper_management/index.blade.php  - a newer shell. Its four tabs - Pumpers,
|                                        Assignments, Shifts, Settings - are
|                                        four-line placeholders reading "This tab
|                                        is separated for Petro General
|                                        maintenance". No lists, no data.
|
| This URL pointed at the SHELL, and PumpOperatorController@index was routed
| NOWHERE - so the working page was unreachable from the interface.
|
| The route name is unchanged, so the sidebar link keeps working. That link
| already treats both /petro-general/pump-operators* and
| /petro-general/pumper-management* as active, so the menu highlights correctly
| either way.
|
| The pumper_management shell views and PumperIndexController are left in place,
| unused, rather than deleted: they are somebody's partial rebuild and are not
| mine to remove. Nothing routes to them now.
*/
Route::get('/pumper-management', 'PumpOperatorController@index')
    ->name('petrogeneral.pumper_management.index');

// PUMPER-MGMT-EXCESS-ROUTE-20260821: dedicated data URL kept outside
// /pump-operators/{pump_operator} so the literal endpoint can never be
// consumed by PumpOperator resource model binding.
Route::get('/pumper-management/excess-shortage-payments/data', 'PumpOperatorController@getPumperExcessShortagePayments')
    ->name('petrogeneral.pumper_management.excess_shortage_payments.data');

Route::get('/pump-operators/get-settings', 'Pumper\\PumperSettingsController@edit')
    ->name('petrogeneral.pump_operators.settings.edit');
Route::post('/pump-operators/get-settings', 'Pumper\\PumperSettingsController@store')
    ->name('petrogeneral.pump_operators.settings.store');

Route::get('/pump-operators/update-passcode', 'Pumper\\PumperPasscodeController@edit')
    ->name('petrogeneral.pump_operators.passcode.edit');
Route::post('/pump-operators/update-passcode', 'Pumper\\PumperPasscodeController@store')
    ->name('petrogeneral.pump_operators.passcode.store');

Route::get('/pump-operators/ledger', 'Pumper\\PumperLedgerController@index')
    ->name('petrogeneral.pump_operators.ledger');
Route::get('/pump-operators/list-commission/{id}', 'Pumper\\PumperCommissionController@index')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.pump_operators.commission.index');

Route::get('/pump-operators/toggle-active/{id}', 'Pumper\\PumperStatusController@toggle')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.pump_operators.toggle_active');

Route::get('/pump-operators/get-dashboard-data', 'Pumper\\PumperDashboardController@data')
    ->name('petrogeneral.pump_operators.dashboard.data');
Route::get('/pump-operators/setting_dash', 'Pumper\\PumperDashboardController@settings')
    ->name('petrogeneral.pump_operators.dashboard.settings');
Route::get('/pump-operators/dashboard', 'Pumper\\PumperDashboardController@index')
    ->name('petrogeneral.pump_operators.dashboard.index');

Route::get('/pump-operators/check-passcode', 'Pumper\\PumperLoginCheckController@passcode')
    ->name('petrogeneral.pump_operators.check_passcode');
Route::get('/pump-operators/check-username', 'Pumper\\PumperLoginCheckController@username')
    ->name('petrogeneral.pump_operators.check_username');

Route::get('/pump-operators/unblock-pumper-login-attempt/{id}', 'Pumper\\PumperLoginAttemptController@unblock')
    ->where('id', '[0-9]+')
    ->name('petrogeneral.unblockPumperLoginAttempt');
Route::get('/pump-operators/unblock-pumper-login-attempts', 'Pumper\\PumperLoginAttemptController@blocked')
    ->name('petrogeneral.blockedPumperLoginAttempt');
Route::get('/pump-operators/login-attempt-history', 'Pumper\\PumperLoginAttemptController@history')
    ->name('petrogeneral.pumper_login_attempt_history');

Route::get('/pump-operator-actions/get-receive-pump', 'Pumper\\PumperReceivePumpController@index')
    ->name('petrogeneral.pump_operator_actions.receive_pump');
Route::get('/pump-operator-actions/get-colsing-meter-modal', 'Pumper\\PumperClosingMeterController@modal')
    ->name('petrogeneral.pump_operator_actions.closing_meter_modal');
Route::get('/pump-operator-actions/get-colsing-meter/{pump_id}', 'Pumper\\PumperClosingMeterController@show')
    ->where('pump_id', '[0-9]+')
    ->name('petrogeneral.pump_operator_actions.closing_meter.show');
Route::post('/pump-operator-actions/get-colsing-meter/{pump_id}', 'Pumper\\PumperClosingMeterController@store')
    ->where('pump_id', '[0-9]+')
    ->name('petrogeneral.pump_operator_actions.closing_meter.store');

Route::post('/pump-operator-actions/get-pumper-assignment/{pump_id}/{pump_operator_id}', 'Pumper\\PumperAssignmentActionController@postAssignment')
    ->where(['pump_id' => '[0-9]+', 'pump_operator_id' => '[0-9]+'])
    ->name('petrogeneral.pump_operator_actions.assignment.post');
Route::get('/pump-operator-actions/get-pumper-assignment/{pump_id}/{pump_operator_id}', 'Pumper\\PumperAssignmentActionController@getAssignment')
    ->where(['pump_id' => '[0-9]+', 'pump_operator_id' => '[0-9]+'])
    ->name('petrogeneral.pump_operator_actions.assignment.get');
Route::get('/pump-operator-actions/confirm-pumps/{assignment_id}', 'Pumper\\PumperAssignmentConfirmController@show')
    ->where('assignment_id', '[0-9]+')
    ->name('petrogeneral.pump_operator_actions.confirm_pumps.show');
Route::post('/pump-operator-actions/confirm-pumps/{assignment_id}', 'Pumper\\PumperAssignmentConfirmController@store')
    ->where('assignment_id', '[0-9]+')
    ->name('petrogeneral.pump_operator_actions.confirm_pumps.store');

Route::get('/pump-operator-actions/get-day-entry-summary', 'Pumper\\PumperDayEntrySummaryController@dayEntry')
    ->name('petrogeneral.pump_operator_actions.day_entry_summary');
Route::get('/pump-operator-actions/get-closing-shift-summary', 'Pumper\\PumperDayEntrySummaryController@closingShift')
    ->name('pump-operator-actions.get-closing-shift-summary');

Route::resource('/pump-operators/shift-summary', 'ShiftSummaryController');

// PUMPER-MGMT-LIVE-ROUTE-20260821: register static endpoints before the resource.
Route::get('/pump-operators/pumper-day-entries/get-daily-collection', 'PumperDayEntry\PumperDayEntryDailyCollectionController@getDailyCollection')
    ->name('petrogeneral.pumper_day_entries.daily_collection');
Route::get('/pump-operators/pumper-day-entries/add-settlement-no/{id}', 'PumperDayEntryController@getAddSettlementNo')->where('id', '[0-9]+');
Route::post('/pump-operators/pumper-day-entries/add-settlement-no/{id}', 'PumperDayEntryController@postAddSettlementNo')->where('id', '[0-9]+');
Route::get('/pump-operators/pumper-day-entries/view-settlement-no/{id}', 'PumperDayEntryController@viewAddSettlementNo')->where('id', '[0-9]+');

Route::resource('/pump-operators/pumper-day-entries', 'PumperDayEntryController');
Route::resource('/pump-operator-assignment', 'PumpOperatorAssignmentController');
Route::resource('/pump-operators', 'PumpOperatorController');
