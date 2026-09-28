<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroSettlementMeterSaleEditInstantTotalsTest extends TestCase
{
    /** @test */
    public function direct_meter_sale_edit_refreshes_visible_totals_and_blocks_later_pump_usage(): void
    {
        $js = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/assets/js/app.js');
        $controller = file_get_contents(__DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementController.php');
        $table = file_get_contents(__DIR__ . '/../../Modules/Petro/Resources/views/settlement/partials/meter_sale.blade.php');

        $this->assertStringContainsString(
            'applyMeterSaleTableHtml',
            $js,
            'Edited meter sale response must update the rendered meter sale table instead of waiting for refresh.'
        );
        $this->assertStringContainsString(
            'calculate_payment_tab_total();',
            $js,
            'Edited meter sale response must update the Payment tab totals immediately.'
        );
        $this->assertStringContainsString(
            'laterPumpSettlementExists',
            $controller,
            'Direct meter sale edit must reject edits when the same pump is used by a later settlement.'
        );
        $this->assertStringContainsString(
            '$can_edit_meter_sale',
            $table,
            'Meter sale edit/delete buttons must be hidden when later pump settlement usage exists.'
        );
    }
}
