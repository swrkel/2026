<?php
namespace Modules\ProductsNew\Http\Requests\Framework;
use Illuminate\Foundation\Http\FormRequest;
class StoreCustomFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['product_type_id'=>'nullable|integer','label'=>'required|string|max:120','field_key'=>'required|string|max:80','field_type'=>'required|string|max:40','options'=>'nullable|string','is_required'=>'nullable|boolean','is_searchable'=>'nullable|boolean','sort_order'=>'nullable|integer']; }
}
