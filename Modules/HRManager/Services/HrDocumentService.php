<?php

namespace Modules\HRManager\Services;

use Modules\HRManager\Models\HrDocumentExpiryAlert;

class HrDocumentService
{
    public function createExpiryAlert(array $data): HrDocumentExpiryAlert
    {
        return HrDocumentExpiryAlert::create([
            'business_id' => $data['business_id'],
            'employee_id' => $data['employee_id'],
            'source_table' => $data['source_table'],
            'source_id' => $data['source_id'],
            'document_title' => $data['document_title'],
            'expiry_date' => $data['expiry_date'],
            'alert_status' => 'pending',
        ]);
    }
}
