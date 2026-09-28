<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPdEditViewPartialsTest extends TestCase
{
    /** @test */
    public function petro_pd_edit_view_uses_petro_pd_partials_for_saved_details(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/edit.blade.php');

        $this->assertStringContainsString("petropd::pd_settlement.partials.meter_sale", $view);
        $this->assertStringContainsString("petropd::pd_settlement.partials.other_sale", $view);
        $this->assertStringContainsString("petropd::pd_settlement.partials.payment", $view);
    }

    /** @test */
    public function petro_pd_edit_view_uses_real_shift_id_for_saved_detail_loaders(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/edit.blade.php');
        $meterSalePartial = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/partials/meter_sale.blade.php');

        $this->assertStringContainsString('id="shift_id"', $view);
        $this->assertStringContainsString("const \$shiftId = $('#shift_id');", $view);
        $this->assertStringContainsString('var new_shift_ids = $shiftId.val();', $view);
        $this->assertStringContainsString('d.shift_ids = $shiftId.val();', $view);

        $this->assertStringContainsString("const shiftOk = !!($('#shift_id').val() || $('#shift_number').val());", $meterSalePartial);
        $this->assertStringContainsString("const shift_id = $('#shift_id').val() || $('#shift_number').val();", $meterSalePartial);
    }

    /** @test */
    public function petro_pd_edit_controller_hydrates_meter_sales_by_shift_when_settlement_no_is_missing(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementPDController.php');

        $this->assertStringContainsString('$this->hydrateSettlementPdMeterSalesForDisplay($active_settlement, $shiftIds);', $controller);
    }

    /** @test */
    public function finalized_petro_pd_edit_does_not_replace_saved_meter_rows_with_shift_ajax_rows(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/edit.blade.php');

        $this->assertStringContainsString('const isFinalizedSettlement =', $view);
        $this->assertStringContainsString('const hasSavedMeterSales =', $view);
        $this->assertStringContainsString('if (!isFinalizedSettlement && !hasSavedMeterSales) {', $view);
        $this->assertStringContainsString('loadMeterSalesData();', $view);
    }

    /** @test */
    public function petro_pd_edit_renders_explicit_display_meter_sales_from_controller(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementPDController.php');
        $partial = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/partials/meter_sale.blade.php');

        $this->assertStringContainsString('$display_meter_sales = $this->getSettlementPdDisplayMeterSales(', $controller);
        $this->assertStringContainsString('"display_meter_sales"', $controller);
        $this->assertStringContainsString('@foreach ($display_meter_sales as $item)', $partial);
        $this->assertStringNotContainsString('@elseif (!empty($active_settlement) && !empty($active_settlement->meter_sales)', $partial);
    }

    /** @test */
    public function petro_pd_payment_tab_refresh_uses_settlement_meter_total(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementPDController.php');
        $methodStart = strpos($controller, 'public function getPaymentTabTotals');
        $method = substr($controller, $methodStart, 1400);

        $this->assertStringContainsString('$this->getSettlementPDMeterSaleTotal(', $method);
        $this->assertStringNotContainsString('$this->getMeterSaleTotalByShift(', $method);
    }

    /** @test */
    public function petro_pd_global_payment_refresh_uses_petro_pd_totals_endpoint(): void
    {
        $script = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/assets/js/app.js');
        $functionStart = strpos($script, 'function refresh_settlement_totals()');
        $functionBody = substr($script, $functionStart, 700);

        $this->assertStringContainsString('/petro/settlement-pd/get-payment-tab-totals', $functionBody);
        $this->assertStringContainsString('/petro/settlement/get-payment-tab-totals', $functionBody);
    }

    /** @test */
    public function petro_pd_payment_tab_does_not_locally_recalculate_before_ajax_refresh(): void
    {
        $script = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/assets/js/app.js');
        $handlerStart = strpos($script, 'shown.bs.tab');
        $handlerBody = substr($script, $handlerStart, 500);

        $this->assertStringContainsString('if (!isPetroPdSettlementPage()) {', $handlerBody);
        $this->assertStringContainsString('calculate_payment_tab_total();', $handlerBody);
    }

    /** @test */
    public function saved_petro_pd_edit_meter_table_draw_does_not_overwrite_payment_total(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/edit.blade.php');
        $drawStart = strpos($view, 'fnDrawCallback()');
        $drawBody = substr($view, $drawStart, 900);

        $this->assertStringContainsString('if (isFinalizedSettlement || hasSavedMeterSales) {', $drawBody);
        $this->assertStringContainsString('return;', $drawBody);
        $this->assertStringNotContainsString('Payments summary reads from `#meter_sale_total`', $drawBody);
    }

    /** @test */
    public function petro_pd_no_change_add_payment_modal_makes_credit_sales_read_only(): void
    {
        $payment = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/partials/payment.blade.php');
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/AddPaymentController.php');
        $creditSales = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/partials/payment_tabs/credit_sales.blade.php');

        $this->assertStringContainsString("'no_change' => request()->no_change", $payment);
        $this->assertStringContainsString('$no_change = $request->boolean(\'no_change\');', $controller);
        $this->assertStringContainsString("'no_change'", $controller);
        $this->assertStringContainsString('$is_no_change_payment_modal = !empty($no_change);', $creditSales);
        $this->assertStringContainsString('@unless($is_no_change_payment_modal)', $creditSales);
        $this->assertStringContainsString('@if(!$is_no_change_payment_modal)', $creditSales);
        $this->assertStringContainsString('window.isPetroPdNoChangePaymentModal', $creditSales);
    }

    /** @test */
    public function petro_pd_meter_sale_total_regular_fallback_subtracts_discount_from_sub_total(): void
    {
        // Regression: after adding an Other Sale on the edit (incl. Edit-no-change)
        // view, refresh_settlement_totals() refreshes the Payment tab via
        // getPaymentTabTotals -> getSettlementPDMeterSaleTotal(). For finalized
        // settlements both PumpOperatorMeterSale-based totals are zero (their
        // p_o_payment_id is set), so the function falls back to the meter_sales
        // table. The fallback previously summed `discount_amount`, which is the
        // discount value (zero for sales without discount), causing the Meter
        // Sales Total to render as 0.00 and the meter sales to "disappear".
        // The fallback must sum the after-discount amount: sub_total - discount_amount.
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementPDController.php'
        );

        $methodStart = strpos($controller, 'private function getSettlementPDMeterSaleTotal');
        $this->assertNotFalse(
            $methodStart,
            'Could not locate getSettlementPDMeterSaleTotal in SettlementPDController.'
        );

        $methodBody = substr($controller, $methodStart, 2000);

        $this->assertStringNotContainsString(
            "->sum('discount_amount')",
            $methodBody,
            'Meter sale regular_total fallback must NOT sum discount_amount; that returns 0 for sales without a discount and makes Meter Sales Total disappear on the Payment tab after adding an Other Sale.'
        );

        $this->assertStringContainsString(
            'sub_total - discount_amount',
            $methodBody,
            'Meter sale regular_total fallback must sum (sub_total - discount_amount) to reflect the after-discount displayable amount.'
        );
    }

    /** @test */
    public function payment_total_calculator_uses_safe_numeric_parsing(): void
    {
        $script = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/assets/js/app.js');
        $functionStart = strpos($script, 'function calculate_payment_tab_total()');
        $functionBody = substr($script, $functionStart, 1300);

        $this->assertStringContainsString('readSettlementTotal(', $functionBody);
        $this->assertStringContainsString('Number.isFinite(all_totals)', $functionBody);
    }
}
