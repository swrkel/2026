<?php

namespace Modules\MembershipNew\app\Services;

use Illuminate\Support\Facades\DB;
use Modules\MembershipNew\app\Models\MembershipNewPayment;

class MembershipNewPaymentService
{
    public function create(array $data): MembershipNewPayment
    {
        return DB::transaction(fn () => MembershipNewPayment::create($data));
    }

    public function update(MembershipNewPayment $payment, array $data): MembershipNewPayment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment->update($data);
            return $payment->fresh();
        });
    }
}
