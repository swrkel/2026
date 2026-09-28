<?php
namespace Modules\LeadsNew\Database\Seeders;
use Illuminate\Database\Seeder; use Modules\LeadsNew\Models\LeadsNewMessageTemplate;
class LeadsNewStage6Seeder extends Seeder { public function run(){ $businessId=session('business.id') ?? 1; foreach(['email','sms','whatsapp'] as $type){ LeadsNewMessageTemplate::firstOrCreate(['business_id'=>$businessId,'type'=>$type,'name'=>'Default '.strtoupper($type)],['subject'=>'Lead update','body'=>'Dear {{name}}, we will contact you soon.','is_default'=>true,'is_active'=>true]); } } }
