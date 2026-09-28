<?php

namespace Modules\AirlineTicketingNew\Tests\Feature;

use Tests\TestCase;

class AirlineTicketingIsolationTest extends TestCase
{
    public function test_module_routes_use_exclusive_name_prefix(): void
    {
        $routes = collect(app('router')->getRoutes())->filter(
            fn ($route) => str_starts_with((string)$route->getName(), 'airline-ticketing-new.')
        );

        $this->assertNotEmpty($routes);
        $this->assertTrue($routes->every(
            fn ($route) => str_starts_with((string)$route->uri(), 'airline-ticketing-new')
        ));
    }
}
