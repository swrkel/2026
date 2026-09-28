<?php

namespace Tests\Unit;

use Tests\TestCase;

class PetroSettlementPrintPaymentDetailsLayoutTest extends TestCase
{
    /** @test */
    public function settlement_print_payment_details_table_uses_full_width_no_clip_layout(): void
    {
        $templates = [
            base_path('Modules/Petro/Resources/views/settlement/print.blade.php'),
            base_path('Modules/PetroPD/Resources/views/pd_settlement/print.blade.php'),
        ];

        foreach ($templates as $template) {
            $contents = file_get_contents($template);

            $this->assertStringContainsString('payment-details-table', $contents);
            $this->assertStringContainsString('payment-details-total', $contents);
            $this->assertStringContainsString('white-space: nowrap', $contents);
        }
    }

    /** @test */
    public function settlement_print_meter_sale_subtotal_spans_all_non_total_columns(): void
    {
        $templates = [
            base_path('Modules/Petro/Resources/views/settlement/print.blade.php'),
            base_path('Modules/Petro/Resources/views/settlement/show.blade.php'),
        ];

        foreach ($templates as $template) {
            $contents = file_get_contents($template);

            $this->assertStringContainsString('<td colspan="8" style="text-align: right;">@lang(\'petro::lang.sub_total\')</td>', $contents);
        }
    }

    /** @test */
    public function settlement_print_meter_sale_table_uses_full_width_bootstrap_column(): void
    {
        $templates = [
            base_path('Modules/Petro/Resources/views/settlement/print.blade.php'),
            base_path('Modules/Petro/Resources/views/settlement/show.blade.php'),
        ];

        foreach ($templates as $template) {
            $contents = file_get_contents($template);
            $meterSaleSection = strstr($contents, "@lang('petro::lang.other_sale')", true);

            $this->assertStringContainsString('<div class="col-md-12">', $meterSaleSection);
            $this->assertStringNotContainsString('<div class="col-xs-12">', $meterSaleSection);
        }
    }
}
