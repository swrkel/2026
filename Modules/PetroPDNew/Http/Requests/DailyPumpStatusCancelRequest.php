<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DailyPumpStatusCancelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
