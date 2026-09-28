<?php

namespace Modules\MembershipNew\app\Providers;

use Illuminate\Support\Facades\Route;

class MembershipNewRouteLoader
{
    public static function load(): void
    {
        $routesPath = module_path('MembershipNew', 'routes');

        // Start with the explicitly configured route order.
        $routeFiles = config('membershipnew.route_files', ['web.php', 'central_registry.php']);

        // Do not allow a newer Membership-New parcel to be installed while its
        // versioned route file is accidentally omitted from an older cached list.
        $versionedFiles = glob($routesPath . '/memnew_*.php') ?: [];
        natsort($versionedFiles);
        $versionedFiles = array_map('basename', $versionedFiles);

        $routeFiles = array_values(array_unique(array_merge(
            ['web.php', 'central_registry.php'],
            is_array($routeFiles) ? $routeFiles : [],
            $versionedFiles
        )));

        foreach ($routeFiles as $file) {
            $path = $routesPath . DIRECTORY_SEPARATOR . $file;

            if (is_file($path)) {
                // Every Membership-New route file owns its own middleware/prefix/name group.
                // Group the file only to let Laravel register it; do not add a second
                // tenant/business middleware layer here.
                Route::group([], $path);
            }
        }
    }
}
