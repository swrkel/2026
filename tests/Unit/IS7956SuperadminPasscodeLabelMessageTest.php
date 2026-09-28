<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminPasscodeLabelMessageTest extends TestCase
{
    public function test_agent_created_message_labels_passcode_as_passcode(): void
    {
        $lang = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Resources/lang/en/lang.php');
        $this->assertStringContainsString("'agent_created_with_credentials' => 'Success | Agent Code: :agent_code | Passcode: :passcode'", $lang);
    }
}
