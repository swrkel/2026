<?php

namespace Modules\Membership\Utils;

use Illuminate\Support\Facades\Validator;

class MembershipValidator
{
    public function memberRules(bool $isUpdate = false): array
    {
        return [
            'business_id' => 'required|integer',
            'location_id' => 'nullable|integer',
            'member_name' => 'required|string|max:191',
            'member_no' => ($isUpdate ? 'nullable' : 'required') . '|string|max:191',
            'mobile' => 'nullable|string|max:30',
            'transaction_date' => 'nullable|date_format:Y-m-d',
        ];
    }

    public function validateMember(array $data, bool $isUpdate = false): array
    {
        return Validator::make($data, $this->memberRules($isUpdate))->validate();
    }
}
