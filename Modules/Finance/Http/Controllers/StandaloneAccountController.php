<?php

namespace Modules\Finance\Http\Controllers;

/**
 * Backward-compatible Finance account controller alias.
 *
 * Some older route caches / installations still point Finance URLs such as
 * /finance/account/{id} and /finance/deposit/{id} at this class.  The old
 * implementation extended the CORE AccountController and then remapped its
 * views into the Finance namespace.  That mixed two different controller
 * contracts: the core controller did not supply all variables expected by the
 * Finance views (for example the Account Book date-range variables and the
 * Cash/Card Deposit form data), causing HTTP 500 errors.
 *
 * Keep the class name for compatibility, but inherit the authoritative Finance
 * controller directly.  Every legacy route now executes the same Finance-owned
 * methods, data preparation and views as the current routes.
 */
class StandaloneAccountController extends AccountController
{
    // Intentionally empty: all behaviour is inherited from Finance AccountController.
}
