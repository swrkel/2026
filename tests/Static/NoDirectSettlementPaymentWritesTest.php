<?php

namespace Tests\Static;

use Tests\TestCase;

/**
 * Lock 3 - CI static check.
 *
 * Fails the build if code outside the SettlementPaymentReconciler directly
 * invokes create or raw inserts on guarded settlement payment tables.
 *
 * @group static-discipline
 */
class NoDirectSettlementPaymentWritesTest extends TestCase
{
    /** @test */
    public function no_direct_settlement_payment_writes(): void
    {
        $patterns = [
            '/(?<![A-Za-z0-9_])SettlementCardPayment::create\(/',
            '/(?<![A-Za-z0-9_])SettlementCashPayment::create\(/',
            '/(?<![A-Za-z0-9_])SettlementChequePayment::create\(/',
            '/(?<![A-Za-z0-9_])SettlementCreditSalePayment::create\(/',
            '/(?<![A-Za-z0-9_])VatSettlementCardPayment::create\(/',
            '/(?<![A-Za-z0-9_])VatSettlementCashPayment::create\(/',
            '/(?<![A-Za-z0-9_])VatSettlementCreditSalePayment::create\(/',
            "/DB::table\(['\"]settlement_card_payments['\"]\)->insert/",
            "/DB::table\(['\"]settlement_cash_payments['\"]\)->insert/",
            "/DB::table\(['\"]settlement_cheque_payments['\"]\)->insert/",
            "/DB::table\(['\"]settlement_credit_sale_payments['\"]\)->insert/",
            "/DB::table\(['\"]vat_settlement_card_payments['\"]\)->insert/",
            "/DB::table\(['\"]vat_settlement_cash_payments['\"]\)->insert/",
            "/DB::table\(['\"]vat_settlement_credit_sale_payments['\"]\)->insert/",
        ];

        $allowed = [
            'Modules/Petro/Services/SettlementPaymentReconciler.php',
            'Modules/Petro/Database/Seeders/PetroDummyDataSeeder.php',
            'tests/',
        ];

        $violations = [];
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(base_path('Modules'), \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($rii as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relPath = str_replace('\\', '/', substr($file->getPathname(), strlen(base_path()) + 1));
            $skip = false;
            foreach ($allowed as $allow) {
                if (str_contains($relPath, $allow)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $contents)) {
                    $violations[] = "{$relPath}: matches {$pattern}";
                }
            }
        }

        $this->assertEmpty(
            $violations,
            "Direct writes to guarded settlement payment tables outside Reconciler:\n" . implode("\n", $violations)
        );
    }
}
