<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminAgentStoreDuplicateCityReferralReturnsJsonTest extends TestCase
{
    public function test_store_duplicate_city_referral_has_ajax_json_response_path(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Http/Controllers/AgentController.php');

        $matches = [];
        $this->assertSame(1, preg_match('/if\\s*\\(\\$same_city_referral\\)\\s*\\{([\\s\\S]*?)\\n\\s*\\}/', $controller, $matches));
        $block = $matches[1];

        $this->assertStringNotContainsString('redirect()->back()', $block);
        $this->assertStringContainsString('return response()->json', $block);
    }
}
