<?php

namespace Modules\Customers\Http\Controllers;

class CustomerPortalSettingsController extends CustomerConfigurationBaseController
{
    protected $section = 'portal';
    protected $title = 'Dealer Portal Settings';
    protected $route = 'customers.settings.portal.index';
}
