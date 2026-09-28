<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ArticleTagTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('article_tag')->delete();
        
        
        
    }
}