<?php

namespace Tests\Feature\Membership;

use Tests\TestCase;
use App\User;
use App\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MemberContactExclusionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_members_are_excluded_from_customer_list()
    {
        $user = User::first() ?: User::factory()->create();
        $business_id = $user->business_id;

        $this->actingAs($user);
        $this->withSession([
            'user.business_id' => $business_id,
            'business' => ['id' => $business_id, 'date_format' => 'm/d/Y']
        ]);

        // 1. Create a regular customer contact with a non-null contact_id
        $regularCustomer = Contact::create([
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => 'Regular Customer Jane',
            'mobile' => '0771234567',
            'is_default' => 0,
            'created_by' => $user->id,
            'contact_id' => 'CO12345'
        ]);

        // 1.5 Create an old membership contact with null contact_id and null crm_source
        $oldMember = Contact::create([
            'business_id' => $business_id,
            'type' => 'customer',
            'name' => 'Old Null ID Member',
            'mobile' => '0778888888',
            'is_default' => 0,
            'created_by' => $user->id,
            'contact_id' => null,
            'crm_source' => null
        ]);

        // 2. Create basic membership settings
        $settingId = DB::table('membership_settings')->insertGetId([
            'business_id' => $business_id,
            'region' => 'TestRegionExclusion',
            'prefix' => 'EXC',
            'starting_number' => 1000,
            'next_sequence' => 1001,
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $typeId = DB::table('membership_types')->insertGetId([
            'business_id' => $business_id,
            'type_name' => 'PremiumExclusion',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $statusId = DB::table('membership_statuses')->insertGetId([
            'business_id' => $business_id,
            'status_name' => 'ActiveExclusion',
            'created_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        // 3. Store a member via MembershipController storeMember route
        $storeResponse = $this->post('/membership/members', [
            'membership_setting_id' => $settingId,
            'member_name' => 'Member John Doe',
            'membership_business_type_id' => 1,
            'default_mobile_number' => '0779999999',
            'membership_type_id' => $typeId,
            'membership_status_id' => $statusId,
        ], [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $storeResponse->assertStatus(200);
        $storeResponse->assertJson(['success' => true]);

        // 4. Retrieve the customer list from ContactController
        $customerListResponse = $this->get('/contacts?type=customer', [
            'HTTP_X-Requested-With' => 'XMLHttpRequest'
        ]);

        $customerListResponse->assertStatus(200);
        $json = $customerListResponse->json();

        // 5. Assert that "Regular Customer Jane" is in the list, but "Member John Doe" and "Old Null ID Member" are NOT!
        $regularFound = false;
        $memberFound = false;
        $oldMemberFound = false;

        foreach ($json['data'] as $row) {
            if (strpos($row['name'], 'Regular Customer Jane') !== false) {
                $regularFound = true;
            }
            if (strpos($row['name'], 'Member John Doe') !== false) {
                $memberFound = true;
            }
            if (strpos($row['name'], 'Old Null ID Member') !== false) {
                $oldMemberFound = true;
            }
        }

        $this->assertTrue($regularFound, "Regular customer should be present in the customer list.");
        $this->assertFalse($memberFound, "Member should be excluded from the customer list.");
        $this->assertFalse($oldMemberFound, "Old member with null contact_id should be excluded from the customer list.");
    }
}
