<?php
namespace Modules\Audit\Database\Seeders;
use Illuminate\Database\Seeder;use Illuminate\Support\Facades\DB;use Illuminate\Support\Facades\Schema;
class AuditPermissionsSeeder extends Seeder
{
    public function run(){if(!Schema::hasTable('permissions'))return;$names=['audit.view','audit.run','audit.findings.view','audit.findings.resolve','audit.rules.manage','audit.schedules.manage','audit.reports.view','audit.reports.export'];foreach($names as $name){$exists=DB::table('permissions')->where('name',$name)->exists();if(!$exists){$row=['name'=>$name,'created_at'=>now(),'updated_at'=>now()];if(Schema::hasColumn('permissions','guard_name'))$row['guard_name']='web';DB::table('permissions')->insert($row);}}}
}
