<?php
namespace Modules\Tailoring\OrderManagement\Services;

use Illuminate\Http\Request;

class TailoringJobCardService
{
    public function summary(Request $request): array
    {
        return [
            'total' => 0,
            'pending' => 0,
            'active' => 0,
            'completed' => 0,
            'overdue' => 0,
        ];
    }

    public function find($id): array
    {
        return [
            'id' => $id,
            'job_card_no' => '',
            'order_no' => '',
            'customer' => '',
            'garment' => '',
            'workflow' => [],
            'materials' => [],
            'measurements' => [],
        ];
    }
}
