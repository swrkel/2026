<?php

namespace Modules\LeadsNew\Tests\Feature;

use Tests\TestCase;

class LeadsNewSmokeTest extends TestCase
{
    public function test_module_constant_exists(): void
    {
        $this->assertTrue(class_exists(\Modules\LeadsNew\Support\LeadsNewSettingsDefaults::class));
    }
}
