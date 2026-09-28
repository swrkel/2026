<?php

namespace Tests\Feature\Petro;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Petro\Services\SettlementPaymentQueryService;

/**
 * Step 5 — SettlementPaymentQueryService.
 *
 * The four public methods each return rows in the binding shape documented
 * in the service. Tests assert exact row counts, ordering, and that every
 * key in RETURN_SHAPE is present on every returned row.
 *
 * @group characterization
 */
class SettlementPaymentQueryServiceTest extends PetroTestCase
{
    use DatabaseTransactions;

    private SettlementPaymentQueryService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(SettlementPaymentQueryService::class);
    }

    /** @test */
    public function payments_for_shift_returns_rows_in_id_order(): void
    {
        $shiftId = random_int(900000, 999999);
        $id1 = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'shift_id' => $shiftId, 'payment_amount' => '100']);
        $id2 = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'shift_id' => $shiftId, 'payment_amount' => '200']);
        // Decoy — different shift.
        $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'shift_id' => $shiftId + 1, 'payment_amount' => '999']);

        $rows = $this->svc->paymentsForShift($this->businessId, $shiftId);

        $this->assertCount(2, $rows);
        $this->assertEquals($id1, $rows[0]['pump_payment_id']);
        $this->assertEquals($id2, $rows[1]['pump_payment_id']);
        $this->assertEquals('100', $rows[0]['payment_amount']);
        $this->assertEquals('200', $rows[1]['payment_amount']);
    }

    /** @test */
    public function payments_for_settlement_matches_numeric_and_string_forms(): void
    {
        $settlementId = random_int(900000, 999999);
        $id1 = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'settlement_no' => (string) $settlementId]);
        $id2 = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'settlement_no' => (string) $settlementId]);

        $rows = $this->svc->paymentsForSettlement($this->businessId, $settlementId);

        $this->assertCount(2, $rows);
        $this->assertEquals([$id1, $id2], $rows->pluck('pump_payment_id')->all());
    }

    /** @test */
    public function payments_for_operator_optionally_filters_by_shift(): void
    {
        $shiftA = random_int(900000, 999999);
        $shiftB = $shiftA + 1;
        $idA1 = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'shift_id' => $shiftA]);
        $idA2 = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'shift_id' => $shiftA]);
        $idB1 = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'shift_id' => $shiftB]);

        $allForOperator = $this->svc->paymentsForOperator($this->businessId, $this->pumpOperatorId);
        $this->assertGreaterThanOrEqual(3, $allForOperator->count(),
            'Without shift filter, all operator rows return (incl. any pre-existing in DB).');

        $forShiftA = $this->svc->paymentsForOperator($this->businessId, $this->pumpOperatorId, $shiftA);
        $idsInShiftA = $forShiftA->pluck('pump_payment_id')->all();
        $this->assertContains($idA1, $idsInShiftA);
        $this->assertContains($idA2, $idsInShiftA);
        $this->assertNotContains($idB1, $idsInShiftA);
    }

    /** @test */
    public function payment_by_pump_payment_id_returns_single_shaped_row(): void
    {
        $id = $this->seedPumpOperatorPayment(['payment_type' => 'card', 'payment_amount' => '777']);

        $row = $this->svc->paymentByPumpPaymentId($id);

        $this->assertIsArray($row);
        $this->assertEquals($id, $row['pump_payment_id']);
        $this->assertEquals('card', $row['payment_type']);
        $this->assertEquals('777', $row['payment_amount']);
    }

    /** @test */
    public function payment_by_pump_payment_id_returns_null_when_not_found(): void
    {
        $this->assertNull($this->svc->paymentByPumpPaymentId(999999999));
    }

    /** @test */
    public function every_returned_row_has_all_keys_from_return_shape(): void
    {
        $id = $this->seedPumpOperatorPayment(['payment_type' => 'cash']);
        $row = $this->svc->paymentByPumpPaymentId($id);
        $this->assertNotNull($row);

        foreach (SettlementPaymentQueryService::RETURN_SHAPE as $key) {
            $this->assertArrayHasKey($key, $row,
                "Returned row missing key '{$key}' — RETURN_SHAPE is the binding contract.");
        }
    }

    /** @test */
    public function settlement_id_field_is_int_when_settlement_no_is_digit_string(): void
    {
        $id = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'settlement_no' => '12345']);
        $row = $this->svc->paymentByPumpPaymentId($id);

        $this->assertSame(12345, $row['settlement_id'],
            'Numeric-string settlement_no must be exposed as integer settlement_id.');
        $this->assertSame('12345', $row['settlement_no']);
    }

    /** @test */
    public function settlement_id_field_is_null_when_settlement_no_is_code_like_ST5(): void
    {
        $id = $this->seedPumpOperatorPayment(['payment_type' => 'cash', 'settlement_no' => 'ST5']);
        $row = $this->svc->paymentByPumpPaymentId($id);

        $this->assertNull($row['settlement_id'],
            'Non-numeric settlement_no (e.g. "ST5") must yield NULL settlement_id.');
        $this->assertSame('ST5', $row['settlement_no']);
    }
}
