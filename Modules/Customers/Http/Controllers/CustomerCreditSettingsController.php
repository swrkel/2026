<?php

namespace Modules\Customers\Http\Controllers;

class CustomerCreditSettingsController extends CustomerConfigurationBaseController
{
    protected $section = 'credit';
    protected $title = 'Customer Credit Settings';
    protected $route = 'customers.settings.credit.index';
}
