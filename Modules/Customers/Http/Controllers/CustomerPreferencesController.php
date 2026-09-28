<?php

namespace Modules\Customers\Http\Controllers;

class CustomerPreferencesController extends CustomerConfigurationBaseController
{
    protected $section = 'preferences';
    protected $title = 'Customer Preferences';
    protected $route = 'customers.settings.preferences.index';
}
