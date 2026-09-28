<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminCityPlusButtonNoDuplicateOptionTest extends TestCase
{
    public function test_agent_create_city_plus_button_does_not_append_duplicate_option(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Resources/views/agents/create.blade.php');

        $this->assertStringContainsString("$('#add_city_btn').on('click'", $view);
        $this->assertStringContainsString("#agent_city option[value=\"", $view);
        $this->assertStringContainsString("length === 0", $view);
    }
}
