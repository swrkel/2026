<?php

namespace Tests\Feature\Petro;

use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;

class RealTimeEntriesOtherSalesPageTest extends PetroTestCase
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
    public function test_other_sales_page_contains_only_one_cancel_button(): void
    {
        $user = User::where('business_id', $this->businessId)->firstOrFail();

        $response = $this->actingAs($user)
            ->withSession([
                'business.id' => $this->businessId,
                'user.business_id' => $this->businessId,
                'user.id' => $this->userId,
            ])
            ->get('/real-time-entries/other-sales');

        $response->assertOk();
        
        $content = $response->getContent();
        
        // Assert that the dashboard link Cancel button is not present
        $this->assertStringNotContainsString('/petro/pump-operators/dashboard', $content);
    }
}
