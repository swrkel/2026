<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminAddedByFilterSearchesByAgentCodeTest extends TestCase
{
    public function test_referral_index_builds_added_by_filter_labels_with_agent_code(): void
    {
        $controller = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Http/Controllers/ReferralController.php');

        $matches = [];
        $this->assertSame(1, preg_match('/\\$added_bys\\s*=\\s*([\\s\\S]+?);/', $controller, $matches));
        $assignment = $matches[0];

        $this->assertStringContainsString('agent_code', $assignment);
    }
}

