<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewCalendarEvent;
class LeadsNewCalendarService {
    public function createForFollowup($followup): LeadsNewCalendarEvent {
        return LeadsNewCalendarEvent::create(['business_id'=>$followup->business_id,'lead_id'=>$followup->lead_id,'followup_id'=>$followup->id,'title'=>$followup->title ?? 'Lead Follow-up','starts_at'=>$followup->followup_at ?? now(),'assigned_to'=>$followup->assigned_to ?? null,'description'=>$followup->note ?? null]);
    }
}
