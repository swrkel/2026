<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PetroSettlementEditMechanicalMeterTest extends TestCase
{
    /** @test */
    public function settlement_edit_view_wires_mechanical_meter_button(): void
    {
        // Regression: on Petro / List direct settlements / Action / Edit (and
        // Edit-no-change) the Mechanical Meter button renders (it's part of
        // the shared meter_sale_form partial) but clicking it does nothing
        // because the entry modal HTML and the #mechanical_meter_btn click
        // handlers live inline in create.blade.php and are not loaded by
        // edit.blade.php. The edit view must include the same wiring so the
        // button opens the Mechanical Meter modal exactly like on create.
        $edit = file_get_contents(
            __DIR__ . '/../../Modules/Petro/Resources/views/settlement/edit.blade.php'
        );

        // The edit view must pull in the mechanical meter modal + click
        // wiring (either inline or via a shared partial). We check for an
        // @include of a shared partial as the canonical wiring; if it's
        // inlined instead, the inline markup/handler must be present.
        $hasInclude = (bool) preg_match(
            "/@include\\(\\s*['\"]petro::settlement\\.partials\\.mechanical_meter_entry['\"]/",
            $edit
        );

        if (! $hasInclude) {
            $this->assertStringContainsString(
                'id="mechanical_meter_modal"',
                $edit,
                'Edit view must render the #mechanical_meter_modal that the Mechanical Meter button opens.'
            );

            $this->assertMatchesRegularExpression(
                '/on\(\s*[\'"]click[^\'"]*[\'"]\s*,\s*[\'"]#mechanical_meter_btn[\'"]/',
                $edit,
                'Edit view must bind a click handler to #mechanical_meter_btn (same wiring as the create page).'
            );

            $this->assertStringContainsString(
                "$('#mechanical_meter_modal').modal('show')",
                $edit,
                'Edit view must show the #mechanical_meter_modal when the button is clicked.'
            );
        } else {
            // Shared partial path — verify the partial itself wires the button.
            $partialPath = __DIR__ . '/../../Modules/Petro/Resources/views/settlement/partials/mechanical_meter_entry.blade.php';
            $this->assertFileExists($partialPath, 'Shared mechanical_meter_entry partial must exist.');

            $partial = file_get_contents($partialPath);

            $this->assertStringContainsString(
                'id="mechanical_meter_modal"',
                $partial,
                'Shared partial must render the #mechanical_meter_modal.'
            );

            $this->assertMatchesRegularExpression(
                '/on\(\s*[\'"]click[^\'"]*[\'"]\s*,\s*[\'"]#mechanical_meter_btn[\'"]/',
                $partial,
                'Shared partial must bind a click handler to #mechanical_meter_btn.'
            );

            $this->assertStringContainsString(
                "$('#mechanical_meter_modal').modal('show')",
                $partial,
                'Shared partial must show the #mechanical_meter_modal on button click.'
            );
        }
    }
}
