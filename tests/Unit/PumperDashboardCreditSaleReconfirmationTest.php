<?php

namespace Tests\Unit;

use Tests\TestCase;

class PumperDashboardCreditSaleReconfirmationTest extends TestCase
{
    /** @test */
    public function credit_sale_view_contains_reconfirmation_containers(): void
    {
        $view = file_get_contents(base_path('Modules/PumperDashboard/Resources/views/credit_sale.blade.php'));
        
        $this->assertStringContainsString('id="customer_reconfirmed_container"', $view);
        $this->assertStringContainsString('id="order_reconfirmed_container"', $view);
    }

    /** @test */
    public function payments_js_contains_reconfirmation_handlers(): void
    {
        $js = file_get_contents(base_path('Modules/PumperDashboard/Resources/views/actions/payments.blade.php'));
        
        $this->assertStringContainsString('isConfirmingCustomer', $js);
        $this->assertStringContainsString('isConfirmingOrderNumber', $js);
        $this->assertStringContainsString('swal-reverse-buttons', $js);
    }
}
