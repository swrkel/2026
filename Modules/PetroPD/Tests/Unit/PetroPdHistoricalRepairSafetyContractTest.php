<?php

namespace Modules\PetroPD\Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPdHistoricalRepairSafetyContractTest extends TestCase
{
    public function test_safe_repair_sql_never_deletes_financial_rows(): void
    {
        $sql = strtoupper((string) file_get_contents(
            dirname(__DIR__, 2) . '/Database/sql/PETROPD_PAYMENT_INTEGRITY_SAFE_REPAIR_PARCEL_3_20260723.sql'
        ));

        self::assertStringNotContainsString('DELETE FROM', $sql);
        self::assertStringContainsString('START TRANSACTION', $sql);
        self::assertStringContainsString('COMMIT', $sql);
        self::assertStringContainsString('HAVINGCOUNT(*)=1', preg_replace('/\s+/', '', $sql));
    }

    public function test_repair_service_refuses_competing_legacy_links_and_amount_overwrites(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/Services/PetroPdHistoricalPaymentRepairService.php'
        );

        self::assertStringContainsString('countCompetingUnlinkedRows', $source);
        self::assertStringContainsString('supporting_link_not_one_to_one', $source);
        self::assertStringContainsString('supporting_amount_mismatch', $source);
        self::assertStringContainsString('No amount was changed automatically', $source);
    }
}
