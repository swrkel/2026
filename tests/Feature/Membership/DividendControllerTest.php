<?php

namespace Tests\Feature\Membership;

use Tests\TestCase;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DividendControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_auto_saves_dividends_when_marking_as_checked()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        $this->actingAs($user);
        $this->withSession([
            'user.business_id' => $business_id,
            'business' => ['id' => $business_id, 'date_format' => 'm/d/Y']
        ]);

        // Seed basic settings
        $settingId = DB::table('membership_settings')->insertGetId([
            'business_id' => $business_id,
            'region' => 'TestRegion',
            'prefix' => 'MBR',
            'starting_number' => 1000,
            'next_sequence' => 1001,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $typeId = DB::table('membership_types')->insertGetId([
            'business_id' => $business_id,
            'type_name' => 'Premium',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $statusId = DB::table('membership_statuses')->insertGetId([
            'business_id' => $business_id,
            'status_name' => 'Active',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $memberId = DB::table('membership_members')->insertGetId([
            'business_id' => $business_id,
            'member_number' => 'MBR_TEST_8888',
            'member_name' => 'Lashan',
            'date_joined' => '2026-05-19',
            'membership_setting_id' => $settingId,
            'membership_type_id' => $typeId,
            'membership_status_id' => $statusId,
            'membership_business_type_id' => 1,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 1. Send mark-as-checked with dividends input directly (no prior save)
        $response = $this->post('/membership/dividends/mark-as-checked', [
            'dividend_date' => '05/19/2026',
            'reference_number' => '1',
            'dividends' => [
                [
                    'member_id' => $memberId,
                    'amount' => '1000.00'
                ]
            ]
        ]);

        // This should pass and successfully auto-save + mark as checked
        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'msg' => 'Marked as checked successfully'
        ]);

        // 2. Verify database shows it is saved and checked
        $this->assertDatabaseHas('membership_dividends', [
            'business_id' => $business_id,
            'member_id' => $memberId,
            'dividend_date' => '2026-05-19',
            'reference_number' => '1',
            'dividend_amount' => '1000.00',
            'is_checked' => 1
        ]);
    }

    /** @test */
    public function it_allows_saving_dividends_when_checked_or_approved()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        $this->actingAs($user);
        $this->withSession([
            'user.business_id' => $business_id,
            'business' => ['id' => $business_id, 'date_format' => 'm/d/Y']
        ]);

        $settingId = DB::table('membership_settings')->insertGetId([
            'business_id' => $business_id,
            'region' => 'TestRegion2',
            'prefix' => 'MBR2',
            'starting_number' => 1000,
            'next_sequence' => 1001,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $typeId = DB::table('membership_types')->insertGetId([
            'business_id' => $business_id,
            'type_name' => 'Premium2',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $statusId = DB::table('membership_statuses')->insertGetId([
            'business_id' => $business_id,
            'status_name' => 'Active2',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $memberId = DB::table('membership_members')->insertGetId([
            'business_id' => $business_id,
            'member_number' => 'MBR_TEST_9999',
            'member_name' => 'Lashan2',
            'date_joined' => '2026-05-19',
            'membership_setting_id' => $settingId,
            'membership_type_id' => $typeId,
            'membership_status_id' => $statusId,
            'membership_business_type_id' => 1,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Insert an approved/checked dividend row first
        DB::table('membership_dividends')->insert([
            'business_id' => $business_id,
            'member_id' => $memberId,
            'dividend_date' => '2026-05-19',
            'reference_number' => '2',
            'dividend_amount' => '1000.00',
            'is_checked' => 1,
            'is_approved' => 1,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // Try to update it - this should succeed and not return 403
        $response = $this->post('/membership/dividends/store', [
            'dividend_date' => '05/19/2026',
            'reference_number' => '2',
            'dividends' => [
                [
                    'member_id' => $memberId,
                    'amount' => '1500.00'
                ]
            ]
        ]);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('membership_dividends', [
            'business_id' => $business_id,
            'member_id' => $memberId,
            'dividend_date' => '2026-05-19',
            'reference_number' => '2',
            'dividend_amount' => '1500.00'
        ]);
    }
}

