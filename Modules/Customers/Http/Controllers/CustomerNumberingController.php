<?php

namespace Modules\Customers\Http\Controllers;

class CustomerNumberingController extends CustomerConfigurationBaseController
{
    protected $section = 'numbering';
    protected $title = 'Customer Numbering';
    protected $route = 'customers.settings.numbering.index';
}
