<?php
namespace Modules\PumperDashboardNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ShortageRecoveryRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
        'amount'=>['required','numeric','gt:0'], 'recovery_date'=>['nullable','date'],
        'payment_method'=>['required',Rule::in(['cash','card','cheque','bank','other'])],
        'reference_no'=>['nullable','string','max:191'], 'note'=>['nullable','string','max:2000'],
    ]; }
}
