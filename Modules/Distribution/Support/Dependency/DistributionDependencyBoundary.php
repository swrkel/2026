<?php

namespace Modules\Distribution\Support\Dependency;

/**
 * Central boundary for the few ERP-level dependencies still required during
 * the Distribution standalone migration. Keeping these calls here makes the
 * remaining dependencies visible and easier to replace safely in later stages.
 */
class DistributionDependencyBoundary
{
    public static function appModel(string $class): string
    {
        return '\\App\\' . ltrim($class, '\\');
    }

    public static function controllerAction(string $distributionControllerAction, array $parameters = []): string
    {
        return action('\\Modules\\Distribution\\Http\\Controllers\\' . ltrim($distributionControllerAction, '\\'), $parameters);
    }

    public static function route(string $name, array $parameters = []): string
    {
        if (strpos($name, 'distribution.') !== 0) {
            $name = 'distribution.' . ltrim($name, '.');
        }

        return route($name, $parameters);
    }
}
