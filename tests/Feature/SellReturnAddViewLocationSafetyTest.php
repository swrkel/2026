<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class SellReturnAddViewLocationSafetyTest extends TestCase
{
    public function test_sell_return_add_view_handles_null_location_safely(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/resources/views/sell_return/add.blade.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        
        $this->assertStringContainsString('$sell->location->id ??', $contents);
        $this->assertStringContainsString('$sell->location->receipt_printer_type ??', $contents);
        $this->assertStringContainsString('$sell->location->name ??', $contents);
    }

    public function test_sell_return_add_view_contains_invoice_url_link(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/resources/views/sell_return/add.blade.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        
        $this->assertStringContainsString('class="view_invoice_url"', $contents);
    }
}
