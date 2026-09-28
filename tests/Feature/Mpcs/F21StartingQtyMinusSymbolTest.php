<?php

namespace Tests\Feature\Mpcs;

use App\Business;
use App\User;
use App\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class F21StartingQtyMinusSymbolTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_f21_starting_qty_has_no_minus_symbol()
    {
        $business = Business::first();
        if (!$business) {
            $this->markTestSkipped('No business');
        }
        $user = User::where('business_id', $business->id)->first();

        // Retrieve controller instance
        $controller = app(\Modules\MPCS\Http\Controllers\F21FormController::class);

        // Set Laravel session on global request for testing
        $session = app('session.store');
        $session->put('business.id', $business->id);
        $session->put('user.business_id', $business->id);
        request()->setLaravelSession($session);

        // Retrieve controller instance
        $reflector = new \ReflectionClass(\Modules\MPCS\Http\Controllers\F21FormController::class);

        // Let's create a test row with an explicitly negative balance
        $rowWithNegativeBalance = (object)[
            'transaction_type' => 'Opening Stock',
            'product_id' => 9999,
            'location_id' => 1,
            'transaction_date' => '2026-05-15 12:00:00',
            'balance_qty' => -362.80,
        ];

        $computeMethod = $reflector->getMethod('computeF21StartingQty');
        $computeMethod->setAccessible(true);

        // Compute starting qty with $absolute = false: should have minus sign
        $qtyWithMinus = $computeMethod->invoke($controller, $rowWithNegativeBalance, false);
        $this->assertStringContainsString('-', $qtyWithMinus);

        // Compute starting qty with $absolute = true: should NOT have minus sign
        $qtyWithoutMinus = $computeMethod->invoke($controller, $rowWithNegativeBalance, true);
        $this->assertStringNotContainsString('-', $qtyWithoutMinus);
        $this->assertEquals('362.80', $qtyWithoutMinus);
    }
}
