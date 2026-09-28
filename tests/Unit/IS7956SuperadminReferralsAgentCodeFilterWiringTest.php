<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminReferralsAgentCodeFilterWiringTest extends TestCase
{
    public function test_list_agents_has_agent_code_filter_and_backend_applies_it(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $filtersView = file_get_contents($projectRoot . '/Modules/Superadmin/Resources/views/agents/partials/list_agents_tab.blade.php');
        $indexTabJs = file_get_contents($projectRoot . '/Modules/Superadmin/Resources/views/agents/index_tab.blade.php');
        $agentController = file_get_contents($projectRoot . '/Modules/Superadmin/Http/Controllers/AgentController.php');

        $this->assertStringContainsString("Form::label('agent_code'", $filtersView);
        $this->assertStringContainsString("Form::select('agent_code'", $filtersView);

        $this->assertStringContainsString("d.agent_code = $('#agent_code').val();", $indexTabJs);
        $this->assertStringContainsString("#agent_code", $indexTabJs);

        $this->assertStringContainsString("request()->agent_code", $agentController);
        $this->assertStringContainsString("agents.agent_code", $agentController);
    }
}

