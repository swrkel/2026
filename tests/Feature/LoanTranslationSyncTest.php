<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoanTranslationSyncTest extends TestCase
{
    public function test_other_languages_have_new_translation_keys()
    {
        $locales = ['ar', 'ce', 'de', 'es', 'fr', 'hi', 'id', 'lo', 'nl', 'ps', 'pt', 'ro', 'sq', 'tr', 'vi'];
        
        foreach ($locales as $locale) {
            $filePath = base_path("Modules/Loan/Resources/lang/{$locale}/lang.php");
            $this->assertFileExists($filePath, "Translation file missing for locale: {$locale}");
            
            $translations = include $filePath;
            $this->assertIsArray($translations, "Translation file is not an array: {$locale}");
            
            // Assert that new keys are defined
            $this->assertArrayHasKey('filter', $translations, "Key 'filter' is missing in locale: {$locale}");
            $this->assertArrayHasKey('clear', $translations, "Key 'clear' is missing in locale: {$locale}");
            $this->assertArrayHasKey('leave_empty_to_autogenerate', $translations, "Key 'leave_empty_to_autogenerate' is missing in locale: {$locale}");
            $this->assertArrayHasKey('set_default', $translations, "Key 'set_default' is missing in locale: {$locale}");
        }
    }
}
