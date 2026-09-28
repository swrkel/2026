<?php

namespace Modules\CommunicationHub\Services\Campaigns;

use Modules\CommunicationHub\Entities\CommunicationHubCampaign;

class CampaignManagerService
{
    public function summary(): array
    {
        return [
            'draft' => CommunicationHubCampaign::where('status', 'draft')->count(),
            'scheduled' => CommunicationHubCampaign::where('status', 'scheduled')->count(),
            'processing' => CommunicationHubCampaign::where('status', 'processing')->count(),
            'completed' => CommunicationHubCampaign::where('status', 'completed')->count(),
            'cancelled' => CommunicationHubCampaign::where('status', 'cancelled')->count(),
        ];
    }

    public function save(CommunicationHubCampaign $campaign, array $data): CommunicationHubCampaign
    {
        $campaign->fill([
            'name' => $data['name'] ?? $campaign->name,
            'channel' => $data['channel'] ?? 'sms',
            'template_id' => $data['template_id'] ?? null,
            'audience_type' => $data['audience_type'] ?? 'manual',
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'status' => $campaign->exists ? ($campaign->status ?? 'draft') : 'draft',
            'description' => $data['description'] ?? null,
        ])->save();

        return $campaign;
    }

    public function schedule(CommunicationHubCampaign $campaign): void
    {
        $campaign->update(['status' => 'scheduled']);
    }

    public function cancel(CommunicationHubCampaign $campaign): void
    {
        $campaign->update(['status' => 'cancelled']);
    }
}
