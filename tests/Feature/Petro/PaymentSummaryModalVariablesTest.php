<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class PaymentSummaryModalVariablesTest extends PetroTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_payment_summary_modal_renders_successfully(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();
        $user->pump_operator_id = $this->pumpOperatorId;
        $user->is_pump_operator = 1;
        $user->save();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $user->id,
            ])
            ->get('/pumper-dashboard/pump-operator-payments/get-modal?only_pumper=1');

        $response->assertStatus(200);
        $response->assertSee('payment_summary_pump_operators');
        $response->assertSee('payment_summary_shift_id');
        $response->assertSee('payment_summary_payment_method');
        $response->assertSee('payment_summary_customer');
        $response->assertSee('payment_summary_slip_no');
        $response->assertSee('payment_summary_order_no');
    }
}
