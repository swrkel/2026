<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShiftCloseRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['note' => ['nullable', 'string', 'max:4000'], 'confirmed' => ['accepted']]; }
}
