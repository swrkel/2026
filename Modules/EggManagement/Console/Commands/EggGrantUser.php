<?php
namespace Modules\EggManagement\Console\Commands;
use Illuminate\Console\Command;use Illuminate\Support\Facades\DB;
class EggGrantUser extends Command
{
    protected $signature='egg:grant-user {business_id : Business ID} {user_id : User ID}';protected $description='Grant all Egg Management permissions to one business user.';
    public function handle(){ $permissions=require __DIR__.'/../../Permissions/permissions.php';$db=DB::connection(config('egg.connection'));foreach($permissions as $p){$db->table('egg_access_grants')->updateOrInsert(['business_id'=>(int)$this->argument('business_id'),'user_id'=>(int)$this->argument('user_id'),'permission'=>$p['name']],['allowed'=>1,'created_at'=>now(),'updated_at'=>now()]);}$this->info('Granted '.count($permissions).' Egg permissions.');return 0; }
}
