<?php

namespace Modules\LeadsNew\Providers;

/**
 * Compatibility service provider.
 *
 * Some deployments of this ERP load standalone modules using the generic
 * Providers\ServiceProvider class name. The Customers module also has this
 * file. Keep this class as a thin wrapper so Leads-New follows the same
 * bootstrap contract without introducing changes to the host application's
 * main route files.
 */
class ServiceProvider extends LeadsNewServiceProvider
{
    // All boot/register logic lives in LeadsNewServiceProvider.
}
