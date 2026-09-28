<?php

namespace Tests\Unit;

use Tests\TestCase;

class PetroCreditSaleEditLanguageTest extends TestCase
{
    /** @test */
    public function credit_payment_edit_modal_translates_title_as_credit_sales(): void
    {
        $view = file_get_contents(base_path('Modules/Petro/Resources/views/daily_collection/partials/edit_daily_voucher.blade.php'));
        $lang = include base_path('Modules/Petro/Resources/lang/en/lang.php');

        $this->assertStringContainsString("petro::lang.edit_credit_sale", $view);
        $this->assertArrayHasKey('edit_credit_sale', $lang);
        $this->assertSame('Credit Sales', $lang['edit_credit_sale']);
    }
}
