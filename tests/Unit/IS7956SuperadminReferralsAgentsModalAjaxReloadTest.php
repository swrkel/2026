<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminReferralsAgentsModalAjaxReloadTest extends TestCase
{
    public function test_superadmin_agents_modal_forms_submit_via_ajax_and_reload_agents_table(): void
    {
        $script = file_get_contents(__DIR__ . '/../../public/js/app.js');

        $this->assertStringContainsString("form#add_agent_form", $script);
        $this->assertStringContainsString("form#edit_agent", $script);
        $this->assertStringContainsString("agents_table.ajax.reload", $script);
    }
}

