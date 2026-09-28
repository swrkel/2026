<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
class TempTest extends TestCase {
    public function test_output() {
        $html = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Resources/views/agents/partials/list_agents_tab.blade.php');
        $this->assertStringContainsString('filter-select2', $html);
    }
}
