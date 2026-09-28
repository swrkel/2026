<?php

namespace Tests\Unit;

use Tests\TestCase;
use Modules\Superadmin\Entities\ModulePermissionLocation;

class SuperadminModulePermissionLocationTest extends TestCase
{
    /** @test */
    public function pump_operator_module_is_in_module_permission_list(): void
    {
        $list = ModulePermissionLocation::getModulePermissionList();
        $this->assertContains('pump_operator_module', $list);
    }
}
