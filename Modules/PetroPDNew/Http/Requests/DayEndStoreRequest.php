<?php
namespace Modules\PetroPDNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class DayEndStoreRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['day_end_date'=>'required|date','location_id'=>'nullable|integer|min:1','note'=>'nullable|string|max:5000']; }
}
