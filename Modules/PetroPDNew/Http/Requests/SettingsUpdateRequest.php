<?php
namespace Modules\PetroPDNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SettingsUpdateRequest extends FormRequest {
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void {
        foreach (['require_review','require_approval','require_zero_variance','allow_reopen','auto_import_closed_shifts','is_active'] as $key) {
            $this->merge([$key => $this->boolean($key)]);
        }
    }
    public function rules(): array {
        return [
            'location_id'=>'nullable|integer|min:1','settlement_prefix'=>'required|string|max:30',
            'day_end_prefix'=>'required|string|max:30','amount_decimals'=>'required|integer|min:0|max:8',
            'quantity_decimals'=>'required|integer|min:0|max:8','require_review'=>'required|boolean',
            'require_approval'=>'required|boolean','require_zero_variance'=>'required|boolean',
            'allow_reopen'=>'required|boolean','auto_import_closed_shifts'=>'required|boolean','is_active'=>'required|boolean',
        ];
    }
}
