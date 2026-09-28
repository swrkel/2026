<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Poultry\Support\BusinessContext;

/**
 * Base controller for the module.
 *
 * Extends Illuminate's Controller directly rather than App\Http\Controllers\Controller,
 * keeping the module free of core imports. Permission checking uses Laravel's
 * Gate, which the application's existing permission package already backs -
 * so roles configured on the Roles screen work here with no extra wiring.
 */
abstract class PoultryBaseController extends Controller
{
    protected function businessId()
    {
        return BusinessContext::requireId();
    }

    protected function locationId()
    {
        return BusinessContext::locationId();
    }

    /**
     * Abort unless the user holds the permission. Keys match
     * Config/module_permissions.php.
     */
    protected function authorizePermission($permission)
    {
        if (! auth()->check()) {
            abort(401);
        }

        // Superadmin style bypass, consistent with the rest of the ERP.
        if (method_exists(auth()->user(), 'can') && auth()->user()->can('superadmin')) {
            return true;
        }

        if (! auth()->user()->can($permission)) {
            abort(403, 'Unauthorised action.');
        }

        return true;
    }

    /** Consistent JSON envelope for the ajax endpoints. */
    protected function ok($message, array $extra = [])
    {
        return response()->json(array_merge([
            'success' => true,
            'msg'     => $message,
        ], $extra));
    }

    protected function fail($message, array $extra = [])
    {
        return response()->json(array_merge([
            'success' => false,
            'msg'     => $message,
        ], $extra));
    }
}
