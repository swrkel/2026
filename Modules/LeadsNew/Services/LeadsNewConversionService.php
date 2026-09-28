<?php
namespace Modules\LeadsNew\Services;
use Illuminate\Support\Facades\DB; use Modules\LeadsNew\Models\LeadsNewLead; use Modules\LeadsNew\Models\LeadsNewConversion;
class LeadsNewConversionService {
 public function convert(LeadsNewLead $lead, string $type, ?int $targetId=null, ?int $userId=null): LeadsNewConversion { return DB::transaction(function() use($lead,$type,$targetId,$userId){ $lead->update(['status'=>'converted']); return LeadsNewConversion::create(['business_id'=>$lead->business_id,'lead_id'=>$lead->id,'converted_to_type'=>$type,'converted_to_id'=>$targetId,'snapshot'=>$lead->toArray(),'converted_by'=>$userId,'converted_at'=>now()]); }); }
 public function rollback(LeadsNewConversion $conversion): void { DB::transaction(function() use($conversion){ if($lead=LeadsNewLead::find($conversion->lead_id)){ $lead->update(['status'=>'open']); } $conversion->delete(); }); }
}
