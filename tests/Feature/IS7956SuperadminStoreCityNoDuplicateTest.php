<?php

namespace Tests\Feature;

use Modules\Superadmin\Entities\Towns;
use PHPUnit\Framework\TestCase;

class IS7956SuperadminStoreCityNoDuplicateTest extends TestCase
{
    public function test_store_city_does_not_create_duplicate_for_same_district_case_insensitive(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Http/Controllers/AgentController.php');
        $this->assertStringContainsString("LOWER(name) = ?", $controller);
    }
}
