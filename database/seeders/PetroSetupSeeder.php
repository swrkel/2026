<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PetroSetupSeeder extends Seeder
{
    public function run()
    {
        // 1. Find user syzygy
        $user = DB::table('users')->where('username', 'syzygy')->first();

        if (!$user) {
            $this->command->error('User syzygy not found!');
            return;
        }

        $this->command->info("Found user: {$user->first_name} (ID: {$user->id}, Business ID: {$user->business_id})");

        $business_id = $user->business_id;
        $user_id     = $user->id;

        // 2. Get location
        $location = DB::table('business_locations')->where('business_id', $business_id)->first();
        if (!$location) {
            $this->command->error('No business location found for business_id=' . $business_id);
            return;
        }
        $location_id = $location->id;
        $this->command->info("Using location: {$location->name} (ID: {$location_id})");

        // 3. Ensure a fuel product exists (use first product or create one)
        $product = DB::table('products')->where('business_id', $business_id)->first();
        if (!$product) {
            // Create a minimal category first
            $category_id = DB::table('categories')->insertGetId([
                'business_id' => $business_id,
                'name'        => 'Fuel',
                'short_code'  => 'FUEL',
                'created_by'  => $user_id,
                'created_at'  => Carbon::now(),
                'updated_at'  => Carbon::now(),
            ]);

            $product_id = DB::table('products')->insertGetId([
                'business_id'  => $business_id,
                'name'         => 'Petrol 92',
                'sku'          => 'FUEL-92',
                'type'         => 'single',
                'tax_type'     => 'inclusive',
                'category_id'  => $category_id,
                'enable_stock' => 1,
                'created_by'   => $user_id,
                'created_at'   => Carbon::now(),
                'updated_at'   => Carbon::now(),
            ]);
        } else {
            $product_id = $product->id;
        }
        $this->command->info("Using product ID: {$product_id}");

        // =====================================================================
        // 4. FUEL TANKS — add 2 if none exist, or add up to 2 total
        // =====================================================================
        $existingTanks = DB::table('fuel_tanks')->where('business_id', $business_id)->get();
        $tank_ids = $existingTanks->pluck('id')->toArray();

        $tanksToCreate = [
            [
                'fuel_tank_number' => 'TANK-001',
                'fuel_type'        => 'Petrol',
                'storage_volume'   => '10000',
                'current_balance'  => '8000',
                'tank_capacity'    => 10000.000,
                'unit_name'        => 'Litre',
            ],
            [
                'fuel_tank_number' => 'TANK-002',
                'fuel_type'        => 'Diesel',
                'storage_volume'   => '15000',
                'current_balance'  => '12000',
                'tank_capacity'    => 15000.000,
                'unit_name'        => 'Litre',
            ],
        ];

        foreach ($tanksToCreate as $tankData) {
            $exists = DB::table('fuel_tanks')
                ->where('business_id', $business_id)
                ->where('fuel_tank_number', $tankData['fuel_tank_number'])
                ->first();

            if ($exists) {
                $this->command->info("Fuel Tank already exists: {$tankData['fuel_tank_number']} (ID: {$exists->id})");
                if (!in_array($exists->id, $tank_ids)) {
                    $tank_ids[] = $exists->id;
                }
            } else {
                $tank_id = DB::table('fuel_tanks')->insertGetId(array_merge($tankData, [
                    'business_id'      => $business_id,
                    'product_id'       => $product_id,
                    'location_id'      => $location_id,
                    'bulk_tank'        => 0,
                    'user_id'          => $user_id,
                    'transaction_date' => Carbon::now()->toDateString(),
                    'created_at'       => Carbon::now(),
                    'updated_at'       => Carbon::now(),
                ]));
                $tank_ids[] = $tank_id;
                $this->command->info("Created Fuel Tank: {$tankData['fuel_tank_number']} (ID: {$tank_id})");
            }
        }

        // =====================================================================
        // 5. PUMPS — add 2 if none exist (linked to tanks above)
        // =====================================================================
        $existingPumps = DB::table('pumps')->where('business_id', $business_id)->get();
        $pump_ids = $existingPumps->pluck('id')->toArray();

        $pumpsToCreate = [
            [
                'pump_name'    => 'Pump-001',
                'pump_no'      => 'P001',
                'fuel_type'    => 'Petrol',
                'tank_index'   => 0,
            ],
            [
                'pump_name'    => 'Pump-002',
                'pump_no'      => 'P002',
                'fuel_type'    => 'Diesel',
                'tank_index'   => 1,
            ],
        ];

        foreach ($pumpsToCreate as $pumpData) {
            $exists = DB::table('pumps')
                ->where('business_id', $business_id)
                ->where('pump_name', $pumpData['pump_name'])
                ->first();

            if ($exists) {
                $this->command->info("Pump already exists: {$pumpData['pump_name']} (ID: {$exists->id})");
                if (!in_array($exists->id, $pump_ids)) {
                    $pump_ids[] = $exists->id;
                }
            } else {
                $tank_id = $tank_ids[$pumpData['tank_index']] ?? $tank_ids[0];

                $pump_id = DB::table('pumps')->insertGetId([
                    'business_id'        => $business_id,
                    'location_id'        => $location_id,
                    'pump_name'          => $pumpData['pump_name'],
                    'pump_no'            => $pumpData['pump_no'],
                    'fuel_type'          => $pumpData['fuel_type'],
                    'fuel_tank_id'       => $tank_id,
                    'product_id'         => $product_id,
                    'storage_tank'       => (string) $tank_id,
                    'starting_meter'     => 0.0000,
                    'last_meter_reading' => 0.000,
                    'temp_meter_reading' => 0.000,
                    'qty'                => '0',
                    'checkk'             => 1,
                    'testing'            => '1',
                    'installation_date'  => Carbon::now()->toDateString(),
                    'transaction_date'   => Carbon::now()->toDateString(),
                    'bulk_sale_meter'    => 0,
                    'created_at'         => Carbon::now(),
                    'updated_at'         => Carbon::now(),
                ]);
                $pump_ids[] = $pump_id;
                $this->command->info("Created Pump: {$pumpData['pump_name']} (ID: {$pump_id}) → Tank ID: {$tank_id}");
            }
        }

        // =====================================================================
        // 6. PUMP OPERATORS — ensure 2 exist
        // =====================================================================
        $existingOperators = DB::table('pump_operators')
            ->where('business_id', $business_id)
            ->get();

        $this->command->info("Existing pump operators: {$existingOperators->count()}");

        $operatorsToCreate = [
            [
                'name'   => 'Operator Alpha',
                'mobile' => '0711000001',
                'cnic'   => 'CNIC-ALPHA-001',
            ],
            [
                'name'   => 'Operator Beta',
                'mobile' => '0711000002',
                'cnic'   => 'CNIC-BETA-002',
            ],
        ];

        $operator_ids = $existingOperators->pluck('id')->toArray();

        foreach ($operatorsToCreate as $i => $opData) {
            $exists = DB::table('pump_operators')
                ->where('business_id', $business_id)
                ->where('name', $opData['name'])
                ->first();

            if ($exists) {
                $this->command->info("Pump Operator already exists: {$opData['name']} (ID: {$exists->id})");
                if (!in_array($exists->id, $operator_ids)) {
                    $operator_ids[] = $exists->id;
                }
            } else {
                $assigned_pump = $pump_ids[$i] ?? $pump_ids[0] ?? null;

                $op_id = DB::table('pump_operators')->insertGetId([
                    'business_id'     => $business_id,
                    'location_id'     => $location_id,
                    'pump_id'         => $assigned_pump ?? 0,
                    'assigned_pump_id'=> $assigned_pump ?? 0,
                    'name'            => $opData['name'],
                    'cnic'            => $opData['cnic'],
                    'address'         => 'Station Address',
                    'dob'             => '1990-01-01',
                    'mobile'          => $opData['mobile'],
                    'commission_type' => 'none',
                    'commission_ap'   => 0,
                    'short_amount'    => 0,
                    'excess_amount'   => 0,
                    'active'          => 1,
                    'is_default'      => 0,
                    'can_fullscreen'  => 0,
                    'created_at'      => Carbon::now(),
                    'updated_at'      => Carbon::now(),
                ]);
                $operator_ids[] = $op_id;
                $this->command->info("Created Pump Operator: {$opData['name']} (ID: {$op_id}) → Pump ID: {$assigned_pump}");
            }
        }

        // =====================================================================
        // Summary
        // =====================================================================
        $this->command->info('');
        $this->command->info('=== Seeding Complete ===');
        $this->command->info('Tank IDs  : ' . implode(', ', $tank_ids));
        $this->command->info('Pump IDs  : ' . implode(', ', $pump_ids));
        $this->command->info('Operator IDs: ' . implode(', ', $operator_ids));
    }
}
