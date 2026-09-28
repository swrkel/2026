<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminReferralCodePlusButtonUsesAjaxTest extends TestCase
{
    public function test_agent_create_referral_code_plus_button_uses_ajax_not_prompt(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Resources/views/agents/create.blade.php');

        $this->assertStringNotContainsString('enter_new_referral_code', $view);
        $this->assertStringContainsString('agents/next-referral-code', $view);
    }
}
