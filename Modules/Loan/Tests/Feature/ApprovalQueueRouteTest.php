<?php

namespace Modules\Loan\Tests\Feature;

use Tests\TestCase;

class ApprovalQueueRouteTest extends TestCase
{
    /** @test */
    public function loan_approval_queue_route_is_registered()
    {
        $route = app('router')->getRoutes()->getByName('loan.approval_queue.index');

        $this->assertNotNull($route);
    }
}
