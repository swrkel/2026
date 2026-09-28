<?php

namespace Tests\Feature\Mpcs;

use App\Business;
use App\BusinessLocation;
use App\User;
use App\Category;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Modules\MPCS\Entities\Mpcs9aFormSettings;
use Illuminate\Support\Facades\DB;

class MPCS9AFormTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_9a_form_carries_forward_opening_details_to_subsequent_dates()
    {
        $this->withoutExceptionHandling();
        $business = Business::first();
        $user = User::where('business_id', $business->id)->first();
        $location = BusinessLocation::where('business_id', $business->id)->first();

        // Create Category
        $category = Category::create([
            'name' => 'Test Category',
            'business_id' => $business->id,
            'parent_id' => 1, // Sub-category since parent_id != 0
            'created_by' => $user->id
        ]);

        // Delete existing settings to prevent conflict with seeded data
        Mpcs9aFormSettings::where('business_id', $business->id)->delete();

        // Create Setting for 2026-05-01 (Opening Date)
        $sub_categories_data = [
            [
                'id' => $category->id,
                'name' => $category->name,
                'cash_previous_day' => 150.00,
                'credit_previous_day' => 250.00,
                'sales_previous_day' => 150.00, // For Form9ASettingsController support
                'receipts_previous_day' => 250.00, // For Form9ASettingsController support
            ]
        ];

        Mpcs9aFormSettings::create([
            'business_id' => $business->id,
            'date' => '2026-05-01',
            'starting_number' => 10,
            'ref_pre_form_number' => 'REF-001',
            'total_sale_to_pre' => 1000,
            'pre_day_cash_sale' => 100,
            'pre_day_card_sale' => 100,
            'pre_day_credit_sale' => 100,
            'pre_day_cash' => 500,
            'pre_day_cheques' => 300,
            'pre_day_bank_manual' => json_encode([]),
            'pre_day_card_manual' => json_encode([]),
            'pre_day_total' => 800,
            'pre_day_balance' => 200,
            'pre_day_grand_total' => 1000,
            'no_of_rows_per_page' => 10,
            'sub_categories_data' => json_encode($sub_categories_data)
        ]);

        // Get Form 9A details for 2026-05-02 (the following day)
        // 1. Test via Form9ASettingsController::get9AForm (/mpcs/get-9a-form)
        $response1 = $this->actingAs($user)
            ->withSession([
                'business.id' => $business->id,
                'user.business_id' => $business->id
            ])
            ->getJson('/mpcs/get-9a-form?form_9a_location_id=' . $location->id . '&selected_date=2026-05-02');

        $response1->assertStatus(200);
        $data1 = $response1->json('sub_categories_data');
        $this->assertNotEmpty($data1);
        $this->assertEquals(150.00, $data1[0]['row3'], 'Row 3 should carry forward the opening cash detail');
        $this->assertEquals(250.00, $data1[0]['row4'], 'Row 4 should carry forward the opening credit detail');

        // 2. Test via MPCSController::get9AForm (/mpcs/get-9a-form_value)
        $response2 = $this->actingAs($user)
            ->withSession([
                'business.id' => $business->id,
                'user.business_id' => $business->id
            ])
            ->getJson('/mpcs/get-9a-form_value?form_9a_location_id=' . $location->id . '&selected_date=2026-05-02');

        $response2->assertStatus(200);
        $data2 = $response2->json('sub_categories_data');
        $this->assertNotEmpty($data2);
        $this->assertEquals(150.00, $data2[0]['row3'], 'Row 3 should carry forward the opening cash detail');
        $this->assertEquals(250.00, $data2[0]['row4'], 'Row 4 should carry forward the opening credit detail');
    }
}
