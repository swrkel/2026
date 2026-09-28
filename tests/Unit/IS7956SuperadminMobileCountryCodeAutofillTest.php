<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminMobileCountryCodeAutofillTest extends TestCase
{
    public function test_country_change_autofills_mobile_inputs_with_plus_prefix(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Resources/views/agents/create.blade.php');

        $this->assertStringContainsString('function applyCountryCodeToMobiles()', $view);
        $this->assertStringContainsString("if (!currentVal) {", $view);
        $this->assertStringContainsString("$(this).val(prefix);", $view);
    }
}

