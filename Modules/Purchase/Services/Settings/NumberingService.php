<?php
namespace Modules\Purchase\Services\Settings;

class NumberingService
{
    public function __construct(private SettingsDataService $data) {}
    public function getSettings(int $businessId): array { return $this->data->numbering($businessId); }
}
