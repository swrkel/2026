<?php

namespace Modules\LeadsNew\Services;

use Illuminate\Support\Facades\DB;

class LeadsNewCustomer360Service
{
    public function profile(int $businessId, int $leadId): array
    {
        $lead = DB::table('leads_new_leads')->where('business_id', $businessId)->where('id', $leadId)->first();

        return [
            'lead' => $lead,
            'timeline' => DB::table('leads_new_activities')->where('lead_id', $leadId)->latest('created_at')->limit(100)->get(),
            'followups' => DB::table('leads_new_followups')->where('lead_id', $leadId)->latest('followup_at')->get(),
            'documents' => DB::table('leads_new_documents')->where('lead_id', $leadId)->latest('created_at')->get(),
            'opportunities' => DB::table('leads_new_opportunities')->where('lead_id', $leadId)->latest('created_at')->get(),
            'quotes' => DB::table('leads_new_quotes')->where('lead_id', $leadId)->latest('created_at')->get(),
        ];
    }
}
