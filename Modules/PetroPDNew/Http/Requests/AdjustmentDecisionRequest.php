<?php
namespace Modules\PetroPDNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AdjustmentDecisionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['approved_amount'=>'nullable|numeric','note'=>'nullable|string|max:5000']; }
}
