<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\Petro\Http\Controllers\SettlementPDController;

class PetroPdCreditSaleAccountingDuplicateTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function credit_sale_transaction_creation_is_idempotent_for_the_same_settlement_credit_sale(): void
    {
        $settlement = $this->seedSettlement();
        $creditSale = $this->seedCreditSale($settlement);

        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ]);
        session()->put('business.id', $this->businessId);
        session()->put('user.business_id', $this->businessId);
        session()->put('user.id', $this->userId);
        request()->setLaravelSession(app('session.store'));

        $controller = app(SettlementPDController::class);
        $controller->createCreditSellTransactions($settlement, $creditSale, $settlement->location_id);
        $controller->createCreditSellTransactions($settlement, $creditSale, $settlement->location_id);

        $this->assertSame(1, DB::table('transactions')
            ->where('business_id', $this->businessId)
            ->where('type', 'sell')
            ->where('sub_type', 'credit_sale')
            ->where('credit_sale_id', $creditSale->id)
            ->count());
    }

    private function seedSettlement(): Settlement
    {
        $locationId = (int) DB::table('business_locations')
            ->where('business_id', $this->businessId)
            ->value('id');

        $id = DB::table('settlements')->insertGetId([
            'settlement_no' => 'PDST-AR-' . uniqid(),
            'business_id' => $this->businessId,
            'transaction_date' => now()->toDateString(),
            'finish_date' => now()->toDateString(),
            'location_id' => $locationId ?: null,
            'pump_operator_id' => $this->pumpOperatorId,
            'work_shift' => json_encode([]),
            'status' => 1,
            'is_edit' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Settlement::findOrFail($id);
    }

    private function seedCreditSale(Settlement $settlement): SettlementCreditSalePayment
    {
        $id = DB::table('settlement_credit_sale_payments')->insertGetId($this->buildCreditSalePaymentData([
            'settlement_no' => $settlement->settlement_no,
            'pump_operator_id' => $settlement->pump_operator_id,
            'amount' => 4425,
            'sub_total' => 4425,
            'total_discount' => 0,
            'order_number' => 'AR-DUP-' . uniqid(),
        ]));

        return SettlementCreditSalePayment::findOrFail($id);
    }
}
