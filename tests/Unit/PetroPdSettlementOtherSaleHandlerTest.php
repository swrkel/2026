<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroPdSettlementOtherSaleHandlerTest extends TestCase
{
    /** @test */
    public function petropd_settlement_create_view_defines_custom_other_sale_click_handler(): void
    {
        $createView = file_get_contents(
            __DIR__ . '/../../Modules/PetroPD/Resources/views/pd_settlement/create.blade.php'
        );

        $this->assertStringContainsString(
            'click.petro_other_sale_pd',
            $createView,
            'PetroPD settlement create view must define a custom .btn_other_sale click handler namespaceed with click.petro_other_sale_pd.'
        );

        $this->assertStringContainsString(
            '/petro/settlement-pd/save-other-sale',
            $createView,
            'The custom handler must submit to the PD settlement endpoint /petro/settlement-pd/save-other-sale.'
        );
    }
}
