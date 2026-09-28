<?php

namespace Modules\PetroPD\Tests\Unit;

use Illuminate\Support\Collection;
use Modules\PetroPD\Services\PetroPdSettlementPaymentSnapshotService;
use Modules\PetroPD\Services\PumpOperatorPaymentAuthorityService;
use PHPUnit\Framework\TestCase;

class PetroPdPaymentSnapshotTotalsTest extends TestCase
{
    private function service(): PetroPdSettlementPaymentSnapshotService
    {
        return new PetroPdSettlementPaymentSnapshotService(
            new PumpOperatorPaymentAuthorityService()
        );
    }

    private function row(int $id, string $type, float $net, ?float $gross = null, float $discount = 0): object
    {
        return (object) [
            'pump_payment_id' => $id,
            'payment_type' => $type,
            'gross_amount' => $gross ?? $net,
            'discount_amount' => $discount,
            'net_amount' => $net,
        ];
    }

    public function test_same_payment_id_is_counted_once_even_when_a_join_returns_it_twice(): void
    {
        $rows = new Collection([
            $this->row(101, 'credit', 23302, 23302),
            $this->row(101, 'credit', 23302, 23302),
            $this->row(102, 'credit', 18336, 18336),
        ]);

        $totals = $this->service()->summarizeUniquePayments($rows);

        self::assertSame(41638.0, $totals['credit']);
        self::assertSame(41638.0, $totals['recognized_total_paid']);
    }

    public function test_equal_amounts_with_different_payment_ids_are_both_counted(): void
    {
        $rows = new Collection([
            $this->row(201, 'credit', 5000, 5000),
            $this->row(202, 'credit', 5000, 5000),
        ]);

        $totals = $this->service()->summarizeUniquePayments($rows);

        self::assertSame(10000.0, $totals['credit']);
    }

    public function test_credit_gross_discount_and_net_use_one_master_identity(): void
    {
        $rows = new Collection([
            $this->row(301, 'credit', 9000, 10000, 1000),
            $this->row(302, 'cash', 2500),
            $this->row(303, 'card', 1500),
        ]);

        $totals = $this->service()->summarizeUniquePayments($rows);

        self::assertSame(10000.0, $totals['credit_gross']);
        self::assertSame(1000.0, $totals['credit_discount']);
        self::assertSame(9000.0, $totals['credit']);
        self::assertSame(13000.0, $totals['recognized_total_paid']);
    }

    public function test_negative_excess_reduces_total_paid_without_changing_other_rows(): void
    {
        $rows = new Collection([
            $this->row(401, 'cash', 10000),
            $this->row(402, 'excess', -500),
        ]);

        $totals = $this->service()->summarizeUniquePayments($rows);

        self::assertSame(-500.0, $totals['excess']);
        self::assertSame(9500.0, $totals['recognized_total_paid']);
    }
}
