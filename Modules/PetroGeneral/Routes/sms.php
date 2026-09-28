<?php

use Illuminate\Support\Facades\Route;

Route::get('/sms-notifications-general', 'SMS\\NotificationListController@index')
    ->name('petrogeneral.sms_notifications.index');
Route::get('/sms-notifications-general/create', 'SMS\\NotificationSendController@create')
    ->name('petrogeneral.sms_notifications.create');
Route::get('/sms-notifications-general/sms-templates', 'SMS\\NotificationTemplateController@smsTemplates')
    ->name('petrogeneral.sms_notifications.sms_templates');
Route::get('/sms-notifications-general/whatsapp-templates', 'SMS\\NotificationTemplateController@whatsappTemplates')
    ->name('petrogeneral.sms_notifications.whatsapp_templates');
