<?php

Route::group(['middleware' => ['web', 'tenant.context'], 'prefix' => 'membership', 'namespace' => 'Modules\Membership\Http\Controllers'], function () {
    Route::group(['prefix' => 'setting'], function () {
        Route::middleware('permission:add_membership_settings')->group(function () {
            Route::get('/business-types/create', 'MembershipSettingController@createBusinessType')->name('membership.setting.business-types.create');
            Route::post('/business-types', 'MembershipSettingController@storeBusinessType')->name('membership.setting.business-types.store');

            Route::get('/point-settings/create', 'MembershipSettingController@createPointSetting');
            Route::post('/point-settings', 'MembershipSettingController@storePointSetting');

            Route::get('/card-settings/create', 'MembershipSettingController@createCardSetting');
            Route::post('/card-settings', 'MembershipSettingController@storeCardSetting');

            Route::get('/signatures/create', 'Settings\AuthorizedSignatureController@create')->name('membership.setting.authorized-signatures.create');
            Route::post('/signatures', 'Settings\AuthorizedSignatureController@store')->name('membership.setting.authorized-signatures.store');

            Route::post('/membership-settings', 'MembershipSettingController@storeMembershipSetting');

            Route::get('/business-names/create', 'MembershipSettingController@createBusinessName');
            Route::post('/business-name', 'MembershipSettingController@storeBusinessName')->name('membership.setting.business-name.store');
        });

        Route::middleware('permission:edit_membership_settings|membership.settings_page')->group(function () {
            Route::get('/', 'MembershipSettingController@index');
            Route::get('/business-types', 'MembershipSettingController@getBusinessTypes')->name('membership.setting.business-types.index');
            Route::get('/business-types/{id}', 'MembershipSettingController@viewBusinessType')->name('membership.setting.business-types.show');
            Route::get('/business-types/{id}/edit', 'MembershipSettingController@editBusinessType')->name('membership.setting.business-types.edit');
            Route::get('/business-types/{id}/users', 'MembershipSettingController@getUsersByBusinessType')->name('membership.setting.business-types.users');

            Route::get('/point-settings', 'MembershipSettingController@getPointSettings');
            Route::get('/point-settings/{id}', 'MembershipSettingController@showPointSetting');
            Route::get('/point-settings/{id}/edit', 'MembershipSettingController@editPointSetting');

            Route::get('/card-settings', 'MembershipSettingController@getCardSettings');
            Route::get('/card-settings/{id}', 'MembershipSettingController@viewCardSetting');
            Route::get('/card-settings/{id}/edit', 'MembershipSettingController@editCardSetting');

            Route::get('/signatures', 'Settings\AuthorizedSignatureController@index')->name('membership.setting.authorized-signatures.index');
            Route::get('/signatures/{id}', 'Settings\AuthorizedSignatureController@show')->name('membership.setting.authorized-signatures.show');
            Route::get('/signatures/{id}/edit', 'Settings\AuthorizedSignatureController@edit')->name('membership.setting.authorized-signatures.edit');

            Route::get('/membership-settings/{id}', 'MembershipSettingController@viewMembershipSetting');
            Route::get('/membership-settings/{id}/edit', 'MembershipSettingController@editMembershipSetting');

            Route::get('/business-name/data', 'MembershipSettingController@getBusinessNames')->name('membership.setting.business-name.data');
            Route::get('/business-names/{id}', 'MembershipSettingController@viewBusinessName');
            Route::get('/business-names/{id}/edit', 'MembershipSettingController@editBusinessName');
        });

        Route::middleware('permission:edit_membership_settings')->group(function () {
            Route::put('/business-types/{id}', 'MembershipSettingController@updateBusinessType')->name('membership.setting.business-types.update');
            Route::put('/business-types/{id}/disable', 'MembershipSettingController@disableBusinessType')->name('membership.setting.business-types.disable');
            Route::delete('/business-types/{id}', 'MembershipSettingController@destroyBusinessType')->name('membership.setting.business-types.destroy');

            Route::put('/point-settings/{id}', 'MembershipSettingController@updatePointSetting');
            Route::delete('/point-settings/{id}', 'MembershipSettingController@destroyPointSetting');

            Route::put('/card-settings/{id}', 'MembershipSettingController@updateCardSetting');
            Route::delete('/card-settings/{id}', 'MembershipSettingController@destroyCardSetting');

            Route::put('/signatures/{id}', 'Settings\AuthorizedSignatureController@update')->name('membership.setting.authorized-signatures.update');
            Route::delete('/signatures/{id}', 'Settings\AuthorizedSignatureController@destroy')->name('membership.setting.authorized-signatures.destroy');

            Route::put('/membership-settings/{id}', 'MembershipSettingController@updateMembershipSetting');
            Route::delete('/membership-settings/{id}', 'MembershipSettingController@destroyMembershipSetting');

            Route::put('/business-names/{id}', 'MembershipSettingController@updateBusinessName');
            Route::delete('/business-names/{id}', 'MembershipSettingController@destroyBusinessName');
        });
    });

    Route::middleware('permission:edit_membership_settings|membership.settings_page')->group(function () {
        Route::get('membership/settings', 'MembershipSettingController@editMembershipSettings')->name('membership.settings');
    });
    Route::middleware('permission:edit_membership_settings')->group(function () {
        Route::post('membership/settings', 'MembershipSettingController@updateMembershipSettings')->name('membership.settings.update');
    });
    Route::middleware('permission:add_membership_settings')->group(function () {
        Route::post('membership/settings/business-name', 'MembershipSettingController@storeBusinessName')->name('membership.settings.business.name');
    });

    Route::middleware('permission:add_membership_settings|edit_membership_settings|membership.settings_page')->group(function () {
        Route::get('/setting/get-business-names', 'MembershipSettingController@getBusinessNames');
    });

    Route::middleware('permission:edit_member|membership.list_members_page')->group(function () {
        Route::get('/members', 'MembershipController@getMembers');
    });

    Route::middleware('permission:membership.membership_activities_page')->group(function () {
        Route::get('/members/activities', 'MembershipController@getMembershipActivities');
    });

    Route::middleware('permission:add_member|membership.add_member_page')->group(function () {
        Route::get('/members/create', 'MembershipController@createMember');
        Route::get('/next-member-number', 'MembershipController@getNextMemberNumber');
        Route::get('/membership-types/search', 'MembershipController@searchMembershipTypes');
        Route::get('/membership-statuses/search', 'MembershipController@searchMembershipStatuses');
        Route::get('/members/search', 'MembershipController@searchMembers')->name('membership.members.search');
    });

    Route::middleware('permission:add_member')->group(function () {
        Route::post('/members', 'MembershipController@storeMember');
        Route::post('/membership-types', 'MembershipController@storeMembershipType');
        Route::post('/membership-statuses', 'MembershipController@storeMembershipStatus');
    });

    Route::middleware('permission:membership.add_points_page')->group(function () {
        Route::get('/add-points', 'MembershipPointController@addPointForm');
        Route::post('/add-points', 'MembershipPointController@addPoint');
    });

    Route::middleware('permission:membership.point_activities_page')->group(function () {
        Route::get('/list-points', 'MembershipPointController@getListPoints');
        Route::get('/member-points', 'MembershipPointController@getMemberPoints');
        Route::get('/get-point-balance-for-edit', 'MembershipPointController@getPointBalanceForEdit');
        Route::get('/member-bills', 'MembershipPointController@getMemberBills');
        Route::get('/get-reward-percent', 'MembershipPointController@getRewardPercent');
        Route::get('/get-business-names-by-type', 'MembershipPointController@getBusinessNamesByType');
    });

    Route::middleware('permission:edit_member')->group(function () {
        Route::get('/members/{id}', 'MembershipController@viewMember');
        Route::get('/members/{id}/edit', 'MembershipController@editMember');
        Route::get('/members/{id}/renewal', 'MembershipController@showRenewalModal');
        Route::post('/members/{id}/renewal', 'MembershipController@storeRenewal');
        Route::put('/members/{id}', 'MembershipController@updateMember');
        Route::delete('/members/{id}', 'MembershipController@destroyMember');
        Route::get('/members/{id}/ledger', 'MembershipController@viewMemberLedger');
        Route::get('/members/{id}/ledger-detail', 'MembershipController@getMemberLedger');
        Route::get('/members/{id}/print-card', 'MembershipController@printCard');

        Route::get('/edit-point/{id}', 'MembershipPointController@editPointForm')->name('membership.edit_point');
        Route::put('/update-points/{id}', 'MembershipPointController@updatePoint')->name('membership.update-points');
        Route::get('/view-point/{id}', 'MembershipPointController@viewPoint')->name('membership.view_point');
        Route::delete('/delete-point/{id}', 'MembershipPointController@destroyPoint')->name('membership.delete_point');
    });

    Route::middleware('permission:add_dividends|membership.add_dividends_page')->group(function () {
        Route::get('/dividends/add', 'DividendController@addDividends');
        Route::get('/dividends/filtered-members', 'DividendController@getFilteredMembers');
    });

    Route::middleware('permission:add_dividends')->group(function () {
        Route::post('/dividends/store', 'DividendController@storeDividends');
    });

    Route::middleware('permission:add_dividends|checked_by|approved_by|edit_dividends|membership.add_dividends_page|membership.list_dividends_page')->group(function () {
        Route::get('/dividends/check-lock-status', 'DividendController@checkLockStatus');
    });

    Route::middleware('permission:checked_by')->group(function () {
        Route::post('/dividends/mark-as-checked', 'DividendController@markAsChecked');
    });

    Route::middleware('permission:approved_by')->group(function () {
        Route::post('/dividends/mark-as-approved', 'DividendController@markAsApproved');
    });

    Route::middleware('permission:edit_dividends|membership.list_dividends_page')->group(function () {
        Route::get('/dividends/list', 'DividendController@listDividends');
        Route::get('/dividends/last', 'DividendController@getLastDividends');
        Route::get('/dividends/issued', 'DividendController@getIssuedDividends');
    });
});
