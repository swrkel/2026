<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PumperDayEntryPaymentActionTest extends TestCase
{
    /** @test */
    public function day_entries_table_exposes_payment_type_column(): void
    {
        $view = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Resources/views/pumper_day_entries.blade.php'
        );
        $partial = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Resources/views/partials/pumper_day_entries.blade.php'
        );
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Http/Controllers/PumperDayEntryController.php'
        );

        $this->assertStringContainsString("@lang('pumperdashboard::lang.payment_type')", $partial);
        $this->assertStringContainsString("data: 'payment_type'", $view);
        $this->assertStringContainsString("'payment_type' => 'Credit'", $controller);
        $this->assertStringContainsString("'payment_type' => 'Card'", $controller);
        $this->assertStringContainsString("'payment_type' => 'Cash'", $controller);
    }

    /** @test */
    public function day_entries_action_column_renders_payment_edit_modal_links_for_admins(): void
    {
        $controller = file_get_contents(
            __DIR__ . '/../../Modules/PumperDashboard/Http/Controllers/PumperDayEntryController.php'
        );

        $actionColumnStart = strpos($controller, "'action',");
        $this->assertNotFalse($actionColumnStart, 'The Day Entries action column could not be found.');

        $actionColumnEnd = strpos($controller, "->addColumn('name'", $actionColumnStart);
        $this->assertNotFalse($actionColumnEnd, 'The Day Entries action column end could not be found.');

        $actionColumn = substr($controller, $actionColumnStart, $actionColumnEnd - $actionColumnStart);

        $this->assertStringContainsString(
            "in_array(\$row->row_type, ['credit_sale', 'card_payment', 'cash_payment'], true)",
            $actionColumn,
            'Admin users must get edit actions for Day Entries payment rows.'
        );

        $this->assertStringContainsString(
            "'credit_sale' => 'credit'",
            $actionColumn,
            'Credit sale rows must open the payment edit modal as credit payments.'
        );

        $this->assertStringContainsString(
            "'card_payment' => 'card'",
            $actionColumn,
            'Card payment rows must open the payment edit modal as card payments.'
        );

        $this->assertStringContainsString(
            "'cash_payment' => 'cash'",
            $actionColumn,
            'Cash payment rows must open the payment edit modal as cash payments.'
        );

        $this->assertStringContainsString(
            "'&credit_sale_id=' . urlencode(\$row->id)",
            $actionColumn,
            'Credit sale edit links must carry the selected credit-sale row id.'
        );

        $this->assertStringContainsString(
            "url('pumper-dashboard/pump-operators/payment/' . \$row->id . '/edit')",
            $actionColumn,
            'Payment rows must reuse the existing PumperDashboard payment edit modal URL.'
        );
    }
}
