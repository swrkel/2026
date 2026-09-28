<?php

namespace Tests\Feature\Petro;

use App\Transaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Http\Controllers\Traits\UpdatesSettlementTransactions;

/**
 * @group characterization
 */
class SettlementTransactionLookupModeTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function fk_only_settlement_lookup_flag_defaults_to_false(): void
    {
        $this->assertFalse(config('petro.fk_only_settlement_lookup'));
    }

    /** @test */
    public function fk_only_lookup_updates_fk_rows_and_skips_like_only_rows(): void
    {
        config(['petro.fk_only_settlement_lookup' => true]);

        $settlement = $this->seedSettlement();
        $newDate = now()->addDays(3)->toDateString();
        $oldDate = now()->subDays(3)->toDateString();

        $fkTransactionId = $this->seedTransaction([
            'invoice_no' => 'NO-LIKE-' . uniqid(),
            'ref_no' => 'unrelated',
            'transaction_date' => $oldDate,
            'petro_settlement_id' => $settlement->id,
        ]);
        $likeOnlyTransactionId = $this->seedTransaction([
            'invoice_no' => $settlement->settlement_no,
            'ref_no' => 'Settlement No: ' . $settlement->settlement_no,
            'transaction_date' => $oldDate,
            'petro_settlement_id' => null,
        ]);

        (new class {
            use UpdatesSettlementTransactions;

            public function run($settlement, string $newDate): void
            {
                $this->updateSettlementRelatedTransactions($settlement, $newDate);
            }
        })->run($settlement, $newDate);

        $this->assertSame(
            $newDate . ' 00:00:00',
            Transaction::where('id', $fkTransactionId)->value('transaction_date')
        );
        $this->assertSame(
            $oldDate . ' 00:00:00',
            Transaction::where('id', $likeOnlyTransactionId)->value('transaction_date')
        );
    }

    /** @test */
    public function coexistence_lookup_still_updates_historical_like_rows_by_default(): void
    {
        config(['petro.fk_only_settlement_lookup' => false]);

        $settlement = $this->seedSettlement();
        $newDate = now()->addDays(3)->toDateString();
        $oldDate = now()->subDays(3)->toDateString();

        $transactionId = $this->seedTransaction([
            'invoice_no' => $settlement->settlement_no,
            'ref_no' => 'Settlement No: ' . $settlement->settlement_no,
            'transaction_date' => $oldDate,
            'petro_settlement_id' => null,
        ]);

        (new class {
            use UpdatesSettlementTransactions;

            public function run($settlement, string $newDate): void
            {
                $this->updateSettlementRelatedTransactions($settlement, $newDate);
            }
        })->run($settlement, $newDate);

        $this->assertSame(
            $newDate . ' 00:00:00',
            Transaction::where('id', $transactionId)->value('transaction_date')
        );
    }

    private function seedSettlement(): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');
        if (! $locationId) {
            $this->markTestSkipped("No business_locations for business {$this->businessId}.");
        }

        $settlementId = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-LOOKUP-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId,
            'pump_operator_id' => $this->pumpOperatorId,
            'bulk_store_product' => 0,
            'work_shift' => json_encode([]),
            'note' => null,
            'total_amount' => '0',
            'status' => 0,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($settlementId);
    }

    private function seedTransaction(array $overrides = []): int
    {
        return DB::table('transactions')->insertGetId(array_merge([
            'business_id' => $this->businessId,
            'location_id' => null,
            'type' => 'settlement',
            'sub_type' => 'phase3',
            'status' => 'final',
            'payment_status' => 'paid',
            'invoice_no' => 'PHASE3-' . uniqid(),
            'ref_no' => null,
            'transaction_date' => now()->toDateString(),
            'total_before_tax' => 100,
            'final_total' => 100,
            'tax_amount' => 0,
            'created_by' => $this->userId,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }
}
