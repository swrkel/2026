<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS1286Pos2PriceIncTaxColumnTest extends TestCase
{
    public function test_pos2_product_row_contains_price_inc_tax_column_markup(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $path = $projectRoot . '/Modules/POS2/Resources/views/product_row.blade.php';
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString('price_inc_tax_column', $contents);
        $this->assertStringContainsString('price_inc_tax_display', $contents);
    }
}

