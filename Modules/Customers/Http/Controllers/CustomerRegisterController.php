<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Http\Request;

/**
 * CUS_SEP_007
 * Customer register entry controller.
 *
 * This keeps the register URL owned by the Customers module. For safety, it
 * delegates to the existing Customers module CustomerController for the actual
 * grid/data behavior so no working register functionality is changed.
 */
class CustomerRegisterController extends CustomerController
{
    public function index(Request $request)
    {
        return parent::index($request);
    }
}
