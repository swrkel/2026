<?php

namespace Modules\LeadsNew\Tests\Feature;

use Tests\TestCase;

class LeadsNewStandaloneAuditTest extends TestCase
{
    public function test_stage_17_registry_is_available(): void
    {
        $this->assertTrue(class_exists(\Modules\LeadsNew\Reports\LeadsNewReportRegistry::class));
    }
}
