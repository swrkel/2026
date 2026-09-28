<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class RealtimePaymentSummarySearchTest extends PetroTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (\Illuminate\Support\Facades\Schema::connection('system')->hasTable('subscriptions')) {
            DB::connection('system')->table('subscriptions')
                ->where('business_id', $this->businessId)
                ->update([
                    'package_details' => json_encode([
                        'petro_pd_module' => 1,
                        'real_time_entries' => 1,
                    ])
                ]);
        }
    }

    /** @test */
    public function search_in_payment_summary_does_not_throw_sql_errors(): void
    {
        $paymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'credit',
            'payment_amount' => 500.00,
        ]);

        $queryString = 'draw=1&start=0&length=10&search%5Bvalue%5D=credit&search%5Bregex%5D=false';
        $queryString .= '&columns%5B0%5D%5Bdata%5D=action&columns%5B0%5D%5Bname%5D=action&columns%5B0%5D%5Bsearchable%5D=false&columns%5B0%5D%5Borderable%5D=false';
        $queryString .= '&columns%5B1%5D%5Bdata%5D=date&columns%5B1%5D%5Bname%5D=date&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B2%5D%5Bdata%5D=location_name&columns%5B2%5D%5Bname%5D=business_locations.name&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B3%5D%5Bdata%5D=time&columns%5B3%5D%5Bname%5D=time&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B4%5D%5Bdata%5D=pump_operator_name&columns%5B4%5D%5Bname%5D=pump_operators.name&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B5%5D%5Bdata%5D=shift_number&columns%5B5%5D%5Bname%5D=shift_number&columns%5B5%5D%5Bsearchable%5D=true&columns%5B5%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B6%5D%5Bdata%5D=collection_form_no&columns%5B6%5D%5Bname%5D=collection_form_no&columns%5B6%5D%5Bsearchable%5D=true&columns%5B6%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B7%5D%5Bdata%5D=payment_type&columns%5B7%5D%5Bname%5D=payment_type&columns%5B7%5D%5Bsearchable%5D=true&columns%5B7%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B8%5D%5Bdata%5D=customer_name&columns%5B8%5D%5Bname%5D=customer_name&columns%5B8%5D%5Bsearchable%5D=true&columns%5B8%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B9%5D%5Bdata%5D=slip_no&columns%5B9%5D%5Bname%5D=slip_no&columns%5B9%5D%5Bsearchable%5D=true&columns%5B9%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B10%5D%5Bdata%5D=order_no&columns%5B10%5D%5Bname%5D=order_no&columns%5B10%5D%5Bsearchable%5D=true&columns%5B10%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B11%5D%5Bdata%5D=cheque_no&columns%5B11%5D%5Bname%5D=cheque_no&columns%5B11%5D%5Bsearchable%5D=true&columns%5B11%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B12%5D%5Bdata%5D=amount&columns%5B12%5D%5Bname%5D=amount&columns%5B12%5D%5Bsearchable%5D=true&columns%5B12%5D%5Borderable%5D=true';

        $searchParams = [];
        parse_str($queryString, $searchParams);

        $searchResponse = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->call('GET', '/real-time-entries/payment-summary', $searchParams, [], [], [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $searchResponse->assertOk();
        $this->assertEquals(1, $searchResponse->json('recordsFiltered'));
        $this->assertArrayHasKey('data', $searchResponse->json());
    }

    /** @test */
    public function shifts_dropdown_contains_no_duplicate_shift_ids(): void
    {
        // 1. Create a petro shift
        $shiftId = DB::table('petro_shifts')->insertGetId([
            'business_id' => $this->businessId,
            'pump_operator_id' => $this->pumpOperatorId,
            'shift_date' => now()->toDateString(),
            'status' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create multiple assignments for this same shift and operator
        DB::table('pump_operator_assignments')->insert([
            [
                'business_id' => $this->businessId,
                'shift_id' => $shiftId,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => 1,
                'shift_number' => '99',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'business_id' => $this->businessId,
                'shift_id' => $shiftId,
                'pump_operator_id' => $this->pumpOperatorId,
                'pump_id' => 2,
                'shift_number' => '99',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->get('/real-time-entries/payment-summary');

        $response->assertOk();
        $shifts = $response->viewData('shifts');

        $matchingShifts = $shifts->where('id', $shiftId);
        $this->assertCount(1, $matchingShifts, "Shifts dropdown contains duplicate shift entries for shift ID {$shiftId}");
    }

    /** @test */
    public function customer_column_for_cash_payment_displays_walkin_customer(): void
    {
        $paymentId = $this->seedPumpOperatorPayment([
            'payment_type' => 'cash',
            'payment_amount' => 120.00,
        ]);

        $queryString = 'draw=1&start=0&length=10&search%5Bvalue%5D=cash&search%5Bregex%5D=false';
        $queryString .= '&columns%5B0%5D%5Bdata%5D=action&columns%5B0%5D%5Bname%5D=action&columns%5B0%5D%5Bsearchable%5D=false&columns%5B0%5D%5Borderable%5D=false';
        $queryString .= '&columns%5B1%5D%5Bdata%5D=date&columns%5B1%5D%5Bname%5D=date&columns%5B1%5D%5Bsearchable%5D=true&columns%5B1%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B2%5D%5Bdata%5D=location_name&columns%5B2%5D%5Bname%5D=business_locations.name&columns%5B2%5D%5Bsearchable%5D=true&columns%5B2%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B3%5D%5Bdata%5D=time&columns%5B3%5D%5Bname%5D=time&columns%5B3%5D%5Bsearchable%5D=true&columns%5B3%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B4%5D%5Bdata%5D=pump_operator_name&columns%5B4%5D%5Bname%5D=pump_operators.name&columns%5B4%5D%5Bsearchable%5D=true&columns%5B4%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B5%5D%5Bdata%5D=shift_number&columns%5B5%5D%5Bname%5D=shift_number&columns%5B5%5D%5Bsearchable%5D=true&columns%5B5%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B6%5D%5Bdata%5D=collection_form_no&columns%5B6%5D%5Bname%5D=collection_form_no&columns%5B6%5D%5Bsearchable%5D=true&columns%5B6%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B7%5D%5Bdata%5D=payment_type&columns%5B7%5D%5Bname%5D=payment_type&columns%5B7%5D%5Bsearchable%5D=true&columns%5B7%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B8%5D%5Bdata%5D=customer_name&columns%5B8%5D%5Bname%5D=customer_name&columns%5B8%5D%5Bsearchable%5D=true&columns%5B8%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B9%5D%5Bdata%5D=slip_no&columns%5B9%5D%5Bname%5D=slip_no&columns%5B9%5D%5Bsearchable%5D=true&columns%5B9%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B10%5D%5Bdata%5D=order_no&columns%5B10%5D%5Bname%5D=order_no&columns%5B10%5D%5Bsearchable%5D=true&columns%5B10%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B11%5D%5Bdata%5D=cheque_no&columns%5B11%5D%5Bname%5D=cheque_no&columns%5B11%5D%5Bsearchable%5D=true&columns%5B11%5D%5Borderable%5D=true';
        $queryString .= '&columns%5B12%5D%5Bdata%5D=amount&columns%5B12%5D%5Bname%5D=amount&columns%5B12%5D%5Bsearchable%5D=true&columns%5B12%5D%5Borderable%5D=true';

        $searchParams = [];
        parse_str($queryString, $searchParams);

        $response = $this->actingAs(User::where('business_id', $this->businessId)->firstOrFail())
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->call('GET', '/real-time-entries/payment-summary', $searchParams, [], [], [
                'HTTP_X-Requested-With' => 'XMLHttpRequest'
            ]);

        $response->assertOk();
        $data = $response->json('data');
        $this->assertNotEmpty($data);

        $cashRow = collect($data)->firstWhere('id', $paymentId);
        $this->assertNotNull($cashRow, "Could not find the seeded cash payment in the datatable response.");
        $this->assertEquals('Walk-In Customer', $cashRow['customer_name']);
    }
}


