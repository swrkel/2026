<?php

namespace Modules\Customers\Http\Controllers;

class CustomerDefaultsController extends CustomerConfigurationBaseController
{
    protected $section = 'defaults';
    protected $title = 'Customer Defaults';
    protected $route = 'customers.settings.defaults.index';
}
