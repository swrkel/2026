<?php

namespace Modules\SettlementSW\Services;

use Illuminate\Support\Facades\Validator;

/** SW_SEP_003 validation service. */
class SettlementSwValidationService extends SettlementSwBaseService
{
    public function validate(array $data, array $rules, array $messages = []): array
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return $this->failure($validator->errors()->first(), [
                'errors' => $validator->errors()->toArray(),
            ]);
        }

        return $this->success('Validation passed.');
    }
}
