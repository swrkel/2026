<?php

namespace Modules\PetroPD\Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPdPaymentIntegrityReadinessContractTest extends TestCase
{
    public function test_readiness_service_checks_master_identity_shift_and_detail_links(): void
    {
        $path = dirname(__DIR__, 2) . '/Services/PetroPdPaymentIntegrityReadinessService.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString("'pump_operator_payments'", $source);
        self::assertStringContainsString("'shift_id'", $source);
        self::assertStringContainsString("'pump_payment_id'", $source);
        self::assertStringContainsString('duplicate_master_source_identity', $source);
        self::assertStringContainsString('unresolved_critical_events', $source);
        self::assertStringContainsString('exact_scope_snapshot', $source);
    }

    public function test_provider_registers_non_destructive_verification_command(): void
    {
        $path = dirname(__DIR__, 2) . '/Providers/PetroPDServiceProvider.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('VerifyPetroPdPaymentIntegrity::class', $source);
    }

    public function test_settlement_controller_has_server_side_finalization_lock(): void
    {
        $path = dirname(__DIR__, 2) . '/Http/Controllers/PetroPDSettlementController.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('petropd:settlement-finalize:', $source);
        self::assertStringContainsString('$finalizationLock->get()', $source);
        self::assertStringContainsString('$finalizationLock->release()', $source);
    }

    public function test_add_payment_form_sends_the_reviewed_snapshot_fingerprint(): void
    {
        $path = dirname(__DIR__, 2) . '/Resources/views/pd_settlement/partials/add_payment.blade.php';
        $source = file_get_contents($path);

        self::assertIsString($source);
        self::assertStringContainsString('payment_snapshot_fingerprint', $source);
        self::assertStringContainsString("$('#payment_snapshot_fingerprint').val()", $source);
    }
}
