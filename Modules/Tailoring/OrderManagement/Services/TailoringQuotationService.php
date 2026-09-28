<?php
namespace Modules\Tailoring\OrderManagement\Services;

use Illuminate\Http\Request;

class TailoringQuotationService
{
    public function summary(Request $request): array
    {
        return [
            'draft' => 0,
            'sent' => 0,
            'approved' => 0,
            'converted' => 0,
        ];
    }

    public function find($id): array
    {
        return [
            'id' => $id,
            'quotation_no' => '',
            'customer' => '',
            'items' => [],
            'total' => 0,
        ];
    }
}
