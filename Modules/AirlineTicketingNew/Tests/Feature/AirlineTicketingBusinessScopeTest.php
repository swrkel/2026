<?php

namespace Modules\AirlineTicketingNew\Tests\Feature;

use Tests\TestCase;

class AirlineTicketingBusinessScopeTest extends TestCase
{
    public function test_business_session_is_required_for_module_access(): void
    {
        $response = $this->get('/airline-ticketing-new');
        $response->assertStatus(403);
    }
}
