<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class DistributionInvoiceListActionTest extends TestCase
{
    public function test_distribution_invoice_list_controller_contains_invoice_url_action(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceListController.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        
        $this->assertStringContainsString('SellPosController@showInvoiceUrl', $contents);
        $this->assertStringContainsString('class="view_invoice_url"', $contents);
    }

    public function test_distribution_invoice_list_controller_formats_date_using_created_at(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceListController.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString("editColumn('date', '{{@format_datetime(\$created_at)}}')", $contents);
    }

    public function test_distribution_invoice_list_controller_handles_payment_credit_and_hides_view_payment_when_due(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceListController.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);

        // Assert payment status uses actual paid logic (subtracting payment_credit)
        $this->assertStringContainsString('$actual_paid = $row->payment_total - $row->payment_credit;', $contents);
        // Assert View Payment button is shown only if actual paid is greater than 0
        $this->assertStringContainsString('$actual_paid > 0', $contents);
    }

    public function test_distribution_invoice_controller_redirects_to_list_invoices_index(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceController.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString("redirect()->route('distribution.list_invoices.index')", $contents);
    }

    public function test_sales_orders_views_use_correct_json_keys(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        
        foreach (['create.blade.php', 'edit.blade.php', 'index.blade.php'] as $view) {
            $filePath = $projectRoot . '/Modules/Distribution/Resources/views/sales_orders/' . $view;
            $this->assertFileExists($filePath);
            $contents = file_get_contents($filePath);
            $this->assertIsString($contents);

            // Assert that it parses the correct keys: data.unit_price and data.units
            $this->assertStringContainsString('data.unit_price', $contents);
            $this->assertStringContainsString('data.units', $contents);
        }
    }

    public function test_sales_orders_views_use_select2_for_all_dropdowns(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        
        foreach (['create.blade.php', 'edit.blade.php', 'index.blade.php'] as $view) {
            $filePath = $projectRoot . '/Modules/Distribution/Resources/views/sales_orders/' . $view;
            $this->assertFileExists($filePath);
            $contents = file_get_contents($filePath);
            $this->assertIsString($contents);

            // Assert that temp_tax_type and temp_discount_type have select2 class
            $this->assertMatchesRegularExpression('/id="temp_tax_type"[^>]*class="[^"]*select2/', $contents);
            $this->assertMatchesRegularExpression('/id="temp_discount_type"[^>]*class="[^"]*select2/', $contents);
        }
    }
}
