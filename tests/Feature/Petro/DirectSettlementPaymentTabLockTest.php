<?php
 
namespace Tests\Feature\Petro;
 
use Tests\TestCase;
 
class DirectSettlementPaymentTabLockTest extends TestCase
{
    /** @test */
    public function direct_settlement_payment_tabs_are_not_statically_locked_but_inputs_are_locked_conditionally(): void
    {
        $viewPath = resource_path('views/../../Modules/Petro/Resources/views/settlement/partials/payment_tabs.blade.php');
        $this->assertFileExists($viewPath);
 
        $html = file_get_contents($viewPath);
 
        // 1. Tabs should NOT be statically locked/disabled with pointer-events: none anymore
        $disabledTabs = ['cash_tab', 'cash_deposit_tab', 'cards_tab', 'cheques_tab', 'expense_tab', 'credit_sales_tab', 'loan_payments_tab', 'drawing_payments_tab', 'settlement_customer_loans'];
        foreach ($disabledTabs as $tab) {
            $this->assertDoesNotMatchRegularExpression('/<li[^>]*style="[^"]*pointer-events:\s*none[^"]*"[^>]*>\s*<a[^>]*href="#' . $tab . '"/i', $html, "Tab {$tab} should not be statically disabled.");
        }
 
        // 2. The view must check for real-time shift and contain the script to lock fields
        $this->assertStringContainsString('$is_real_time_shift', $html);
        $this->assertStringContainsString('tabsToLock', $html);
    }
}

