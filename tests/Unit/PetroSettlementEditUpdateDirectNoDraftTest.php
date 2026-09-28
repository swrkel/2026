<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroSettlementEditUpdateDirectNoDraftTest extends TestCase
{
    /** @test */
    public function direct_settlement_update_keeps_finalized_direct_row_when_operator_has_no_assigned_shifts(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementController.php'
        );

        $methodStart = strpos($controller, 'public function update(Request $request, $id)');
        $this->assertNotFalse(
            $methodStart,
            'Could not locate update in SettlementController.'
        );

        $methodBody = substr($controller, $methodStart, 900);

        $this->assertDoesNotMatchRegularExpression(
            '/\$currentSettlementIsDirectDraft\s*=\s*\(int\)\s*\$settlement->status\s*===\s*1\s*&&\s*!\s*empty\(\$this->normalizeDirectSettlementShiftLabel/s',
            $methodBody,
            'Editing a finalized direct settlement must not force id=0 and create a new Pending settlement when changing details.'
        );
    }

    /** @test */
    public function edit_page_sub_item_saves_post_active_settlement_id_to_reuse_finalized_direct_settlement(): void
    {
        $edit = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Resources/views/settlement/edit.blade.php'
        );

        $this->assertStringContainsString(
            'active_settlement_id:',
            $edit,
            'Direct settlement edit sub-item AJAX saves must post active_settlement_id so finalized settlements are reused.'
        );
    }

    /** @test */
    public function add_payment_create_if_not_exist_reuses_active_settlement_id_before_settlement_number_lookup(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/AddPaymentController.php'
        );

        $methodStart = strpos($controller, 'public function createSettlementIfNotExist(Request $request)');
        $this->assertNotFalse(
            $methodStart,
            'Could not locate createSettlementIfNotExist in AddPaymentController.'
        );

        $methodBody = substr($controller, $methodStart, 2200);

        $this->assertStringContainsString(
            "input('active_settlement_id'",
            $methodBody,
            'AddPaymentController payment saves must reuse the active settlement id from edit pages.'
        );
    }

    /** @test */
    public function settlement_create_if_not_exist_never_converts_edit_of_finalized_direct_settlement_into_draft(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementController.php'
        );

        $methodStart = strpos($controller, 'public function createSettlementIfNotExist(Request $request)');
        $this->assertNotFalse(
            $methodStart,
            'Could not locate createSettlementIfNotExist in SettlementController.'
        );

        $methodBody = substr($controller, $methodStart, 2800);

        $this->assertStringContainsString(
            "input('is_edit'",
            $methodBody,
            'Edit requests for finalized direct settlements must reuse the finalized row instead of creating a draft.'
        );

        $this->assertStringContainsString(
            'return $finalized_edit_settlement;',
            $methodBody,
            'Finalized direct settlement edit requests must return the finalized settlement before draft generation.'
        );
    }
}
