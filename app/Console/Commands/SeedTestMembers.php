<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Membership\Entities\MembershipMember;
use Modules\Membership\Entities\MembershipSetting;
use Modules\Membership\Entities\MembershipStatus;
use Modules\Membership\Entities\MembershipType;

class SeedTestMembers extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'membership:seed-test {business_id=3}';

    /**
     * The console command description.
     */
    protected $description = 'Seed 5 test members for the specified tenant database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $businessId = (int) $this->argument('business_id');

        $this->info("Using current database connection for Business ID: {$businessId}...");

        try {
            // 1. Ensure Region/Setting exists
            $setting = MembershipSetting::firstOrCreate([
                'business_id' => $businessId,
                'region' => 'Asia'
            ], [
                'region' => 'Asia'
            ]);

            // 2. Ensure Status exists
            $status = MembershipStatus::firstOrCreate([
                'business_id' => $businessId,
                'status_name' => 'Active'
            ], [
                'status_name' => 'Active'
            ]);

            // 3. Ensure Type exists
            $type = MembershipType::firstOrCreate([
                'business_id' => $businessId,
                'type_name' => 'Standard'
            ], [
                'type_name' => 'Standard'
            ]);

            // 4. Create 5 test members
            $this->info("Seeding 5 test members...");
            for ($i = 1; $i <= 5; $i++) {
                MembershipMember::updateOrCreate([
                    'business_id' => $businessId,
                    'member_number' => 'M00' . $i
                ], [
                    'membership_setting_id' => $setting->id,
                    'region' => 'Asia',
                    'member_name' => 'Member ' . $i,
                    'membership_status_id' => $status->id,
                    'membership_type_id' => $type->id,
                    'no_of_shares' => 60 - ($i * 10),
                    'total_share_value' => (60 - ($i * 10)) * 20.00,
                    'date_joined' => now()->format('Y-m-d'),
                ]);
            }

            $this->info("Successfully seeded 5 test members for Business ID {$businessId}!");
        } catch (\Exception $e) {
            $this->error("Failed to seed members: " . $e->getMessage());
        }
    }
}
