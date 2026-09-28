<?php
namespace Modules\RestaurantNew\Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class RestaurantNewPermissionSeeder extends Seeder
{
 public function run(): void
 {
  if(!Schema::hasTable('permissions')) return;
  foreach(array_keys(require module_path('RestaurantNew','Permissions/permissions.php')) as $permission){
   if(!DB::table('permissions')->where('name',$permission)->where('guard_name','web')->exists()) DB::table('permissions')->insert(['name'=>$permission,'guard_name'=>'web','created_at'=>now(),'updated_at'=>now()]);
  }
  if(app()->bound(\Spatie\Permission\PermissionRegistrar::class)) app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
 }
}
