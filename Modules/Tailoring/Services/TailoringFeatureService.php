<?php

namespace Modules\\Tailoring\\Services;

use Modules\Tailoring\Entities\TailoringFeatureSetting;

class TailoringFeatureService
{
    public const BASIC = 'basic';
    public const PROFESSIONAL = 'professional';
    public const ENTERPRISE = 'enterprise';

    public function editionFeatures(string $edition): array
    {
        $basic = ['customers','measurements','measurement_profiles','orders','job_cards','payments','delivery','basic_reports','settings'];
        $professional = array_merge($basic, ['fabric','inventory','production','tailors','departments','trials','alterations','suppliers','expense_reports']);
        $enterprise = array_merge($professional, ['production_planning','capacity_planning','qr_job_cards','barcode_tracking','quality_control','packing','dispatch','executive_dashboard','factory_analytics']);
        return match ($edition) {
            self::ENTERPRISE => $enterprise,
            self::PROFESSIONAL => $professional,
            default => $basic,
        };
    }

    public function canSee(string $feature, ?TailoringFeatureSetting $setting = null): bool
    {
        if (! $setting) {
            return in_array($feature, $this->editionFeatures(self::BASIC), true);
        }
        $enabled = $setting->enabled_features ?: $this->editionFeatures($setting->edition ?: self::BASIC);
        return in_array($feature, $enabled, true);
    }

    public function editionFromWizard(array $answers): string
    {
        if (($answers['business_type'] ?? '') === 'factory' || !empty($answers['need_production_planning']) || !empty($answers['need_barcode'])) {
            return self::ENTERPRISE;
        }
        if (!empty($answers['need_inventory']) || !empty($answers['employees']) && $answers['employees'] !== '1') {
            return self::PROFESSIONAL;
        }
        return self::BASIC;
    }
}
