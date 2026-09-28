<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class IS1286PrefixStartingNoMinTest extends TestCase
{
    public function test_prefix_starting_no_allows_zero_in_modals(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        $create = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/prefix/create.blade.php');
        $edit = file_get_contents($projectRoot . '/Modules/Distribution/Resources/views/settings/prefix/edit.blade.php');

        $this->assertIsString($create);
        $this->assertIsString($edit);

        $this->assertStringContainsString("'min' => 0", $create);
        $this->assertStringContainsString("'min' => 0", $edit);
    }
}

