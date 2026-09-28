<?php
namespace Modules\PumperDashboardNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AssignmentStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['pump_id'=>['required','integer','min:1'],'opening_meter'=>['nullable','numeric','min:0'],'unit_price'=>['nullable','numeric','min:0'],'note'=>['nullable','string','max:2000']]; }
}
