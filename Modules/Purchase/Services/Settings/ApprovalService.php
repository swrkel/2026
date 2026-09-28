<?php
namespace Modules\Purchase\Services\Settings;

class ApprovalService
{
    public function __construct(private SettingsDataService $data) {}
    public function getSettings(int $businessId): array { return $this->data->approval($businessId); }
}
