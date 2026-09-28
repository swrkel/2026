<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS1286Pos2TaxRateReadTest extends TestCase
{
    public function test_pos2_row_calculation_reads_tax_rate_from_tax_id_element(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $path = $projectRoot . '/public/js/pos2.js';
        $contents = file_get_contents($path);

        $this->assertIsString($contents);
        $this->assertStringContainsString('function pos_each_row', $contents);

        $this->assertStringContainsString('var tax_element = row_obj.find(".tax_id")', $contents);
        $this->assertStringContainsString('tax_element.is("select")', $contents);
        $this->assertStringContainsString('tax_element.data("rate")', $contents);
    }
}
