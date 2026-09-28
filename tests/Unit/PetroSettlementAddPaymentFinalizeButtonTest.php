<?php

namespace Tests\Unit;

use Tests\TestCase;

class PetroSettlementAddPaymentFinalizeButtonTest extends TestCase
{
    /** @test */
    public function add_payment_finalize_button_update_uses_visible_button_state_after_readding_payment(): void
    {
        $view = file_get_contents(base_path('Modules/Petro/Resources/views/settlement/partials/add_payment.blade.php'));

        $this->assertStringContainsString('function set_settlement_finalize_visible(shouldShow)', $view);
        $this->assertStringContainsString('$("#settlement_save_btn").removeClass("hide").show().css("display", "inline-block")', $view);
        $this->assertStringContainsString('$("#settlement_save_btn").addClass("hide").hide()', $view);
        $this->assertStringContainsString('set_settlement_finalize_visible(true);', $view);
        $this->assertStringContainsString('set_settlement_finalize_visible(shouldShow);', $view);
    }
}
