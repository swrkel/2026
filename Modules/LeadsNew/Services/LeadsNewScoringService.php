<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewLead;
class LeadsNewScoringService
{
    public function calculate(LeadsNewLead $lead): int
    {
        $score = 0;
        if (!empty($lead->mobile)) $score += 20;
        if (!empty($lead->email)) $score += 15;
        if (!empty($lead->source)) $score += 10;
        if (($lead->priority ?? '') === 'High') $score += 25;
        if (!empty($lead->transaction_date)) $score += 10;
        return min(100, $score);
    }
}
