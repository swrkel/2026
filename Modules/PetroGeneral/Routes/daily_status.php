<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| PG016 - Petro General Daily Status routes
|--------------------------------------------------------------------------
| These routes split Daily Status ajax/print/pdf actions into small wrapper
| controllers. Existing DailyStatusReportController business logic is not
| changed in this package. This keeps the module safer while reducing route
| dependency on the large legacy controller.
*/

/*
| IS2021: /daily-status-general now serves the WORKING report page.
|
| There were two Daily Status pages in this module:
|
|   daily_status_report/index.blade.php - the complete one. 728 lines, with the
|                                         filters, the datatables and all the
|                                         AJAX endpoints behind them, driven by
|                                         DailyStatusReportController.
|
|   daily_status/index.blade.php        - a newer shell. Its four tabs -
|                                         Summary, Sales, Payments, Stock - were
|                                         four-line placeholders reading "This
|                                         tab is separated for Petro General
|                                         maintenance". No filters, no tables,
|                                         no data.
|
| This URL pointed at the SHELL, which is why the report pages did not work.
|
| Same situation as Dip Management (S665 follow-up): rather than rebuild four
| tabs that already exist and work, the route now renders the complete page.
|
| The route NAME is unchanged so the sidebar links keep working, and the other
| routes in this file - get-pump-sales, get-fuel-sale and the rest - are
| untouched, since the working page calls those same endpoints.
|
| The daily_status shell views and DailyStatusIndexController are left in place,
| unused, rather than deleted: they are somebody's partial rebuild and are not
| mine to remove. Nothing routes to them now.
*/
Route::get('/daily-status-general', 'DailyStatusReportController@index')
    ->name('petrogeneral.daily_status.index');

Route::get('/get-pump-sales', 'DailyStatus\\PumpSalesController@index')
    ->name('petrogeneral.daily_status.get_pump_sales');
Route::get('/get-fuel-sale', 'DailyStatus\\FuelSaleController@index')
    ->name('petrogeneral.daily_status.get_fuel_sale');
Route::get('/get-lubricant-sale', 'DailyStatus\\LubricantSaleController@index')
    ->name('petrogeneral.daily_status.get_lubricant_sale');
Route::get('/get-other-sale', 'DailyStatus\\OtherSaleController@index')
    ->name('petrogeneral.daily_status.get_other_sale');
Route::get('/get-gas-sale', 'DailyStatus\\GasSaleController@index')
    ->name('petrogeneral.daily_status.get_gas_sale');
Route::get('/get-credit-sale', 'DailyStatus\\CreditSaleController@index')
    ->name('petrogeneral.daily_status.get_credit_sale');
Route::get('/get-total-payments', 'DailyStatus\\TotalPaymentsController@index')
    ->name('petrogeneral.daily_status.get_total_payments');
Route::get('/print-report', 'DailyStatus\\PrintReportController@print')
    ->name('petrogeneral.daily_status.print_report');
Route::post('/download-pdf', 'DailyStatus\\DownloadPdfController@download')
    ->name('petrogeneral.daily_status.download_pdf');
