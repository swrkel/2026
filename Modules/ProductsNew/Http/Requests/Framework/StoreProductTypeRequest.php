<?php
namespace Modules\ProductsNew\Http\Requests\Framework;
use Illuminate\Foundation\Http\FormRequest;
class StoreProductTypeRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['name'=>'required|string|max:120','code'=>'required|string|max:60','description'=>'nullable|string','is_active'=>'nullable|boolean']; }
}
