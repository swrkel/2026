<?php 
Route::prefix('helpguide')->group(function() {
    Route::get('/', 'Install\InstallController@index')->name('helpguide.install.index');
    Route::any('/requirements', 'Install\InstallController@requirements')->name('helpguide.install.requirements');
    Route::any('/folder_permissions', 'Install\InstallController@folderPermissions')->name('helpguide.install.folder_permissions');
    Route::any('/product_license', 'Install\InstallController@productLicense')->name('helpguide.install.product_license');
    Route::any('/database', 'Install\InstallController@database')->name('helpguide.install.database');
    Route::any('/admin_account', 'Install\InstallController@createUser')->name('helpguide.install.admin_account');
    Route::any('/finish', 'Install\InstallController@finish')->name('helpguide.install.finish');
});
