<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewActivityLog;
class LeadsNewAuditService { public function log(string $action, $subject=null, array $old=[], array $new=[]): void { LeadsNewActivityLog::create(['business_id'=>session('business.id') ?? request()->session()->get('user.business_id'),'lead_id'=>$new['lead_id'] ?? $old['lead_id'] ?? null,'subject_type'=>is_object($subject)?get_class($subject):null,'subject_id'=>is_object($subject)?($subject->id??null):null,'action'=>$action,'old_values'=>$old,'new_values'=>$new,'user_id'=>auth()->id(),'ip_address'=>request()->ip(),'user_agent'=>request()->userAgent()]); } }
