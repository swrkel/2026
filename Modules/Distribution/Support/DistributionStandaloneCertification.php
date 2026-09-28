<?php

namespace Modules\Distribution\Support;

/**
 * Lightweight helper to classify dependency findings during standalone audits.
 * Does not alter runtime business logic.
 */
class DistributionStandaloneCertification
{
    public static function classify(string $reference): string
    {
        $allowedPrefixes = [
            'Illuminate\\', 'Laravel\\', 'Carbon\\', 'Yajra\\', 'DB', 'Auth', 'Log', 'Route', 'Request', 'Response',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if (str_starts_with($reference, $prefix)) {
                return 'framework_allowed';
            }
        }

        if (str_starts_with($reference, 'Modules\\Distribution\\')) {
            return 'distribution_owned';
        }

        if (str_starts_with($reference, 'App\\') || str_starts_with($reference, '\\App\\')) {
            return 'main_erp_dependency';
        }

        if (str_starts_with($reference, 'Modules\\') && ! str_starts_with($reference, 'Modules\\Distribution\\')) {
            return 'cross_module_dependency';
        }

        return 'review_required';
    }
}
