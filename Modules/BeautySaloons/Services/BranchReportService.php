<?php

namespace Modules\BeautySaloons\Services;

class BranchReportService
{
    public function summary(array $filters = []): array
    {
        return [
            'total_branches' => 0,
            'active_branches' => 0,
            'resource_count' => 0,
            'chair_count' => 0,
            'message' => 'Branch report scaffold ready for DataTable integration.',
        ];
    }
}
