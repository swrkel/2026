<?php

namespace Tests\Feature;

use PHPUnit\Framework\TestCase;

class ListInvoicesActivitiesModalTest extends TestCase
{
    public function test_list_invoices_page_contains_activity_log_modal_container(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Resources/views/invoices/list_invoices.blade.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString('class="modal fade activity_log_modal"', $contents);
    }

    public function test_list_invoices_page_contains_changed_details_click_handler(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Resources/views/invoices/list_invoices.blade.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString("$(document).on('click', '.btn-changed-details', function", $contents);
    }

    public function test_activity_log_popup_contains_changed_details_button_and_wrapper(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Resources/views/invoices/partials/activity_log_popup.blade.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString('class="btn btn-xs btn-default btn-changed-details"', $contents);
        $this->assertStringContainsString('class="changed-details-wrapper"', $contents);
    }

    public function test_lang_v1_has_changed_activities_and_changed_details_in_all_locales(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $locales = ['ar', 'ce', 'de', 'en', 'es', 'fr', 'hi', 'id', 'nl', 'ps', 'pt', 'si', 'sq', 'ta', 'tr', 'vi'];
        
        foreach ($locales as $locale) {
            $filePath = $projectRoot . "/resources/lang/{$locale}/lang_v1.php";
            $this->assertFileExists($filePath, "Translation file missing for locale: {$locale}");
            
            $translations = include $filePath;
            $this->assertIsArray($translations, "Translation file is not an array: {$locale}");
            
            $this->assertArrayHasKey('changed_activities', $translations, "Key 'changed_activities' is missing in locale: {$locale}");
            $this->assertArrayHasKey('changed_details', $translations, "Key 'changed_details' is missing in locale: {$locale}");
        }
    }

    public function test_list_invoices_controller_contains_payment_method_datatable_definition(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Http/Controllers/DistributionInvoiceListController.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString("addColumn('payment_method'", $contents);
        $this->assertStringContainsString("'payment_method'", $contents);
    }

    public function test_list_invoices_view_contains_payment_details_modal_and_js(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $filePath = $projectRoot . '/Modules/Distribution/Resources/views/invoices/list_invoices.blade.php';

        $this->assertFileExists($filePath);
        $contents = file_get_contents($filePath);

        $this->assertIsString($contents);
        $this->assertStringContainsString('id="invoicePaymentModal"', $contents);
        $this->assertStringContainsString("$(document).on('click', '.show-payment-btn', function", $contents);
        $this->assertStringContainsString("@lang('lang_v1.payment_method')", $contents);
    }
}
