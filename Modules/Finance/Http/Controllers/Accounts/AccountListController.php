<?php

namespace Modules\Finance\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;

class AccountListController extends Controller
{
    public function index()
    {
        return view('finance::accounts.index');
    }
}
