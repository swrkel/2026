<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955SalesOrderTabDivAndCustomerAddressTest extends TestCase
{
    public function test_controller_index_selects_address_for_all_customers_queries(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $controller = file_get_contents($projectRoot . '/Modules/Distribution/Http/Controllers/DistributionSalesOrderController.php');

        $this->assertIsString($controller);
        
        // Find the customer query around line 412:
        // $customers = \App\Contact::where('business_id', $business_id)->...->select(...)
        // It must contain 'address'
        $pattern = "/\\\$customers\s*=\s*\\\App\\\Contact::where\('business_id',\s*\\\$business_id\)(?:[^;]*->select\([^)]*\)|[^;]*->get\([^)]*\))/s";
        preg_match_all($pattern, $controller, $matches);
        
        $this->assertNotEmpty($matches[0], "Could not find customer queries in controller");
        foreach ($matches[0] as $match) {
            $this->assertStringContainsString('address', $match, "Customer query is missing 'address' column: " . $match);
        }
    }

    public function test_index_blade_has_correct_div_nesting_before_submit(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $indexView = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/sales_orders/index.blade.php');

        $this->assertIsString($indexView);
        // Verify we do NOT have four closing divs before the save button row
        $this->assertStringNotContainsString('</div></div>' . "\n" . '                                    </div>' . "\n" . '                                </div>', $indexView);
    }
}
