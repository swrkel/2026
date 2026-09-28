<?php

namespace Modules\Leads\Utils;

use Modules\Leads\Entities\Lead;

class LeadNumberUtil
{
    public function nextLeadNumber(int $business_id): string
    {
        $old_lead = Lead::where('business_id', $business_id)->orderByDesc('id')->first();
        $number = 1;

        if (!empty($old_lead) && !empty($old_lead->lead_no)) {
            $parts = explode('-', $old_lead->lead_no);
            $number = !empty($parts[1]) ? ((int) $parts[1]) + 1 : 1;
        }

        return date('Y') . '-' . $number;
    }
}
