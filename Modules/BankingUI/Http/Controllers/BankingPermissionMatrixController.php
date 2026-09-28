<?php

namespace Modules\BankingUI\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingUI\Support\BankingMenuMatrix;

class BankingPermissionMatrixController extends Controller
{
    public function index()
    {
        return view('bankingui::permissions.matrix', [
            'menuItems' => BankingMenuMatrix::items(),
            'roles' => config('bankingui_roles', []),
            'permissions' => config('bankingui_permissions.permissions', []),
        ]);
    }
}
