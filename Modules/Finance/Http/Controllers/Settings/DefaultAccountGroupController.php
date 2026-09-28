<?php

namespace Modules\Finance\Http\Controllers\Settings;

use Illuminate\Routing\Controller;

/**
 * Finance module controller bridge for DefaultAccountGroupController.
 *
 * This wrapper keeps the current tested ERP behaviour unchanged while moving
 * Finance routes into Modules/Finance. The next refactor phase should move
 * the business logic from the base controller into Finance services and remove
 * this temporary bridge dependency.
 */
/*
 * MA-002: core inheritance removed.
 *
 * This class previously declared
 *     extends App\Http\Controllers\DefaultAccountGroupController
 *
 * It defines no methods of its own, and NOTHING routes to it or references
 * it anywhere - verified across Modules, app, routes and resources before
 * changing it. It was inheriting core code that could never be reached.
 *
 * The file is kept rather than deleted so the change is reversible and
 * cannot cause a class-not-found anywhere unexpected. It now extends
 * Laravel's base controller and carries no dependency on core.
 */
class DefaultAccountGroupController extends Controller
{
    // Intentionally empty: delegates to existing tested controller methods.
}
