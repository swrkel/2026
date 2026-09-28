<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['middleware' => ['web', 'auth', 'tenant.context'], 'prefix' => 'DocManagement'], function () {
        Route::get('documet', 'DocManagementController@index');
        Route::get('doc_settings', 'DocManagementSettingsController@index');
        Route::get('document_category_gets', 'DocManagementSettingsController@doc_category_gets');
        Route::get('document_department_gets', 'DocManagementSettingsController@doc_department_gets');
        Route::get('document_designation_gets', 'DocManagementSettingsController@doc_designation_gets');
        Route::get('document_type_gets', 'DocManagementSettingsController@doc_type_gets');
        Route::get('document_purpose_gets', 'DocManagementSettingsController@doc_purpose_gets');
        Route::get('document_forwardwith_gets', 'DocManagementSettingsController@doc_forwardwith_get');
        Route::get('document_status_gets', 'DocManagementSettingsController@doc_status_gets');
        Route::get('document_mandatorysignature_gets', 'DocManagementSettingsController@doc_mandatorysignature_gets');
        Route::get('document_upload_gets', 'DocManagementSettingsController@doc_upload_gets');
        Route::get('document_uploadlogo_gets', 'DocManagementSettingsController@doc_uploadlogo_gets');
        Route::get('document_referred_to_gets', 'DocManagementSettingsController@doc_referred_to_gets');
        Route::get('document_referred_to_status_history_gets', 'DocManagementSettingsController@doc_referred_to_status_history_gets');
        Route::get('document_designations_by_department', 'DocManagementSettingsController@getDesignationsByDepartment');
          Route::get('document_purpose_gets', 'DocManagementSettingsController@document_purpose_gets');
        Route::post('store_type', 'DocManagementSettingsController@store_type');
        Route::post('store_purpose', 'DocManagementSettingsController@store_purpose');
        Route::post('store_signature_upload', 'DocManagementSettingsController@store_signatures');
        Route::post('store_logo', 'DocManagementSettingsController@store_logo');
        Route::post('store_designation', 'DocManagementSettingsController@store_designation');
        Route::get('store_mandatorySignature', 'DocManagementSettingsController@store_mandatorySignature');
        Route::get('store_forwardwith', 'DocManagementSettingsController@store_forwardwith');
        Route::get('store_doc_status', 'DocManagementSettingsController@store_doc_status');
        Route::post('store_referred_to', 'DocManagementSettingsController@store_referred_to');
        Route::get('update_referred_to_status', 'DocManagementSettingsController@update_referred_to_status');
        Route::get('store_category_type', 'DocManagementSettingsController@store_category');
        Route::get('store_department', 'DocManagementSettingsController@store_department');
        Route::get('store_designation', 'DocManagementSettingsController@store_designation');
        Route::post('store_department', 'DocManagementSettingsController@store_department');
        Route::get('documet', 'DocManagementController@index');
        Route::get('show', 'DocManagementController@show_status');
        Route::get('create', 'DocManagementController@show');  
        Route::get('view/{doc_no}', 'DocManagementController@viewDoc')->name('doc.view');
        Route::get('download/{doc_no}/{index}', 'DocManagementController@downloadAttachment')->name('doc.download');
        Route::get('edit/{doc_no}', 'DocManagementController@editDoc')->name('doc.edit');
        Route::get('print/{doc_no}', 'DocManagementController@printDoc')->name('doc.print');
        Route::post('update/{doc_no}', 'DocManagementController@updateDoc')->name('doc.update');
        Route::POST('store_upload', 'DocManagementController@store');  
          Route::POST('update_referred', 'DocManagementController@update_referred'); 
        Route::get('get_upload_table', 'DocManagementController@get_upload_table'); 
});
