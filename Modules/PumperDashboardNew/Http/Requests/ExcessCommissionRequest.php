<?php
namespace Modules\PumperDashboardNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ExcessCommissionRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
        'commission_date'=>['nullable','date'], 'commission_type'=>['required',Rule::in(['fixed','percentage'])],
        'commission_rate'=>['required','numeric','gt:0'], 'note'=>['nullable','string','max:2000'],
    ]; }
}
