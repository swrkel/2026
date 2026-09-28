<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocManagementSignaturesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('doc_management_signatures')->delete();
        
        \DB::table('doc_management_signatures')->insert(array (
            0 => 
            array (
                'id' => 1,
                'date' => '2024-01-30 11:35:39',
                'location' => 'Main',
                'user' => '29copy',
                'designations' => 'Finance',
                'upload_signature' => 'Note!',
                'signature_levels' => 'PRepared',
                'created_at' => '2024-01-30 07:59:34',
                'updated_at' => '2024-01-29 09:05:36',
            ),
            1 => 
            array (
                'id' => 2,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '8',
                'upload_signature' => 'C:\\fakepath\\stew.jpg',
                'signature_levels' => '',
                'created_at' => '2024-01-31 06:29:21',
                'updated_at' => '2024-01-31 06:29:21',
            ),
            2 => 
            array (
                'id' => 3,
                'date' => '0000-00-00 00:00:00',
            'location' => 'LocationCool (BL0001)',
                'user' => 'User29copy',
                'designations' => 'DesignationsFuelLubricantsGasServicesOther Sales',
                'upload_signature' => 'C:\\fakepath\\noodles.jpg',
                'signature_levels' => '',
                'created_at' => '2024-01-31 06:31:09',
                'updated_at' => '2024-01-31 06:31:09',
            ),
            3 => 
            array (
                'id' => 4,
                'date' => '0000-00-00 00:00:00',
            'location' => 'Cool (BL0001)',
                'user' => '29copy',
                'designations' => 'Gas',
                'upload_signature' => 'C:\\fakepath\\noodles.jpg',
                'signature_levels' => '',
                'created_at' => '2024-01-31 06:34:20',
                'updated_at' => '2024-01-31 06:34:20',
            ),
            4 => 
            array (
                'id' => 5,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '8',
                'upload_signature' => 'images/1707093862.jpeg',
                'signature_levels' => '',
                'created_at' => '2024-02-05 01:44:22',
                'updated_at' => '2024-02-05 01:44:22',
            ),
            5 => 
            array (
                'id' => 6,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '9',
                'upload_signature' => 'images/1707093941.JPG',
                'signature_levels' => '',
                'created_at' => '2024-02-05 01:45:41',
                'updated_at' => '2024-02-05 01:45:41',
            ),
            6 => 
            array (
                'id' => 7,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '7',
                'upload_signature' => 'images/1707094091.JPG',
                'signature_levels' => '',
                'created_at' => '2024-02-05 01:48:11',
                'updated_at' => '2024-02-05 01:48:11',
            ),
            7 => 
            array (
                'id' => 8,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '7',
                'upload_signature' => 'images/1707094289.jpeg',
                'signature_levels' => '',
                'created_at' => '2024-02-05 01:51:29',
                'updated_at' => '2024-02-05 01:51:29',
            ),
            8 => 
            array (
                'id' => 9,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '6',
                'upload_signature' => 'images/1707094324.JPG',
                'signature_levels' => '',
                'created_at' => '2024-02-05 01:52:04',
                'updated_at' => '2024-02-05 01:52:04',
            ),
            9 => 
            array (
                'id' => 10,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '7',
                'upload_signature' => 'images/1707094519.jpeg',
                'signature_levels' => '',
                'created_at' => '2024-02-05 01:55:19',
                'updated_at' => '2024-02-05 01:55:19',
            ),
            10 => 
            array (
                'id' => 11,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '8',
                'upload_signature' => 'images/1707094792.jpeg',
                'signature_levels' => '2',
                'created_at' => '2024-02-05 01:59:52',
                'updated_at' => '2024-02-05 01:59:52',
            ),
            11 => 
            array (
                'id' => 12,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '8',
                'upload_signature' => 'images/1707094822.jpeg',
                'signature_levels' => '2',
                'created_at' => '2024-02-05 02:00:22',
                'updated_at' => '2024-02-05 02:00:22',
            ),
            12 => 
            array (
                'id' => 13,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '0',
                'designations' => '7',
                'upload_signature' => 'images/1707095293.jpeg',
                'signature_levels' => '2',
                'created_at' => '2024-02-05 02:08:13',
                'updated_at' => '2024-02-05 02:08:13',
            ),
            13 => 
            array (
                'id' => 14,
                'date' => '2024-02-05 02:47:24',
                'location' => '',
                'user' => '0',
                'designations' => '',
                'upload_signature' => '',
                'signature_levels' => '',
                'created_at' => '2024-02-05 02:47:24',
                'updated_at' => '2024-02-05 02:47:24',
            ),
            14 => 
            array (
                'id' => 15,
                'date' => '2024-02-05 02:48:00',
                'location' => '',
                'user' => '0',
                'designations' => '',
                'upload_signature' => '',
                'signature_levels' => '',
                'created_at' => '2024-02-05 02:48:00',
                'updated_at' => '2024-02-05 02:48:00',
            ),
            15 => 
            array (
                'id' => 16,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => '7',
                'designations' => 'Finance',
                'upload_signature' => '',
                'signature_levels' => '2',
                'created_at' => '2024-02-05 19:13:06',
                'updated_at' => '2024-02-05 19:13:06',
            ),
            16 => 
            array (
                'id' => 17,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => 'Udesh',
                'designations' => 'Commerce',
                'upload_signature' => '',
                'signature_levels' => 'GM',
                'created_at' => '2024-02-05 19:14:44',
                'updated_at' => '2024-02-05 19:14:44',
            ),
            17 => 
            array (
                'id' => 18,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => 'CO-0003-2',
                'designations' => 'Supervisor',
                'upload_signature' => '',
                'signature_levels' => 'Prepared by',
                'created_at' => '2024-02-05 19:16:00',
                'updated_at' => '2024-02-05 19:16:00',
            ),
            18 => 
            array (
                'id' => 19,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => 'Kumara',
                'designations' => 'Commerce',
                'upload_signature' => '',
                'signature_levels' => 'Prepared by',
                'created_at' => '2024-02-05 19:18:41',
                'updated_at' => '2024-02-05 19:18:41',
            ),
            19 => 
            array (
                'id' => 20,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => 'Kumara',
                'designations' => 'Commerce',
                'upload_signature' => '',
                'signature_levels' => 'Prepared by',
                'created_at' => '2024-02-05 19:20:25',
                'updated_at' => '2024-02-05 19:20:25',
            ),
            20 => 
            array (
                'id' => 21,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => 'Kumara',
                'designations' => 'Commerce',
                'upload_signature' => '',
                'signature_levels' => 'Prepared by',
                'created_at' => '2024-02-05 19:20:57',
                'updated_at' => '2024-02-05 19:20:57',
            ),
            21 => 
            array (
                'id' => 22,
                'date' => '0000-00-00 00:00:00',
                'location' => '2',
                'user' => 'Kumara',
                'designations' => 'Commerce',
                'upload_signature' => '',
                'signature_levels' => 'Prepared by',
                'created_at' => '2024-02-05 19:23:07',
                'updated_at' => '2024-02-05 19:23:07',
            ),
        ));
        
        
    }
}