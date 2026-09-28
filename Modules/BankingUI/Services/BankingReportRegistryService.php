<?php

namespace Modules\BankingUI\Services;

class BankingReportRegistryService
{
    public function groups(): array
    {
        return config('banking_reports.groups', []);
    }

    public function allReports(): array
    {
        $reports = [];
        foreach ($this->groups() as $groupKey => $group) {
            foreach ($group['reports'] ?? [] as $reportKey) {
                $reports[$reportKey] = [
                    'key' => $reportKey,
                    'group' => $groupKey,
                    'group_label' => $group['label'] ?? $groupKey,
                    'label' => str($reportKey)->replace('_', ' ')->title()->toString(),
                ];
            }
        }
        return $reports;
    }
}
