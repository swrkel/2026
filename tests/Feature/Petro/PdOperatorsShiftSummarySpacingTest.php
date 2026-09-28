<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PdOperatorsShiftSummarySpacingTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function shift_summary_widget_has_row_and_col_wrapper_for_spacing(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
                'currency' => $this->currencySessionData(),
            ])
            ->get('/petropd/pd-operators?tab=shift_summary');

        $response->assertOk();
        
        $html = $response->getContent();
        
        // Assert that the widget containing pump_operators_shift_summary_table is wrapped in row and col-md-12
        $this->assertMatchesRegularExpression(
            '/<div class="row">\s*<div class="col-md-12">\s*<div class="box box-primary[^>]*>[\s\S]*?id="pump_operators_shift_summary_table"/',
            $html
        );
    }
}
