<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminAgentCodeFormatTest extends TestCase
{
    public function test_generate_next_agent_code_uses_hyphenated_ag_prefix(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Http/Controllers/AgentController.php');

        $this->assertStringContainsString("return 'AG-' .", $controller);
    }
}

