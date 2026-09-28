<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS7955Pos2PriceIncTaxTest extends TestCase
{
    public function test_pos2_recalculation_uses_original_hidden_inc_tax_price_in_inclusive_mode(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $pos2Js = file_get_contents($projectRoot . '/public/js/pos2.js');

        $this->assertIsString($pos2Js);
        $this->assertStringContainsString('input.original_pos_unit_price_inc_tax', $pos2Js);
        $this->assertStringNotContainsString('original_unit_price_inc_tax = unit_price;', $pos2Js);
        $this->assertStringContainsString('original_unit_price_inc_tax = __read_number(', $pos2Js);
    }
}
