<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AddPaymentControllerCardQueryTest extends TestCase
{
    /** @test */
    public function card_payment_shift_filters_guard_linked_card_payment_alias_by_schema_column(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/AddPaymentController.php');

        $this->assertStringContainsString(
            'function ($q) use ($shift_ids_for_settlement, $has_settlement_card_pump_payment_column)',
            $controller
        );
        $this->assertStringContainsString(
            'function ($q) use ($shift_ids, $has_settlement_card_pump_payment_column)',
            $controller
        );
        $this->assertStringContainsString(
            'if ($include_linked_card_payment) {',
            $controller
        );
        $this->assertStringContainsString(
            "\$this->addCardPaymentShiftFilter(\$popQ, \$shift_ids_for_settlement, \$has_settlement_card_pump_payment_column);",
            $controller
        );
    }

    /** @test */
    public function pumper_credit_sale_query_uses_slip_number_only_when_column_exists(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/AddPaymentController.php');

        $this->assertStringContainsString(
            "\$has_pump_operator_payment_slip_no = \$this->tableHasColumn('pump_operator_payments', 'slip_no');",
            $controller
        );
        $this->assertStringContainsString(
            "\$pumper_credit_sale_selects[] = \$has_pump_operator_payment_slip_no",
            $controller
        );
        $this->assertStringContainsString(
            "DB::raw('NULL as slip_no')",
            $controller
        );
    }

    /** @test */
    public function pumper_credit_sale_query_joins_credit_sales_by_pump_payment_id(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/AddPaymentController.php');

        $pumperCreditStart = strpos($controller, '$pumper_credit_sale_payments = PumpOperatorPayment::');
        $this->assertNotFalse($pumperCreditStart, 'The pumper credit-sale query could not be found.');

        $pumperCreditEnd = strpos($controller, '->get();', $pumperCreditStart);
        $this->assertNotFalse($pumperCreditEnd, 'The pumper credit-sale query end could not be found.');

        $pumperCreditQuery = substr($controller, $pumperCreditStart, $pumperCreditEnd - $pumperCreditStart);

        $this->assertStringContainsString(
            "->on('credit_sales.pump_payment_id', '=', 'pump_operator_payments.id')",
            $pumperCreditQuery,
            'The settlement add-payment pumper credit list must use pump_payment_id to display the matching row.'
        );

        $this->assertStringNotContainsString(
            "credit_sales.amount = pump_operator_payments.payment_amount",
            $pumperCreditQuery,
            'The pumper credit list must not match by amount because sibling rows can share collection_form_no and drift after edits.'
        );

        $this->assertStringNotContainsString(
            "credit_sales.collection_form_no COLLATE",
            $pumperCreditQuery,
            'The pumper credit list must not match by collection_form_no because multiple credit rows can share it.'
        );
    }
}
