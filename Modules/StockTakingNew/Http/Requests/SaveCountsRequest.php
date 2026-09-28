<?php
namespace Modules\StockTakingNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SaveCountsRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array { return [
        'counts'=>'required|array|min:1','counts.*.line_id'=>'required|integer|min:1|distinct',
        'counts.*.counted_qty'=>'required|numeric','counts.*.notes'=>'nullable|string|max:1000',
        'counts.*.barcode'=>'nullable|string|max:120','counts.*.bin_location'=>'nullable|string|max:120',
    ]; }
}
