<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class IS7956SuperadminReferralsModalAjaxReloadTest extends TestCase
{
    public function test_superadmin_referrals_modal_forms_submit_via_ajax_and_reload_tables(): void
    {
        $script = file_get_contents(__DIR__ . '/../../public/js/app.js');

        $this->assertStringContainsString("form#add_pumps_form", $script);
        $this->assertStringContainsString("income_method_table.ajax.reload", $script);
        $this->assertStringContainsString("referral_group_table.ajax.reload", $script);
        $this->assertStringContainsString("referral_starting_code_table.ajax.reload", $script);
    }
}

