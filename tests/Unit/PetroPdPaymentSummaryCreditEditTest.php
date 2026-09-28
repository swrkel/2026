<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPdPaymentSummaryCreditEditTest extends TestCase
{
    /** @test */
    public function petro_pd_payment_summary_credit_edit_carries_row_identity_and_updates_only_that_payment(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/PumpOperatorPaymentController.php'
        );
        $pumperController = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Http/Controllers/PumpOperatorPaymentController.php'
        );
        $paymentQueryService = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Services/SettlementPaymentQueryService.php'
        );
        $dailyVoucherController = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/DailyVoucherController.php'
        );
        $controller = str_replace("\r\n", "\n", $controller);
        $pumperController = str_replace("\r\n", "\n", $pumperController);
        $paymentQueryService = str_replace("\r\n", "\n", $paymentQueryService);
        $dailyVoucherController = str_replace("\r\n", "\n", $dailyVoucherController);

        $actionColumnStart = strpos($controller, "->addColumn('action'");
        $this->assertNotFalse($actionColumnStart, 'The payment summary action column could not be found.');

        $actionColumnEnd = strpos($controller, "->addColumn('date'", $actionColumnStart);
        $this->assertNotFalse($actionColumnEnd, 'The payment summary action column end could not be found.');

        $actionColumn = substr($controller, $actionColumnStart, $actionColumnEnd - $actionColumnStart);

        $this->assertStringContainsString(
            "'scsp.id as scsp_id'",
            $controller,
            'The payment summary query must select the exact settlement_credit_sale_payments id for each credit row.'
        );

        $this->assertStringContainsString(
            '\'&credit_sale_id=\' . urlencode($row->scsp_id)',
            $controller,
            'Credit edit links must pass the selected scsp_id as credit_sale_id. One-by-one dashboard credit sales can share collection_form_no and amount, so edit() cannot safely rediscover the row later.'
        );

        $this->assertStringContainsString(
            '\'&payment_id=\' . urlencode($row->id)',
            $controller,
            'Credit edit links must pass the selected pump_operator_payments id so downstream voucher edits can sync only that row.'
        );

        $this->assertStringContainsString(
            "->on('scsp.pump_payment_id', '=', 'pump_operator_payments.id')",
            $controller,
            'Petro payment summary must join credit rows by pump_payment_id, not collection_form_no/amount.'
        );

        $this->assertStringContainsString(
            "->on('scsp.pump_payment_id', '=', 'pump_operator_payments.id')",
            $paymentQueryService,
            'PumperDashboard payment summary must join credit rows by pump_payment_id, not collection_form_no/amount.'
        );

        $this->assertStringContainsString(
            "->paymentSummaryBaseQuery(\$business_id)",
            $pumperController,
            'PumperDashboard payment summary must use the shared payment summary query service.'
        );

        $this->assertStringNotContainsString(
            "scsp.amount = pump_operator_payments.payment_amount",
            $paymentQueryService,
            'PumperDashboard payment summary must not rediscover credit rows by amount.'
        );

        $this->assertStringNotContainsString(
            "scsp.collection_form_no COLLATE",
            $paymentQueryService,
            'PumperDashboard payment summary must not rediscover credit rows by collection_form_no.'
        );

        $this->assertStringContainsString(
            '$edit_query = \'?type=\' . urlencode($row->payment_type);',
            $pumperController,
            'PumperDashboard edit links must build a query string that can carry row identity.'
        );

        $this->assertStringContainsString(
            '$requested_form_exists = PumpOperatorPayment::where(\'business_id\', $business_id)',
            $pumperController,
            'PumperDashboard one-by-one credit saves must treat a posted collection_form_no as stale when it already exists.'
        );

        $this->assertStringContainsString(
            '$requested_form_exists = PumpOperatorPayment::where(\'business_id\', $business_id)',
            $controller,
            'Petro pump-operator one-by-one credit saves must treat a posted collection_form_no as stale when it already exists.'
        );

        $this->assertStringContainsString(
            "SettlementCreditSalePayment::where('business_id', \$business_id)\n                        ->where('collection_form_no', \$requested_collection_form_no)",
            $pumperController,
            'PumperDashboard one-by-one credit saves must also check existing credit-sale rows before reusing a collection_form_no.'
        );

        $this->assertStringContainsString(
            "SettlementCreditSalePayment::where('pump_payment_id', \$payment->id)",
            $pumperController,
            'PumperDashboard edit() must resolve the credit sale by the selected pump_operator_payments id.'
        );

        $this->assertStringContainsString(
            '\'&payment_id=\' . urlencode($row->id)',
            $pumperController,
            'PumperDashboard credit edit links must carry the selected pump_operator_payments id.'
        );

        $this->assertStringContainsString(
            '\'&credit_sale_id=\' . urlencode($row->scsp_id)',
            $pumperController,
            'PumperDashboard credit edit links must carry the selected settlement_credit_sale_payments id.'
        );

        $this->assertStringContainsString(
            '$request->input(\'payment_id\')',
            $dailyVoucherController,
            'Daily voucher credit edits must use the selected pump_operator_payments id when syncing the payment summary row.'
        );

        $this->assertStringNotContainsString(
            "->where('collection_form_no', \$credit_sale_payment->collection_form_no)\n                        ->update([",
            $dailyVoucherController,
            'Daily voucher credit edits must not update every pump_operator_payments row with the same collection_form_no.'
        );
    }

    /** @test */
    public function pumper_dashboard_payment_summary_filters_use_operator_scoped_real_payment_columns(): void
    {
        $pumperController = str_replace("\r\n", "\n", file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Http/Controllers/PumpOperatorPaymentController.php'
        ));
        $paymentSummaryView = str_replace("\r\n", "\n", file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Resources/views/partials/payment_summary.blade.php'
        ));

        $this->assertStringNotContainsString(
            'pump_operator_payments.shift_number',
            $pumperController,
            'Payment summary searches must not reference the missing pump_operator_payments.shift_number column.'
        );

        $this->assertStringContainsString(
            "name: 'pump_operator_payments.shift_id'",
            file_get_contents(__DIR__ . '/../../Modules/PumperDashboard/Resources/views/payment_summary.blade.php'),
            'DataTables global search for Shift must target pump_operator_payments.shift_id.'
        );

        $this->assertStringContainsString(
            "name: 'pump_operator_payments.payment_amount'",
            file_get_contents(__DIR__ . '/../../Modules/PumperDashboard/Resources/views/payment_summary.blade.php'),
            'DataTables global search for Amount must target pump_operator_payments.payment_amount.'
        );

        $this->assertStringContainsString(
            "\$payment_types = PumpOperatorPayment::where('business_id', \$business_id)",
            $pumperController,
            'Payment method filters must be built from pump operator payments, not every system payment type.'
        );

        $this->assertStringContainsString(
            "->where('pump_operator_id', \$pump_operator_id)",
            $pumperController,
            'Only-pumper payment method filters must be scoped to the logged-in pump operator.'
        );

        $this->assertStringContainsString(
            "\$pump_operators = \$only_pumper\n            ? PumpOperator::where('business_id', \$business_id)->where('id', \$pump_operator_id)->pluck('name', 'id')",
            $pumperController,
            'Only-pumper payment summary must show only the logged-in pump operator in the operator dropdown.'
        );

        $this->assertStringContainsString(
            "Form::select('payment_summary_pump_operators', \$pump_operators, \$selected_pump_operator_id",
            $paymentSummaryView,
            'The pump operator filter must default to the logged-in pump operator.'
        );
    }
}
