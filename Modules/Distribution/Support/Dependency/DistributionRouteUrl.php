<?php

namespace Modules\Distribution\Support\Dependency;

class DistributionRouteUrl
{
    public static function route(string $name, array $parameters = []): string
    {
        return DistributionDependencyBoundary::route($name, $parameters);
    }

    public static function controller(string $controllerAction, array $parameters = []): string
    {
        return DistributionDependencyBoundary::controllerAction($controllerAction, $parameters);
    }
}
