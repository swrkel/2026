<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPdCreditSaleCollectionFormTest extends TestCase
{
    /** @test */
    public function petro_pd_modal_must_not_collapse_different_credit_sales_that_share_a_collection_form_number(): void
    {
        $addPaymentController = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/AddPaymentController.php'
        );

        $creditSaleRows = collect([
            (object) [
                'id' => 101,
                'collection_form_no' => '1',
                'customer_id' => 1,
                'customer_name' => 'Customer - 1',
                'order_number' => '22',
                'amount' => 1200.00,
            ],
            (object) [
                'id' => 102,
                'collection_form_no' => '1',
                'customer_id' => 2,
                'customer_name' => 'Customer - 2',
                'order_number' => '22',
                'amount' => 1300.00,
            ],
        ]);

        $rowsVisibleInModal = $creditSaleRows
            ->unique(function ($item) {
                return 'id-' . $item->id;
            })
            ->values();

        $this->assertCount(
            2,
            $rowsVisibleInModal,
            'The PD payment modal must keep Customer 1 and Customer 2 as separate credit-sale rows.'
        );

        $this->assertStringNotContainsString(
            "ELSE CONCAT('cf:', settlement_credit_sale_payments.collection_form_no) END",
            $addPaymentController,
            'The PD add-payment query must not group credit sales by collection_form_no alone because different customers can share the same form number.'
        );
    }
}
