<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroSettlementEditDoesNotCreateDraftTest extends TestCase
{
    /** @test */
    public function settlement_create_if_not_exist_reuses_finalized_settlement_during_edit(): void
    {
        // Regression: editing a finalized direct settlement (status=0) and
        // clicking Add on any sub-item endpoint (saveOtherSale,
        // saveMeterSale, ...) was creating a brand-new "Pending" Settlement
        // each time. createSettlementIfNotExist only looked up the
        // active settlement when its status was 1 (draft), so finalized
        // settlements were treated as missing and a fresh draft was
        // spawned. When active_settlement_id is provided (the edit page
        // sends it), the existing settlement must be reused regardless of
        // its status.
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Http/Controllers/SettlementController.php'
        );

        $methodStart = strpos($controller, 'public function createSettlementIfNotExist');
        $this->assertNotFalse(
            $methodStart,
            'Could not locate createSettlementIfNotExist in SettlementController.'
        );

        $methodBody = substr($controller, $methodStart, 4000);

        // The active-settlement lookup block must NOT restrict to status=1
        // anymore; it should accept the existing settlement regardless of
        // its status when active_settlement_id and pump_operator_id are
        // present.
        $this->assertDoesNotMatchRegularExpression(
            '/Settlement::where\([^)]*\'id\',\s*\$active_settlement_id\)[^;]*->where\(\'status\',\s*1\)/s',
            $methodBody,
            'createSettlementIfNotExist must not require status=1 when looking up the active settlement; finalized settlements being edited must be reused, not replaced by a fresh draft.'
        );
    }
}
