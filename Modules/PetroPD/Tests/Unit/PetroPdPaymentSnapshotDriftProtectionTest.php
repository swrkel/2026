<?php

namespace Modules\PetroPD\Tests\Unit;

use Modules\PetroPD\Services\PetroPdSettlementPaymentSnapshotService;
use Modules\PetroPD\Services\PumpOperatorPaymentAuthorityService;
use PHPUnit\Framework\TestCase;

class PetroPdPaymentSnapshotDriftProtectionTest extends TestCase
{
    private function service(): PetroPdSettlementPaymentSnapshotService
    {
        return new PetroPdSettlementPaymentSnapshotService(
            new PumpOperatorPaymentAuthorityService()
        );
    }

    public function test_matching_review_and_finalization_fingerprints_are_accepted(): void
    {
        self::assertTrue($this->service()->fingerprintMatches('abc123', 'abc123'));
    }

    public function test_changed_payment_snapshot_is_rejected(): void
    {
        self::assertFalse($this->service()->fingerprintMatches('reviewed-fingerprint', 'changed-fingerprint'));
    }

    public function test_blank_fingerprint_remains_backward_compatible_for_non_modal_callers(): void
    {
        self::assertTrue($this->service()->fingerprintMatches(null, 'current-fingerprint'));
        self::assertTrue($this->service()->fingerprintMatches('', 'current-fingerprint'));
    }
}
