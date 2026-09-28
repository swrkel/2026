<?php

namespace Tests\Feature\Petro;

use App\BusinessLocation;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\View;
use Modules\Petro\Entities\Pump;
use Modules\Petro\Entities\PumpOperator;
use App\Contact;
use Modules\Petro\Entities\PetroShift;

class PdOperatorsPaymentSummaryViewTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function pd_operators_index_includes_petropd_payment_summary_with_custom_id(): void
    {
        $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail());
        request()->setLaravelSession(app('session.store'));

        $businessLocations = BusinessLocation::forDropdown($this->businessId);
        $pumpOperators = PumpOperator::where('business_id', $this->businessId)->pluck('name', 'id');
        $pumps = Pump::where('business_id', $this->businessId)->get();
        $paymentTypes = ['cash' => 'Cash', 'card' => 'Card', 'cheque' => 'Cheque', 'credit' => 'Credit'];
        $customers = Contact::customersDropdown($this->businessId, false, true);
        $shifts = PetroShift::where('business_id', $this->businessId)->get();

        $html = View::make('petropd::pd_operators.index', [
            'business_locations' => $businessLocations,
            'pump_operators' => $pumpOperators,
            'pumps' => $pumps,
            'tanks' => collect([]),
            'products' => collect([]),
            'payment_types' => $paymentTypes,
            'customers' => $customers,
            'shifts' => $shifts,
            'pumperLoginAttempts' => collect([]),
            'default_location' => null,
            'settlement_nos' => [],
            'message' => '',
        ])->render();

        $this->assertStringContainsString('id="payment_summarys"', $html);
    }
}
