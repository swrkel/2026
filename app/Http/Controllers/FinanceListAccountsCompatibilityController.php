<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Tenant-safe compatibility endpoint for standalone Finance > List Accounts.
 *
 * The mature account screen still lives in AccountController on installations
 * where the Finance module does not register its own /finance/account route.
 * Keeping the route under the Finance prefix lets the central module guards
 * recognise the correct parent without duplicating any account business logic.
 */
class FinanceListAccountsCompatibilityController extends Controller
{
    public function __invoke(Request $request, AccountController $accountController)
    {
        return $accountController->index($request);
    }
}
