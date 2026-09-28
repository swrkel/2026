<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PurchaseTaxJSConfirmTest extends TestCase
{
    /** @test */
    public function purchase_js_has_tax_manually_set_logic(): void
    {
        $file = __DIR__ . '/../../public/js/purchase.js';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        // Asserts related to the manual set tracking
        $this->assertStringContainsString('tax_manually_set', $contents);
        $this->assertStringContainsString('!__skipPurchaseTaxConfirm', $contents);
    }
}
