<?php

namespace Tests\Feature;

use Tests\TestCase;

class LangV1TranslationSyncTest extends TestCase
{
    public function test_other_languages_have_pay_due_amount_key()
    {
        $locales = ['ar', 'ce', 'de', 'es', 'fr', 'hi', 'id', 'nl', 'ps', 'pt', 'si', 'sq', 'ta', 'tr', 'vi'];
        
        foreach ($locales as $locale) {
            $filePath = base_path("resources/lang/{$locale}/lang_v1.php");
            $this->assertFileExists($filePath, "Translation file missing for locale: {$locale}");
            
            $translations = include $filePath;
            $this->assertIsArray($translations, "Translation file is not an array: {$locale}");
            
            $this->assertArrayHasKey('pay_due_amount', $translations, "Key 'pay_due_amount' is missing in locale: {$locale}");
        }
    }
}
