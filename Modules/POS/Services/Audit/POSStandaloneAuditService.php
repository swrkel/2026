<?php

namespace Modules\POS\Services\Audit;

class POSStandaloneAuditService
{
    public function checks(): array
    {
        return [
            'table_prefix' => 'pos_',
            'standalone_routes' => true,
            'standalone_views' => true,
            'multiple_payments' => true,
            'module_language_files' => true,
        ];
    }
}
