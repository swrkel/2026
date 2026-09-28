<?php
namespace Modules\PetroPDNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SettlementUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['settlement_date'=>'required|date','notes'=>'nullable|string|max:5000']; }
}
