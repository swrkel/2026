<?php

namespace Modules\Loan\Tests\Unit;

use Modules\Loan\Services\LoanApprovalWorkflowService;
use PHPUnit\Framework\TestCase;

class LoanApprovalWorkflowServiceTest extends TestCase
{
    /** @test */
    public function it_exposes_standard_banking_workflow_statuses()
    {
        $service = new LoanApprovalWorkflowService();

        $statuses = $service->statuses();

        $this->assertArrayHasKey('draft', $statuses);
        $this->assertArrayHasKey('submitted', $statuses);
        $this->assertArrayHasKey('under_review', $statuses);
        $this->assertArrayHasKey('approved', $statuses);
        $this->assertArrayHasKey('rejected', $statuses);
    }
}
