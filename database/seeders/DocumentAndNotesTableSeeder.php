<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DocumentAndNotesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('document_and_notes')->delete();
        
        
        
    }
}