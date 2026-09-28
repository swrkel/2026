<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPaymentJavascriptSafetyTest extends TestCase
{
    /** @test */
    public function show_hide_excess_shortage_tab_handles_missing_total_balance_value(): void
    {
        $script = file_get_contents(__DIR__ . '/../../public/js/petro_payment.js');

        $this->assertStringContainsString(
            'let rawBalance = $("#total_balance").val() || $(".total_balance").eq(0).text() || "0";',
            $script
        );
        $this->assertStringContainsString(
            'let total_balance = parseFloat(String(rawBalance).replace(/,/g, "")) || 0;',
            $script
        );
        $this->assertStringNotContainsString(
            '$("#total_balance").val().replace(/,/g, "")',
            $script
        );
        $this->assertStringNotContainsString(
            '$("#total_balance").val().toString().replace(/,/g, "")',
            $script
        );
    }

    /** @test */
    public function petro_pd_payment_partials_do_not_parse_missing_total_balance_directly(): void
    {
        $files = [
            __DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/partials/add_payment.blade.php',
            __DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/partials/payment_tabs/cash.blade.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString(
                '$("#total_balance").val().replace(/,/g, "")',
                $contents,
                basename($file) . ' should not call replace() directly on #total_balance.val().'
            );
            $this->assertStringNotContainsString(
                '$("#total_balance").val().toString().replace(/,/g, "")',
                $contents,
                basename($file) . ' should not call toString() directly on #total_balance.val().'
            );
            $this->assertStringNotContainsString(
                'rawBalance.replace(/,/g, "")',
                $contents,
                basename($file) . ' should normalize #total_balance through String() before replace().'
            );
        }
    }

    /** @test */
    public function petro_pd_create_view_cache_busts_petro_payment_with_its_own_mtime(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/create.blade.php');

        $this->assertStringContainsString(
            "\$asset_vpetro_payment = filemtime(public_path('js/petro_payment.js'));",
            $view
        );
        $this->assertStringContainsString(
            "js/petro_payment.js?v=' . \$asset_vpetro_payment",
            $view
        );
    }

    /** @test */
    public function direct_settlement_balance_to_operator_routes_shortage_and_excess_by_balance_sign(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/views/settlement/partials/add_payment.blade.php');

        $this->assertStringContainsString(
            "var type = balance > 0 ? 'shortage' : 'excess';",
            $view
        );
        $this->assertStringContainsString(
            "var target_active_tab = type === 'shortage' ? '#shortage_tab' : '#excess_tab';",
            $view
        );
        $this->assertStringContainsString(
            "if (active_tab !== target_active_tab) {",
            $view
        );
        $this->assertStringContainsString(
            "var form_amount = type === 'shortage' ? amount : -amount;",
            $view
        );
        $this->assertStringContainsString(
            'var balance_adjustment = balance;',
            $view
        );
        $this->assertStringContainsString(
            'var new_total_paid = current_total_paid + balance_adjustment;',
            $view
        );
        $this->assertStringContainsString(
            'var new_balance = current_total_balance - balance_adjustment;',
            $view
        );
        $this->assertStringContainsString(
            "if (useLockedState && lastBalanceType === 'shortage') {\n                            // Shortage was added - keep Shortage tab active, lock Excess tab",
            str_replace("\r\n", "\n", $view)
        );
        $this->assertStringContainsString(
            "\$('#settlement_form .shortage_tab').parents('li:first').show().removeClass('disabled');",
            $view
        );
        $this->assertStringContainsString(
            "\$('.shortage_add').prop('disabled', false);",
            $view
        );
        $this->assertStringContainsString(
            "\$('#shortage_amount').prop('disabled', false);",
            $view
        );
        $this->assertStringContainsString(
            "if (useLockedState && lastBalanceType === 'excess') {\n                            // Excess was added - keep Excess tab active, lock Shortage tab",
            str_replace("\r\n", "\n", $view)
        );
        $this->assertStringContainsString(
            "\$('#settlement_form .excess_tab').parents('li:first').show().removeClass('disabled');",
            $view
        );
        $this->assertStringContainsString(
            "\$('.excess_add_btn').prop('disabled', false);",
            $view
        );
        $this->assertStringContainsString(
            "\$('#excess_amount').prop('disabled', false);",
            $view
        );
        $this->assertStringContainsString(
            'if (window.__petro_balance_to_operator_active && shortage_amount < 0) {',
            $view
        );
        $this->assertStringContainsString(
            'shortage_amount = Math.abs(shortage_amount);',
            $view
        );
        $this->assertStringContainsString(
            'if (!window.__petro_balance_to_operator_active) {' . "\n" . '                                    $(".shortage_fields").val("");',
            str_replace("\r\n", "\n", $view)
        );
    }

    /** @test */
    public function credit_sale_add_button_blocks_duplicate_submits_while_ajax_is_pending(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/views/settlement/partials/payment_tabs/credit_sales.blade.php');

        $this->assertStringContainsString(
            "var \$btn = \$(this);",
            $view
        );
        $this->assertStringContainsString(
            "if (\$btn.data('submitting')) {",
            $view
        );
        $this->assertStringContainsString(
            "\$btn.data('submitting', true).prop('disabled', true);",
            $view
        );
        $this->assertStringContainsString(
            "\$btn.data('submitting', false).prop('disabled', false);",
            $view
        );
    }

    /** @test */
    public function credit_sale_add_button_reset_is_not_blocked_by_missing_success_confirmation_helper(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/views/settlement/partials/payment_tabs/credit_sales.blade.php');

        $this->assertStringContainsString(
            "if (typeof handlePaymentSuccessConfirmation === 'function') {",
            $view
        );
        $this->assertStringNotContainsString(
            "                    handlePaymentSuccessConfirmation();\n                    \$btn.data('submitting', false).prop('disabled', false);",
            str_replace("\r\n", "\n", $view)
        );
    }

    /** @test */
    public function direct_settlement_other_income_uses_recomputed_subtotal_after_price_edit(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementController.php');
        $view = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/views/settlement/create.blade.php');

        $this->assertStringContainsString(
            '$sub_total = $this->productUtil->num_uf($request->qty) * $this->productUtil->num_uf($request->price);',
            $controller
        );
        $this->assertStringContainsString(
            "'sub_total' => \$sub_total,",
            $controller
        );
        $this->assertStringContainsString(
            "'sub_total' => \$other_income->sub_total,",
            $controller
        );
        $this->assertStringContainsString(
            'var saved_sub_total = parseFloat(result.sub_total || sub_total) || 0;',
            $view
        );
        $this->assertStringContainsString(
            '+ saved_sub_total;',
            $view
        );
    }

    /** @test */
    public function direct_settlement_other_income_recalculates_footer_from_table_rows(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/views/settlement/create.blade.php');

        $this->assertStringContainsString(
            'function updateOtherIncomeTotalFromRows()',
            $view
        );
        $this->assertStringContainsString(
            "if (\$row.find('td.dataTables_empty').length) return;",
            $view
        );
        $this->assertStringContainsString(
            "var table = \$.fn.DataTable.isDataTable('#other_income_table') ? $('#other_income_table').DataTable() : null;",
            $view
        );
        $this->assertStringContainsString(
            'updateOtherIncomeTotalFromRows();',
            $view
        );
    }

    /** @test */
    public function direct_settlement_other_income_refresh_recomputes_persisted_subtotals(): void
    {
        $partial = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/views/settlement/partials/other_income.blade.php');

        $this->assertStringContainsString(
            '$other_income_sub_total = (float) $other_income_item->qty * (float) $other_income_item->price;',
            $partial
        );
        $this->assertStringContainsString(
            '$other_income_final_total = $other_income_final_total + $other_income_sub_total;',
            $partial
        );
        $this->assertStringContainsString(
            '{{@num_format($other_income_sub_total)}}',
            $partial
        );
        $this->assertStringNotContainsString(
            '$other_income_final_total = $other_income_final_total + $other_income_item->sub_total;',
            $partial
        );
    }

    /** @test */
    public function global_other_income_handlers_skip_direct_settlement_create_page(): void
    {
        $script = file_get_contents(__DIR__ . '/../../public/js/app.js');

        $this->assertStringContainsString(
            'if (window.__petro_settlement_create_local) return;',
            $script
        );
        $this->assertStringContainsString(
            'if (window.__petro_settlement_create_local) return;',
            file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/assets/js/app.js')
        );
    }
}
