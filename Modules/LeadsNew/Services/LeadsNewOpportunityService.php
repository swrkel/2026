<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewOpportunity;
class LeadsNewOpportunityService {
 public function createFromLead($lead, array $data=[]): LeadsNewOpportunity { return LeadsNewOpportunity::create(array_merge(['business_id'=>$lead->business_id,'lead_id'=>$lead->id,'opportunity_no'=>'OPP-'.date('Ymd').'-'.str_pad((string)(LeadsNewOpportunity::count()+1),5,'0',STR_PAD_LEFT),'title'=>$lead->name ?? 'Opportunity','assigned_to'=>$lead->assigned_to ?? null],$data)); }
 public function markWon(LeadsNewOpportunity $opp): LeadsNewOpportunity { $opp->update(['status'=>'won','stage'=>'won','probability'=>100]); return $opp; }
 public function markLost(LeadsNewOpportunity $opp,string $reason): LeadsNewOpportunity { $opp->update(['status'=>'lost','stage'=>'lost','lost_reason'=>$reason]); return $opp; }
}
