<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PetroTestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $business_id = 1;
        $operator_id = 29; // Test Operator 2026
        $pump_id = 1; // P1
        $now = Carbon::now();
        $today = Carbon::today();

        // Ensure we don't have duplicate shifts for today for this operator
        DB::table('petro_shifts')->where('pump_operator_id', $operator_id)->where('business_id', $business_id)->delete();
        DB::table('pump_operator_assignments')->where('pump_operator_id', $operator_id)->where('business_id', $business_id)->delete();

        // 1. Create PetroShift
        $shift_id = DB::table('petro_shifts')->insertGetId([
            'business_id' => $business_id,
            'pump_operator_id' => $operator_id,
            'status' => 0, // open
            'shift_date' => $today,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // 2. Create PumpOperatorAssignment
        DB::table('pump_operator_assignments')->insert([
            'business_id' => $business_id,
            'pump_id' => $pump_id,
            'pump_operator_id' => $operator_id,
            'starting_meter' => 1000.00,
            'date_and_time' => $now,
            'status' => 'open',
            'created_at' => $now,
            'updated_at' => $now,
            'shift_id' => $shift_id,
            'shift_number' => 100,
            'is_confirmed' => 1,
            'confirmed_at' => $now,
        ]);

        $this->command->info('Shift and Assignment for operator 29 (Test Operator 2026) added successfully!');
    }
}
