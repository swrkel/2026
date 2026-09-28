<?php

namespace Modules\BeautySaloons\Services;

use Illuminate\Support\Arr;
use Modules\BeautySaloons\Entities\BeautyCustomerProfile;

class BeautyCustomerService
{
    public function create(array $data): BeautyCustomerProfile
    {
        $data['customer_code'] = $data['customer_code'] ?? $this->nextCustomerCode($data['business_id'] ?? null);
        return BeautyCustomerProfile::create($this->cleanPayload($data));
    }

    public function update(BeautyCustomerProfile $customer, array $data): BeautyCustomerProfile
    {
        $customer->update($this->cleanPayload($data));
        return $customer->fresh();
    }

    public function nextCustomerCode(?int $businessId = null): string
    {
        $prefix = 'BS-CUS-';
        $lastId = (int) BeautyCustomerProfile::query()
            ->when($businessId, fn ($q) => $q->where('business_id', $businessId))
            ->max('id');
        return $prefix . str_pad((string)($lastId + 1), 6, '0', STR_PAD_LEFT);
    }

    private function cleanPayload(array $data): array
    {
        return Arr::only($data, [
            'business_id','business_location_id','customer_code','full_name','mobile','email','gender','dob','nic_no','address',
            'customer_type','preferred_staff_id','preferred_service_id','skin_type','hair_type','allergies','medical_notes',
            'sms_enabled','email_enabled','whatsapp_enabled','loyalty_enabled','opening_balance','credit_limit','credit_days',
            'status','created_by','updated_by'
        ]);
    }
}
