<?php

namespace Modules\EnterpriseFramework\Services\Print;

class PrintEngine
{
    public function layout(array $report): array
    {
        return ['header' => true, 'footer' => true, 'metadata' => true, 'report' => $report];
    }
}
