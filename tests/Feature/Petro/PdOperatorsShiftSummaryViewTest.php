<?php

namespace Tests\Feature\Petro;

use App\BusinessLocation;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;

class PdOperatorsShiftSummaryViewTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function shift_summary_top_section_does_not_render_placeholder_333_values(): void
    {
        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail());
        request()->setLaravelSession(app('session.store'));

        $businessLocations = BusinessLocation::forDropdown($this->businessId);
        $pumpOperators = PumpOperator::where('business_id', $this->businessId)->pluck('name', 'id');
        $pumps = Pump::where('business_id', $this->businessId)->get();
        $paymentTypes = ['cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'credit' => 'Credit'];

        $html = View::make('petro::pump_operators.partials.shift_summary', [
            'business_locations' => $businessLocations,
            'pump_operators' => $pumpOperators,
            'pumps' => $pumps,
            'payment_types' => $paymentTypes,
        ])->render();

        $this->assertStringContainsString('Filters', $html);
        $this->assertStringContainsString('No of Pumps Today', $html);
        $this->assertLessThan(
            strpos($html, 'No of Pumps Today'),
            strpos($html, 'Filters')
        );
        $this->assertStringNotContainsString('<h3>333</h3>', $html);
    }
}
