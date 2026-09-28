<?php

namespace Modules\Pawning\Services;

class ValuationService
{
    public function calculate($grossWeight, $netWeight, $purity, $marketRate, $advancePercentage)
    {
        $grossWeight = (float) $grossWeight;
        $netWeight = (float) ($netWeight ?: $grossWeight);
        $purity = (float) ($purity ?: 0);
        $marketRate = (float) ($marketRate ?: 0);
        $advancePercentage = (float) ($advancePercentage ?: 0);

        $purityFactor = $purity > 0 ? ($purity / 24) : 1;
        $assessedValue = $netWeight * $marketRate * $purityFactor;
        $advanceAmount = $assessedValue * ($advancePercentage / 100);

        return [
            'assessed_value' => round($assessedValue, 2),
            'advance_amount' => round($advanceAmount, 2),
            'purity_factor' => round($purityFactor, 4),
        ];
    }
}
