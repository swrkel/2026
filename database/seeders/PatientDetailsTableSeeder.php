<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PatientDetailsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('patient_details')->delete();
        
        \DB::table('patient_details')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 2,
                'name' => 'Nayeli',
                'address' => '126 Peck',
                'country' => 'Andorra',
                'state' => 'IN',
                'city' => 'Cummings',
                'mobile' => 'Itzayana Rosari',
                'date_of_birth' => '1',
                'gender' => '1',
                'marital_status' => 1,
                'blood_group' => 1,
                'height' => 'Itzayana R',
                'weight' => 'Itzayana R',
                'guardian_name' => 'Nayeli',
                'time_zone' => 'Etc/GMT+12',
                'profile_image' => '',
                'known_allergies' => 'Itzayana Rosario',
                'notes' => 'Itzayana Rosario',
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => 3,
                'name' => 'Maci',
                'address' => '748 Yu',
                'country' => 'Andorra',
                'state' => 'MI',
                'city' => 'Obrien',
                'mobile' => 'Pierce Norman',
                'date_of_birth' => '1',
                'gender' => '1',
                'marital_status' => 1,
                'blood_group' => 1,
                'height' => 'Pierce Nor',
                'weight' => 'Pierce Nor',
                'guardian_name' => 'Maci',
                'time_zone' => 'Etc/GMT+12',
                'profile_image' => '',
                'known_allergies' => 'Pierce Norman',
                'notes' => 'Pierce Norman',
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
            ),
        ));
        
        
    }
}