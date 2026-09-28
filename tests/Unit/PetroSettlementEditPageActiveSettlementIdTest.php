<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroSettlementEditPageActiveSettlementIdTest extends TestCase
{
    /** @test */
    public function settlement_edit_view_renders_active_settlement_id_hidden_input(): void
    {
        // Regression: editing a finalized direct settlement and clicking
        // Add on a sub-item endpoint was creating new "Pending" settlements
        // (ST342, ST343, ...) because the edit view never rendered
        // <input id="active_settlement_id">. The save AJAX therefore posted
        // active_settlement_id=0, which made createSettlementIfNotExist
        // skip the active-settlement reuse branch and fall through to the
        // "settlement_no exists but is finalized → create a new draft"
        // branch.
        $edit = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Resources/views/settlement/edit.blade.php'
        );

        $this->assertMatchesRegularExpression(
            '/id="active_settlement_id"\s+value="\{\{\s*\$active_settlement->id[^}]*\}\}"/',
            $edit,
            'settlement edit.blade.php must render the #active_settlement_id hidden input so sub-item save endpoints can reuse the active settlement instead of spawning new Pending drafts.'
        );
    }
}
