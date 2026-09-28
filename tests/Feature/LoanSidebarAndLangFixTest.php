<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoanSidebarAndLangFixTest extends TestCase
{
    public function test_sidebar_has_correct_title_and_no_missing_translations_or_stray_quotes(): void
    {
        // 1. Assert correct translation for loan module
        $this->assertEquals('Loan Module', __('loan::lang.loan'));

        // 2. Assert navigation view does not contain broken keys/stray quotes
        $navContent = file_get_contents(base_path('Modules/Loan/Resources/views/layouts/nav.blade.php'));
        $this->assertStringNotContainsString('core.bulk', $navContent);
        $this->assertStringNotContainsString('core.settings', $navContent);
        $this->assertStringNotContainsString('core.report', $navContent);
        $this->assertStringNotContainsString("'\n", $navContent);
        $this->assertStringNotContainsString("'                   ", $navContent);

        // 3. Assert terms view does not contain broken keys
        $termsContent = file_get_contents(base_path('Modules/Loan/Resources/views/loan/partials/terms.blade.php'));
        $this->assertStringNotContainsString('core.type', $termsContent);
        $this->assertStringNotContainsString('lang_v1.variations', $termsContent);
    }
}
