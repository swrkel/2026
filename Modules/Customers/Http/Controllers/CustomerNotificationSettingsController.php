<?php

namespace Modules\Customers\Http\Controllers;

class CustomerNotificationSettingsController extends CustomerConfigurationBaseController
{
    protected $section = 'notifications';
    protected $title = 'Customer Notification Settings';
    protected $route = 'customers.settings.notifications.index';
}
