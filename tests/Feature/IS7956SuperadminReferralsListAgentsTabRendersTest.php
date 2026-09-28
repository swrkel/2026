<?php

namespace Tests\Feature;

use Tests\TestCase;

class IS7956SuperadminReferralsListAgentsTabRendersTest extends TestCase
{
    public function test_list_agents_tab_renders_even_when_filter_lists_are_missing(): void
    {
        $html = view('superadmin::agents.partials.list_agents_tab')->render();

        $this->assertIsString($html);
        $this->assertStringContainsString('country_id', $html);
        $this->assertStringContainsString('city', $html);
        $this->assertStringContainsString('referral_code', $html);
    }
}

