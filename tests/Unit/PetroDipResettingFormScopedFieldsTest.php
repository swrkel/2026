<?php

namespace Tests\Unit;

use Tests\TestCase;

class PetroDipResettingFormScopedFieldsTest extends TestCase
{
    /** @test */
    public function dip_resetting_submit_reads_values_from_submitted_form_scope(): void
    {
        $view = file_get_contents(base_path('Modules/Petro/Resources/views/dip_management/index.blade.php'));

        $this->assertStringContainsString('var $form = $(this);', $view);
        $this->assertStringContainsString("tank_id: \$form.find('#add_reset_tank_id').val()", $view);
        $this->assertStringContainsString("current_qty: \$form.find('input[name=current_qty]').val()", $view);
        $this->assertStringNotContainsString("tank_id: $('#add_reset_tank_id').val()", $view);
        $this->assertStringNotContainsString("current_qty: $('input[name=current_qty]').val()", $view);
    }
}
