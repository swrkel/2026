<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminReferralsAgentNameMobileDropdownFiltersTest extends TestCase
{
    public function test_list_agents_filters_use_dropdowns_for_agent_name_and_mobile_number(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $filtersView = file_get_contents($projectRoot . '/Modules/Superadmin/Resources/views/agents/partials/list_agents_tab.blade.php');
        $indexTabJs = file_get_contents($projectRoot . '/Modules/Superadmin/Resources/views/agents/index_tab.blade.php');
        $referralController = file_get_contents($projectRoot . '/Modules/Superadmin/Http/Controllers/ReferralController.php');
        $agentController = file_get_contents($projectRoot . '/Modules/Superadmin/Http/Controllers/AgentController.php');

        $this->assertStringContainsString("Form::select('agent_name'", $filtersView);
        $this->assertStringContainsString("Form::select('mobile_number'", $filtersView);

        $this->assertStringContainsString("d.agent_name = $('#agent_name').val();", $indexTabJs);
        $this->assertStringContainsString("d.mobile_number = $('#mobile_number').val();", $indexTabJs);
        $this->assertStringContainsString("#agent_name", $indexTabJs);
        $this->assertStringContainsString("#mobile_number", $indexTabJs);

        $this->assertStringContainsString("#agent_name, #mobile_number", $indexTabJs);
        $this->assertStringNotContainsString("on('keyup'", $indexTabJs);

        $this->assertStringContainsString("request()->agent_name", $agentController);
        $this->assertStringContainsString("request()->mobile_number", $agentController);

        $this->assertStringContainsString("agent_names", $referralController);
        $this->assertStringContainsString("mobile_numbers", $referralController);
    }
}

