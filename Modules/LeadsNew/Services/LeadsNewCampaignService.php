<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewCampaign;
class LeadsNewCampaignService
{
    public function activeCampaigns(){ return LeadsNewCampaign::where('is_active',1)->orderBy('name')->get(); }
    public function roi(LeadsNewCampaign $campaign): array
    {
        $cost=(float)($campaign->cost ?? 0); $revenue=(float)($campaign->revenue ?? 0);
        return ['cost'=>$cost,'revenue'=>$revenue,'roi'=>$cost>0 ? round((($revenue-$cost)/$cost)*100,2) : 0];
    }
}
