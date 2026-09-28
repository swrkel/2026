<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EssentialsTodosUsersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('essentials_todos_users')->delete();
        
        
        
    }
}