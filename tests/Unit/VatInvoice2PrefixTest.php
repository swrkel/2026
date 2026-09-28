<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class VatInvoice2PrefixTest extends TestCase
{
    /** @test */
    public function format_by_prefix_unit_vat_decimals_uses_localized_formatter(): void
    {
        $files = [
            __DIR__ . '/../../Modules/Vat/Resources/views/vat_invoice2/create.blade.php',
            __DIR__ . '/../../Modules/Vat/Resources/views/vat_invoice2/edit.blade.php',
            __DIR__ . '/../../Modules/Vat/Resources/views/vat_invoice2/create127.blade.php',
            __DIR__ . '/../../Modules/Vat/Resources/views/vat_invoice2/edit127.blade.php',
        ];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            // Assert that formatByPrefixUnitVatDecimals is present
            $this->assertStringContainsString('function formatByPrefixUnitVatDecimals', $contents, basename($file));
            
            // Assert that it uses the global __number_f helper
            $this->assertStringContainsString('__number_f', $contents, basename($file));

            // Assert that roundByPrefixUnitVatDecimals is present
            $this->assertStringContainsString('function roundByPrefixUnitVatDecimals', $contents, basename($file));
        }
    }
}
