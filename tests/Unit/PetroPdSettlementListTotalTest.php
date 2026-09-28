<?php

namespace Tests\Unit;

use Illuminate\Support\Collection;
use Modules\Petro\Entities\PumpOperatorMeterSale;
use Modules\Petro\Entities\PumpOperatorMeterSaleDetail;
use Modules\Petro\Entities\Settlement;
use Modules\Petro\Entities\SettlementCardPayment;
use Modules\Petro\Entities\SettlementCashPayment;
use Modules\Petro\Entities\SettlementChequePayment;
use Modules\Petro\Entities\SettlementCreditSalePayment;
use Modules\PetroPD\Http\Controllers\PetroPDController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class PetroPdSettlementListTotalTest extends TestCase
{
    /** @test */
    public function it_uses_pd_meter_sale_details_for_the_list_total_amount(): void
    {
        $settlement = new Settlement();
        $settlement->total_amount = 0;

        $sale = new PumpOperatorMeterSale();
        $firstDetail = new PumpOperatorMeterSaleDetail();
        $firstDetail->amount = 1250.50;

        $secondDetail = new PumpOperatorMeterSaleDetail();
        $secondDetail->amount = 749.50;

        $sale->setRelation('details', new Collection([$firstDetail, $secondDetail]));

        $settlement->setRelation('meter_sales_pd', new Collection([$sale]));
        $settlement->setRelation('meter_sales', new Collection());
        $settlement->setRelation('other_sales', new Collection());

        $controller = (new ReflectionClass(PetroPDController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(PetroPDController::class))->getMethod('calculatePdSettlementListTotal');
        $method->setAccessible(true);

        $this->assertSame(2000.0, $method->invoke($controller, $settlement));
    }

    /** @test */
    public function it_uses_finalized_payment_records_for_the_list_total_when_settlement_total_is_zero(): void
    {
        $settlement = new Settlement();
        $settlement->total_amount = 0;

        $cashPayment = new SettlementCashPayment();
        $cashPayment->amount = 50000;

        $cardPayment = new SettlementCardPayment();
        $cardPayment->amount = 25000;

        $chequePayment = new SettlementChequePayment();
        $chequePayment->amount = 10000;

        $creditSalePayment = new SettlementCreditSalePayment();
        $creditSalePayment->amount = 60000;
        $creditSalePayment->total_discount = 500;

        $settlement->setRelation('meter_sales_pd', new Collection());
        $settlement->setRelation('meter_sales', new Collection());
        $settlement->setRelation('other_sales', new Collection());
        $settlement->setRelation('cash_payments', new Collection([$cashPayment]));
        $settlement->setRelation('card_payments', new Collection([$cardPayment]));
        $settlement->setRelation('cheque_payments', new Collection([$chequePayment]));
        $settlement->setRelation('credit_sale_payments', new Collection([$creditSalePayment]));

        $controller = (new ReflectionClass(PetroPDController::class))->newInstanceWithoutConstructor();
        $method = (new ReflectionClass(PetroPDController::class))->getMethod('calculatePdSettlementListTotal');
        $method->setAccessible(true);

        $this->assertSame(144500.0, $method->invoke($controller, $settlement));
    }
}
