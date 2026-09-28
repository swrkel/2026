<?php

namespace Tests\Feature;

use Tests\TestCase;

class ReportJsFixTest extends TestCase
{
    /**
     * Verify that report.js does not contain the undefined typo __number_format.
     *
     * @return void
     */
    public function test_report_js_does_not_contain_undefined_number_format_helper()
    {
        $filePath = public_path('js/report.js');
        $this->assertFileExists($filePath);

        $content = file_get_contents($filePath);
        
        // Assert that the undefined function __number_format is NOT present in the JS file.
        $this->assertStringNotContainsString('__number_format', $content);
    }
}
