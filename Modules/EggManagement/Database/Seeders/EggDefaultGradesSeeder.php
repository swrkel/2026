<?php
namespace Modules\EggManagement\Database\Seeders;
use Illuminate\Database\Seeder;use Illuminate\Support\Facades\DB;
class EggDefaultGradesSeeder extends Seeder
{
    public function run(){foreach(DB::connection(config('egg.connection'))->table('business')->pluck('id') as $businessId){foreach([['J','Jumbo',70,null,10],['XL','Extra Large',63,69.99,20],['L','Large',56,62.99,30],['M','Medium',49,55.99,40],['S','Small',null,48.99,50],['R','Reject',null,null,90]] as $x){DB::connection(config('egg.connection'))->table('egg_grades')->updateOrInsert(['business_id'=>$businessId,'code'=>$x[0]],['name'=>$x[1],'min_weight_g'=>$x[2],'max_weight_g'=>$x[3],'sort_order'=>$x[4],'active'=>1,'created_at'=>now(),'updated_at'=>now()]);}}}
}
