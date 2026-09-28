<?php

namespace Modules\PetroPD\Tests\Unit;

use Modules\PetroPD\Services\PetroPdPaymentReconciliationIssueMatcher;
use PHPUnit\Framework\TestCase;

class PetroPdPaymentReconciliationIssueMatcherTest extends TestCase
{
    public function test_recheck_matches_the_exact_issue_and_payment_identity(): void
    {
        $matcher = new PetroPdPaymentReconciliationIssueMatcher();
        $issues = [
            ['type' => 'detail_amount_mismatch', 'pump_payment_id' => 101],
            ['type' => 'detail_amount_mismatch', 'pump_payment_id' => 102],
            ['type' => 'shift_mismatch', 'pump_payment_id' => 101],
        ];

        $matches = $matcher->matchingIssues($issues, 'detail_amount_mismatch', 101);

        $this->assertCount(1, $matches);
        $this->assertSame(101, $matches[0]['pump_payment_id']);
        $this->assertFalse($matcher->isResolved($issues, 'detail_amount_mismatch', 101));
        $this->assertTrue($matcher->isResolved($issues, 'detail_amount_mismatch', 999));
    }

    public function test_shift_level_event_matches_by_issue_type_when_payment_id_is_absent(): void
    {
        $matcher = new PetroPdPaymentReconciliationIssueMatcher();
        $issues = [
            ['type' => 'multiple_shift_scope'],
            ['type' => 'missing_detail_link', 'pump_payment_id' => 50],
        ];

        $this->assertFalse($matcher->isResolved($issues, 'multiple_shift_scope'));
        $this->assertTrue($matcher->isResolved($issues, 'operator_scope_mismatch'));
    }
}
