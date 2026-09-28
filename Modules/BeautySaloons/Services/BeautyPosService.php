<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Facades\DB;
use Modules\BeautySaloons\Entities\BeautySale;
use Modules\BeautySaloons\Entities\BeautyPayment;

class BeautyPosService
{
    public function createSale(array $data): BeautySale
    {
        return DB::transaction(function () use ($data) {
            $sale = BeautySale::create($data['sale']);
            foreach (($data['payments'] ?? []) as $payment) {
                BeautyPayment::create(array_merge($payment, ['sale_id' => $sale->id]));
            }
            return $sale;
        });
    }
}
