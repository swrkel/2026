<?php

namespace Modules\PetroPD\Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPdPaymentReadModelContractTest extends TestCase
{
    public function test_payment_summary_and_day_entry_paths_do_not_join_details_by_amount(): void
    {
        $moduleRoot = dirname(__DIR__, 2);
        $files = [
            $moduleRoot . '/Http/Controllers/PDPumpOperatorPaymentController.php',
            $moduleRoot . '/Http/Controllers/PDPumperDayEntryController.php',
            $moduleRoot . '/Services/SettlementPaymentQueryService.php',
        ];

        $source = implode("\n", array_map(
            static fn (string $file): string => (string) file_get_contents($file),
            $files
        ));

        self::assertStringNotContainsString("scsp.amount = pop.payment_amount", $source);
        self::assertStringNotContainsString("scp.amount'), '=', DB::raw('pop.payment_amount", $source);
        self::assertStringNotContainsString("dc.amount'), '=', DB::raw('pump_operator_payments.payment_amount", $source);
        self::assertStringContainsString('paymentSummaryBaseQuery', $source);
        self::assertStringContainsString('totalsForScope', $source);
    }

    public function test_credit_metadata_is_collapsed_by_authoritative_payment_id(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/Services/SettlementPaymentQueryService.php'
        );

        self::assertStringContainsString("->groupBy('business_id', 'pump_payment_id')", $source);
        self::assertStringContainsString('COUNT(DISTINCT ', $source);
        self::assertStringContainsString('one row per master payment', strtolower($source));
    }
}
