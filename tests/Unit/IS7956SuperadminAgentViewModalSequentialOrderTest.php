<?php

namespace Tests\Unit;

use Tests\TestCase;

class IS7956SuperadminAgentViewModalSequentialOrderTest extends TestCase
{
    public function test_create_blade_has_fields_in_exact_sequential_order(): void
    {
        $view = file_get_contents(__DIR__ . '/../../Modules/Superadmin/Resources/views/agents/create.blade.php');

        // Let's assert the exact sequential ordering of the form fields:
        // 1. Date & Time
        // 2. Full Name
        // 3. Agent Code
        // 4. Address
        // 5. City
        // 6. District
        // 7. Country
        // 8. Mobile No 1
        // 9. Mobile No 2
        // 10. Mobile No 3
        // 11. Land Number
        // 12. Email
        // 13. Username
        // 14. NIC Number
        // 15. Referral Code
        // 16. Bank Name
        // 17. Account Number
        // 18. Branch
        // 19. NIC Copy
        // 20. Agent Photo

        $orderOfFields = [
            "'date'",
            "'name'",
            "'agent_code'",
            "'address'",
            "'country_id'",
            "'district_id'",
            "'city'",
            "'mobile_number'",
            "'mobile_no_2'",
            "'mobile_no_3'",
            "'land_number'",
            "'email'",
            "'username'",
            "'nic_number'",
            "'referral_code'",
            "'bank_name'",
            "'account_number'",
            "'branch'",
            "'nic_copy'",
            "'agent_photo'",
        ];

        $lastPos = 0;
        foreach ($orderOfFields as $field) {
            $pos = strpos($view, $field);
            $this->assertNotFalse($pos, "Field $field not found in view");
            $this->assertGreaterThan($lastPos, $pos, "Field $field is not in the correct sequential order");
            $lastPos = $pos;
        }
    }
}
