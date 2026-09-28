<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OperatorLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'company_number' => ['required', 'string', 'max:100'],
            'passcode' => ['required', 'string', 'min:' . config('pumperdashboardnew.login.passcode_min_length', 1), 'max:' . config('pumperdashboardnew.login.passcode_max_length', 20)],
        ];
    }
}
